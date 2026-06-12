<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * Buat produk beserta baris harga per tipe.
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $image) {
            $product = Product::create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'brand' => $data['brand'] ?? null,
                'stock' => $data['stock'] ?? 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'purchase_price' => $data['purchase_price'] ?? 0,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'image_url' => $image ? $this->storeImage($image) : null,
            ]);

            $this->syncPrices($product, $data['prices'], $data['default_type']);

            return $product->load('prices');
        });
    }

    /**
     * Perbarui produk dan (opsional) harga-harganya.
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $image) {
            $product->fill(array_filter([
                'name' => $data['name'] ?? null,
                'sku' => $data['sku'] ?? null,
                'brand' => $data['brand'] ?? null,
                'description' => $data['description'] ?? null,
            ], fn ($v) => $v !== null));

            // Field yang boleh bernilai 0/false/null ditangani eksplisit.
            foreach (['stock', 'min_stock', 'purchase_price', 'category_id'] as $field) {
                if (array_key_exists($field, $data)) {
                    $product->{$field} = $data[$field];
                }
            }
            if (array_key_exists('is_active', $data)) {
                $product->is_active = $data['is_active'];
            }

            if ($image) {
                $this->deleteImage($product->image_url);
                $product->image_url = $this->storeImage($image);
            }

            $product->save();

            if (! empty($data['prices'])) {
                $this->syncPrices($product, $data['prices'], $data['default_type'] ?? null);
            }

            return $product->load('prices');
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $this->deleteImage($product->image_url);
            $product->delete(); // prices ikut terhapus (cascade FK)
        });
    }

    /**
     * Sinkronkan baris harga produk dan tandai satu sebagai default kasir.
     *
     * @param  array<int,array{price_type:string,price:int|float}>  $prices
     */
    public function syncPrices(Product $product, array $prices, ?string $defaultType): void
    {
        $defaultType ??= $prices[0]['price_type'] ?? null;
        $keepTypes = [];

        foreach ($prices as $row) {
            $product->prices()->updateOrCreate(
                ['price_type' => $row['price_type']],
                [
                    'price' => $row['price'],
                    'is_active_default' => $row['price_type'] === $defaultType,
                ]
            );
            $keepTypes[] = $row['price_type'];
        }

        // Hapus tipe harga yang tidak lagi disertakan.
        $product->prices()->whereNotIn('price_type', $keepTypes)->delete();

        // Pastikan tepat satu default.
        if ($defaultType) {
            $product->prices()->where('price_type', '!=', $defaultType)->update(['is_active_default' => false]);
        }
    }

    /**
     * Update/insert harga tanpa menghapus tipe lain & tanpa mengubah default.
     * Dipakai saat supply (update harga jual sebagian tipe secara bulk).
     *
     * @param  array<int,array{price_type:string,price:int|float}>  $prices
     */
    public function upsertPrices(Product $product, array $prices): void
    {
        foreach ($prices as $row) {
            if (! isset($row['price_type']) || $row['price'] === null || $row['price'] === '') {
                continue;
            }
            $product->prices()->updateOrCreate(
                ['price_type' => $row['price_type']],
                ['price' => $row['price']],
            );
        }
    }

    protected function storeImage(UploadedFile $image): string
    {
        return $image->store('products', 'public'); // mis. "products/abc.jpg"
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
