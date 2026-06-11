<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya titik mutasi stok produk + pencatatan ledger.
 *
 * KONTRAK: pemanggil HARUS sudah me-lock produk (lockForUpdate) dan berjalan
 * di dalam DB::transaction. Service ini tidak membuka transaksi sendiri agar
 * konsisten dengan operasi multi-baris (penjualan, supply, opname, retur).
 */
class StockMovementService
{
    /**
     * Mutasi relatif (tambah/kurang). $signedQty positif menambah, negatif mengurangi.
     * Menolak bila stok akhir < 0.
     */
    public function apply(
        Product $product,
        StockMovementType $type,
        int $signedQty,
        ?Model $reference = null,
        ?User $user = null,
        ?string $note = null
    ): StockMovement {
        $before = (int) $product->stock;
        $after = $before + $signedQty;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'stock' => "Stok \"{$product->name}\" tidak mencukupi (tersisa {$before}).",
            ]);
        }

        return $this->commit($product, $type, $signedQty, $before, $after, $reference, $user, $note);
    }

    /**
     * Set stok ke nilai absolut (dipakai stok opname). qty_change = counted - before.
     */
    public function applyAbsolute(
        Product $product,
        int $countedQty,
        StockMovementType $type,
        ?Model $reference = null,
        ?User $user = null,
        ?string $note = null
    ): StockMovement {
        $before = (int) $product->stock;
        $after = max(0, $countedQty);

        return $this->commit($product, $type, $after - $before, $before, $after, $reference, $user, $note);
    }

    private function commit(
        Product $product,
        StockMovementType $type,
        int $qtyChange,
        int $before,
        int $after,
        ?Model $reference,
        ?User $user,
        ?string $note
    ): StockMovement {
        $product->stock = $after;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'qty_change' => $qtyChange,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'user_id' => $user?->id,
            'note' => $note,
        ]);
    }
}
