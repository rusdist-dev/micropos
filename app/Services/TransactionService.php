<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Support\SequenceGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly SequenceGenerator $sequences,
    ) {
    }

    /**
     * Buat transaksi: validasi stok, snapshot harga, hitung total,
     * generate invoice, dan kurangi stok — seluruhnya dalam satu DB transaction.
     *
     * @param  array{customer_id?:int|null,payment_amount:int|float,note?:string|null,items:array<int,array<string,mixed>>}  $data
     */
    public function create(array $data, User $kasir): Transaction
    {
        return DB::transaction(function () use ($data, $kasir) {
            $resolvedItems = [];
            $total = 0;

            foreach ($data['items'] as $index => $item) {
                $type = $item['item_type'];
                $qty = (int) $item['qty'];

                if ($type === ItemType::Product->value) {
                    $resolved = $this->resolveProductItem($item, $qty, $index);
                } else {
                    $resolved = $this->resolveServiceItem($item, $qty);
                }

                $total += $resolved['subtotal'];
                $resolvedItems[] = $resolved;
            }

            $payment = (float) $data['payment_amount'];
            if ($payment < $total) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'Jumlah bayar kurang dari total (' . number_format($total, 0, ',', '.') . ').',
                ]);
            }

            $transaction = Transaction::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'kasir_id' => $kasir->id,
                'customer_id' => $data['customer_id'] ?? null,
                'total' => $total,
                'payment_amount' => $payment,
                'change_amount' => $payment - $total,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($resolvedItems as $resolved) {
                $transaction->items()->create($resolved['attributes']);

                // Kurangi stok untuk item produk via ledger (produk sudah di-lock & tervalidasi).
                if ($resolved['product'] instanceof Product) {
                    $this->movements->apply(
                        $resolved['product'],
                        StockMovementType::Sale,
                        -$resolved['attributes']['qty'],
                        $transaction,
                        $kasir,
                    );
                }
            }

            return $transaction->load(['items', 'kasir', 'customer']);
        });
    }

    /**
     * Resolusi item produk: ambil harga otoritatif dari DB & validasi stok.
     *
     * @return array{subtotal:float,attributes:array<string,mixed>,product:Product}
     */
    protected function resolveProductItem(array $item, int $qty, int $index): array
    {
        /** @var Product $product */
        $product = Product::lockForUpdate()->find($item['product_id']);

        if (! $product || ! $product->is_active) {
            throw ValidationException::withMessages([
                "items.$index.product_id" => 'Produk tidak tersedia.',
            ]);
        }

        $priceRow = $product->prices()->where('price_type', $item['price_type'])->first();
        if (! $priceRow) {
            throw ValidationException::withMessages([
                "items.$index.price_type" => "Produk \"{$product->name}\" tidak memiliki harga untuk tipe tersebut.",
            ]);
        }

        if ($product->stock < $qty) {
            throw ValidationException::withMessages([
                "items.$index.qty" => "Stok \"{$product->name}\" tidak mencukupi (tersisa {$product->stock}).",
            ]);
        }

        $price = (float) $priceRow->price;

        return [
            'subtotal' => $price * $qty,
            'product' => $product,
            'attributes' => [
                'item_type' => ItemType::Product->value,
                'product_id' => $product->id,
                'service_id' => null,
                'item_name' => $product->name,
                'price_type_used' => $item['price_type'],
                'price_snapshot' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'note' => $item['note'] ?? null,
            ],
        ];
    }

    /**
     * Resolusi item jasa: harga dari kasir (custom) atau template.
     *
     * @return array{subtotal:float,attributes:array<string,mixed>,product:null}
     */
    protected function resolveServiceItem(array $item, int $qty): array
    {
        $serviceId = $item['service_id'] ?? null;
        $name = $item['item_name'];
        $price = (float) $item['price'];

        // Jika merujuk template tapi tanpa nama, ambil dari template.
        if ($serviceId && empty($name)) {
            $service = Service::find($serviceId);
            $name = $service?->name ?? $name;
        }

        return [
            'subtotal' => $price * $qty,
            'product' => null,
            'attributes' => [
                'item_type' => ItemType::Service->value,
                'product_id' => null,
                'service_id' => $serviceId,
                'item_name' => $name,
                'price_type_used' => null,
                'price_snapshot' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'note' => $item['note'] ?? null,
            ],
        ];
    }

    /**
     * Format: INV-YYYYMMDD-XXXXX (counter reset per hari).
     */
    public function generateInvoiceNumber(?Carbon $date = null): string
    {
        return $this->sequences->next(Transaction::class, 'invoice_number', 'INV', $date);
    }
}
