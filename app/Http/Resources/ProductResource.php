<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $default = $this->whenLoaded('prices', fn () => $this->prices->firstWhere('is_active_default', true));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'brand' => $this->brand,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'is_low_stock' => $this->stock <= $this->min_stock,
            'purchase_price' => (float) $this->purchase_price,
            'image_url' => $this->image_url ? asset('storage/' . ltrim($this->image_url, '/')) : null,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'prices' => ProductPriceResource::collection($this->whenLoaded('prices')),
            'default_price' => $this->when(
                $this->relationLoaded('prices') && $default,
                fn () => [
                    'price_type' => $default?->price_type,
                    'price' => (float) ($default?->price ?? 0),
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
