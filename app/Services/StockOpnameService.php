<?php

namespace App\Services;

use App\Enums\OpnameStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use App\Services\Support\SequenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly SequenceGenerator $sequences,
    ) {
    }

    /**
     * Buat sesi opname draft + snapshot stok sistem per produk.
     *
     * @param  array{product_ids?:array<int>,note?:string|null}  $data
     */
    public function createDraft(array $data, User $user): StockOpname
    {
        return DB::transaction(function () use ($data, $user) {
            $opname = StockOpname::create([
                'code' => $this->sequences->next(StockOpname::class, 'code', 'OPN'),
                'status' => OpnameStatus::Draft,
                'user_id' => $user->id,
                'note' => $data['note'] ?? null,
            ]);

            $productIds = $data['product_ids'] ?? null;
            $products = Product::query()
                ->when($productIds, fn ($q) => $q->whereIn('id', $productIds), fn ($q) => $q->where('is_active', true))
                ->orderBy('name')
                ->get(['id', 'stock']);

            foreach ($products as $product) {
                $opname->items()->create([
                    'product_id' => $product->id,
                    'system_qty' => (int) $product->stock,
                    'counted_qty' => null,
                    'difference' => 0,
                ]);
            }

            return $opname->load('items.product');
        });
    }

    /**
     * Simpan hasil hitung (hanya saat draft).
     *
     * @param  array{items:array<int,array{product_id:int,counted_qty:?int,note?:string|null}>}  $data
     */
    public function updateCounts(StockOpname $opname, array $data): StockOpname
    {
        $this->ensureDraft($opname);

        foreach ($data['items'] as $row) {
            $opname->items()->where('product_id', $row['product_id'])->update([
                'counted_qty' => $row['counted_qty'] ?? null,
                'note' => $row['note'] ?? null,
            ]);
        }

        return $opname->load('items.product');
    }

    /**
     * Finalisasi: sinkronkan stok sistem ke hasil hitung. Immutable setelahnya.
     */
    public function finalize(StockOpname $opname, User $user): StockOpname
    {
        $this->ensureDraft($opname);

        return DB::transaction(function () use ($opname, $user) {
            $items = $opname->items()->whereNotNull('counted_qty')->orderBy('product_id')->get();

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) {
                    continue; // produk terhapus — lewati
                }

                $currentStock = (int) $product->stock;
                $counted = (int) $item->counted_qty;

                // Selisih dihitung terhadap stok TERKINI (antisipasi penjualan interim).
                if ($counted !== $currentStock) {
                    $this->movements->applyAbsolute(
                        $product,
                        $counted,
                        StockMovementType::Opname,
                        $opname,
                        $user,
                        'Stok opname ' . $opname->code,
                    );
                }

                $item->update([
                    'system_qty' => $currentStock,
                    'difference' => $counted - $currentStock,
                ]);
            }

            $opname->update([
                'status' => OpnameStatus::Completed,
                'completed_at' => now(),
            ]);

            return $opname->load('items.product');
        });
    }

    private function ensureDraft(StockOpname $opname): void
    {
        if (! $opname->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Opname sudah difinalisasi dan tidak dapat diubah.',
            ]);
        }
    }
}
