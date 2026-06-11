<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Supply;
use App\Models\User;
use App\Services\Support\SequenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly ProductService $products,
        private readonly SequenceGenerator $sequences,
    ) {
    }

    /**
     * Catat supply: tambah stok, update modal & harga jual (opsional), simpan supplier.
     *
     * @param  array{supplier_id:int,note?:string|null,items:array<int,array<string,mixed>>}  $data
     */
    public function create(array $data, User $user): Supply
    {
        return DB::transaction(function () use ($data, $user) {
            $supply = Supply::create([
                'code' => $this->sequences->next(Supply::class, 'code', 'SUP'),
                'supplier_id' => $data['supplier_id'],
                'user_id' => $user->id,
                'total_cost' => 0,
                'status' => 'posted',
                'note' => $data['note'] ?? null,
            ]);

            // Urut product_id agar konsisten dgn modul lain (hindari deadlock).
            $items = collect($data['items'])->sortBy('product_id')->values();
            $totalCost = 0;

            foreach ($items as $index => $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->find($item['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages(["items.$index.product_id" => 'Produk tidak ditemukan.']);
                }

                $qty = (int) $item['qty'];

                // Modal: gunakan nilai baru bila diisi, jika tidak pertahankan.
                $newCost = $item['purchase_price'] ?? null;
                if ($newCost !== null && $newCost !== '') {
                    $product->purchase_price = $newCost;
                    $product->save();
                }
                $effectiveCost = (float) $product->purchase_price;
                $lineCost = $effectiveCost * $qty;
                $totalCost += $lineCost;

                // Tambah stok via ledger (produk sudah di-lock).
                $this->movements->apply($product, StockMovementType::Supply, $qty, $supply, $user);

                // Update harga jual (sebagian tipe) tanpa menghapus tipe lain.
                $pricesSnapshot = null;
                if (! empty($item['prices'])) {
                    $this->products->upsertPrices($product, $item['prices']);
                    $pricesSnapshot = collect($item['prices'])
                        ->filter(fn ($p) => isset($p['price_type']) && $p['price'] !== null && $p['price'] !== '')
                        ->map(fn ($p) => ['price_type' => $p['price_type'], 'price' => (float) $p['price']])
                        ->values()->all();
                }

                $supply->items()->create([
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'purchase_price' => $newCost !== null && $newCost !== '' ? $newCost : null,
                    'prices' => $pricesSnapshot,
                    'line_cost' => $lineCost,
                    'note' => $item['note'] ?? null,
                ]);
            }

            $supply->update(['total_cost' => $totalCost]);

            return $supply->load(['items.product', 'supplier', 'user']);
        });
    }
}
