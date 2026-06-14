<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\Support\SequenceGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logika order servis: workflow Process -> Selesai/Batal.
 *
 * Berbeda dari kasir (Transaction yang immutable & langsung lunas):
 * - harga produk memakai HARGA MODAL (purchase_price), bukan harga jual;
 * - item bisa diubah selama status Process (stok ikut disesuaikan);
 * - pembayaran bertahap (DP -> pelunasan) via paid_amount;
 * - pembatalan mengembalikan stok bahan ke produk.
 */
class ServiceOrderService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly SequenceGenerator $sequences,
    ) {
    }

    /**
     * Mulai servis: snapshot harga modal, kurangi stok bahan, set status Process.
     *
     * @param  array{customer_id?:int|null,technician_id?:int|null,due_date?:string|null,discount?:int|float|null,paid_amount?:int|float|null,note?:string|null,items:array<int,array<string,mixed>>}  $data
     */
    public function create(array $data, User $operator): ServiceOrder
    {
        return DB::transaction(function () use ($data, $operator) {
            $resolved = $this->resolveItems($data['items']);
            $subtotal = array_sum(array_column($resolved, 'subtotal'));

            $discount = $this->resolveDiscount($data['discount'] ?? 0, $subtotal);
            $total = $subtotal - $discount;
            $paid = $this->resolvePayment($data['paid_amount'] ?? 0, $total);

            $order = ServiceOrder::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'technician_id' => $data['technician_id'] ?? null,
                'operator_id' => $operator->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'payment_status' => $this->derivePaymentStatus($total, $paid),
                'service_status' => ServiceStatus::Process,
                'due_date' => $data['due_date'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($resolved as $row) {
                $order->items()->create($row['attributes']);

                if ($row['product'] instanceof Product) {
                    $this->movements->apply(
                        $row['product'],
                        StockMovementType::ServiceOut,
                        -$row['attributes']['qty'],
                        $order,
                        $operator,
                        'Servis ' . $order->invoice_number,
                    );
                }
            }

            return $order->load(['items', 'customer', 'technician', 'operator']);
        });
    }

    /**
     * Ubah item & pembayaran selama status Process. Stok disesuaikan dari selisih
     * konsumsi tiap produk (tambah pemakaian = service_out, kurangi = service_in).
     *
     * @param  array{technician_id?:int|null,due_date?:string|null,discount?:int|float|null,paid_amount?:int|float|null,note?:string|null,items:array<int,array<string,mixed>>}  $data
     */
    public function update(ServiceOrder $order, array $data, User $user): ServiceOrder
    {
        $this->ensureProcess($order);

        return DB::transaction(function () use ($order, $data, $user) {
            // Konsumsi produk sebelum perubahan (product_id => total qty).
            $oldConsumption = $this->productConsumption($order);

            // Stok divalidasi lewat selisih konsumsi (movement apply), bukan qty penuh.
            $resolved = $this->resolveItems($data['items'], false);
            $newConsumption = [];
            foreach ($resolved as $row) {
                if ($row['product'] instanceof Product) {
                    $id = $row['product']->id;
                    $newConsumption[$id] = ($newConsumption[$id] ?? 0) + $row['attributes']['qty'];
                }
            }

            // Terapkan selisih stok per produk. signedQty = oldQty - newQty:
            // butuh lebih banyak (newQty>oldQty) -> negatif (keluar), sebaliknya masuk.
            $productIds = array_unique(array_merge(array_keys($oldConsumption), array_keys($newConsumption)));
            foreach ($productIds as $productId) {
                $delta = ($oldConsumption[$productId] ?? 0) - ($newConsumption[$productId] ?? 0);
                if ($delta === 0) {
                    continue;
                }

                /** @var Product|null $product */
                $product = Product::lockForUpdate()->find($productId);
                if (! $product) {
                    continue; // produk terhapus — lewati
                }

                $this->movements->apply(
                    $product,
                    $delta < 0 ? StockMovementType::ServiceOut : StockMovementType::ServiceIn,
                    $delta,
                    $order,
                    $user,
                    'Penyesuaian servis ' . $order->invoice_number,
                );
            }

            // Bangun ulang item (validasi stok tidak diulang di sini — selisih konsumsi
            // sudah divalidasi oleh StockMovementService::apply yang menolak stok < 0).
            $order->items()->delete();
            foreach ($resolved as $row) {
                $order->items()->create($row['attributes']);
            }

            $subtotal = array_sum(array_column($resolved, 'subtotal'));
            $discount = $this->resolveDiscount($data['discount'] ?? $order->discount, $subtotal);
            $total = $subtotal - $discount;
            $paid = (float) $order->paid_amount;

            $order->update([
                'technician_id' => array_key_exists('technician_id', $data) ? $data['technician_id'] : $order->technician_id,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $order->due_date,
                'note' => array_key_exists('note', $data) ? $data['note'] : $order->note,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_status' => $this->derivePaymentStatus($total, $paid),
            ]);

            return $order->load(['items', 'customer', 'technician', 'operator']);
        });
    }

    /**
     * Selesaikan servis + catat pelunasan (opsional). Status -> Selesai.
     */
    public function complete(ServiceOrder $order, float $payment): ServiceOrder
    {
        $this->ensureProcess($order);

        return DB::transaction(function () use ($order, $payment) {
            $paid = (float) $order->paid_amount + max(0.0, $payment);
            $total = (float) $order->total;

            if ($paid > $total + 0.01) {
                throw ValidationException::withMessages([
                    'payment' => 'Pembayaran melebihi sisa tagihan (' . number_format($order->remaining(), 0, ',', '.') . ').',
                ]);
            }

            $order->update([
                'paid_amount' => $paid,
                'payment_status' => $this->derivePaymentStatus($total, $paid),
                'service_status' => ServiceStatus::Selesai,
                'completed_at' => now(),
            ]);

            return $order->load(['items', 'customer', 'technician', 'operator']);
        });
    }

    /**
     * Batalkan servis: kembalikan stok bahan ke produk. Status -> Batal.
     * $fee = biaya pembatalan (bagian DP yang ditahan sbg pendapatan); sisanya direfund.
     * Jika $fee null, default menahan seluruh DP.
     */
    public function cancel(ServiceOrder $order, string $note, ?float $fee, User $user): ServiceOrder
    {
        $this->ensureProcess($order);

        return DB::transaction(function () use ($order, $note, $fee, $user) {
            foreach ($this->productConsumption($order) as $productId => $qty) {
                /** @var Product|null $product */
                $product = Product::lockForUpdate()->find($productId);
                if (! $product || $qty <= 0) {
                    continue;
                }

                $this->movements->apply(
                    $product,
                    StockMovementType::ServiceIn,
                    $qty,
                    $order,
                    $user,
                    'Pembatalan servis ' . $order->invoice_number,
                );
            }

            $order->update([
                'service_status' => ServiceStatus::Batal,
                'cancel_note' => $note,
                'cancellation_fee' => $this->clampFee($fee, (float) $order->paid_amount),
                'canceled_at' => now(),
            ]);

            return $order->load(['items', 'customer', 'technician', 'operator']);
        });
    }

    /**
     * Edit ulang biaya pembatalan (dan keterangan) pada order yang sudah Batal,
     * agar biaya pembatalan bersifat dinamis. Stok tidak diubah di sini.
     */
    public function updateCancellation(ServiceOrder $order, string $note, ?float $fee): ServiceOrder
    {
        if ($order->service_status !== ServiceStatus::Batal) {
            throw ValidationException::withMessages([
                'status' => 'Biaya pembatalan hanya dapat diubah pada order yang berstatus Batal.',
            ]);
        }

        $order->update([
            'cancel_note' => $note,
            'cancellation_fee' => $this->clampFee($fee, (float) $order->paid_amount),
        ]);

        return $order->load(['items', 'customer', 'technician', 'operator']);
    }

    /** Biaya pembatalan dibatasi 0..DP yang sudah dibayar (tak bisa menahan lebih dari yang dibayar). */
    protected function clampFee(?float $fee, float $paid): float
    {
        if ($fee === null) {
            return $paid; // default: tahan seluruh DP
        }

        return min(max(0.0, $fee), $paid);
    }

    /**
     * Resolusi daftar item: produk (harga modal + validasi stok) & jasa.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array{subtotal:float,product:Product|null,attributes:array<string,mixed>}>
     */
    protected function resolveItems(array $items, bool $validateStock = true): array
    {
        $resolved = [];

        foreach ($items as $index => $item) {
            $qty = (int) $item['qty'];

            $resolved[] = $item['item_type'] === ItemType::Product->value
                ? $this->resolveProductItem($item, $qty, $index, $validateStock)
                : $this->resolveServiceItem($item, $qty);
        }

        return $resolved;
    }

    /**
     * Item produk bahan: harga = HARGA MODAL (purchase_price), validasi stok.
     *
     * @return array{subtotal:float,product:Product,attributes:array<string,mixed>}
     */
    protected function resolveProductItem(array $item, int $qty, int $index, bool $validateStock = true): array
    {
        /** @var Product|null $product */
        $product = Product::lockForUpdate()->find($item['product_id']);

        if (! $product || ! $product->is_active) {
            throw ValidationException::withMessages([
                "items.$index.product_id" => 'Produk tidak tersedia.',
            ]);
        }

        if ($validateStock && $product->stock < $qty) {
            throw ValidationException::withMessages([
                "items.$index.qty" => "Stok \"{$product->name}\" tidak mencukupi (tersisa {$product->stock}).",
            ]);
        }

        $price = (float) $product->purchase_price;

        return [
            'subtotal' => $price * $qty,
            'product' => $product,
            'attributes' => [
                'item_type' => ItemType::Product->value,
                'product_id' => $product->id,
                'service_id' => null,
                'item_name' => $product->name,
                'price_snapshot' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'note' => $item['note'] ?? null,
            ],
        ];
    }

    /**
     * Item jasa servis: harga dari input (atau template bila kosong).
     *
     * @return array{subtotal:float,product:null,attributes:array<string,mixed>}
     */
    protected function resolveServiceItem(array $item, int $qty): array
    {
        $serviceId = $item['service_id'] ?? null;
        $name = $item['item_name'] ?? null;
        $price = (float) $item['price'];

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
                'price_snapshot' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
                'note' => $item['note'] ?? null,
            ],
        ];
    }

    /**
     * Total qty produk yang dikonsumsi order (product_id => qty).
     *
     * @return array<int,int>
     */
    protected function productConsumption(ServiceOrder $order): array
    {
        $map = [];
        foreach ($order->items()->where('item_type', ItemType::Product->value)->get() as $item) {
            if ($item->product_id) {
                $map[$item->product_id] = ($map[$item->product_id] ?? 0) + (int) $item->qty;
            }
        }

        return $map;
    }

    protected function resolveDiscount(int|float $input, float $subtotal): float
    {
        $discount = max(0.0, (float) $input);
        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => 'Diskon tidak boleh melebihi subtotal (' . number_format($subtotal, 0, ',', '.') . ').',
            ]);
        }

        return $discount;
    }

    protected function resolvePayment(int|float $input, float $total): float
    {
        $paid = max(0.0, (float) $input);
        if ($paid > $total + 0.01) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Jumlah bayar tidak boleh melebihi total (' . number_format($total, 0, ',', '.') . ').',
            ]);
        }

        return $paid;
    }

    protected function derivePaymentStatus(float $total, float $paid): PaymentStatus
    {
        if ($paid <= 0) {
            return PaymentStatus::Unpaid;
        }

        return $paid >= $total - 0.01 ? PaymentStatus::Lunas : PaymentStatus::Dp;
    }

    private function ensureProcess(ServiceOrder $order): void
    {
        if (! $order->isProcess()) {
            throw ValidationException::withMessages([
                'status' => 'Order servis sudah ' . $order->service_status->label() . ' dan tidak dapat diubah.',
            ]);
        }
    }

    /** Format: SRV-YYYYMMDD-XXXXX (counter reset per hari). */
    public function generateInvoiceNumber(?Carbon $date = null): string
    {
        return $this->sequences->next(ServiceOrder::class, 'invoice_number', 'SRV', $date);
    }
}
