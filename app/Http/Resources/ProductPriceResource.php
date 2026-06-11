<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price_type' => $this->price_type,
            'price_type_name' => $this->whenLoaded('priceType', fn () => $this->priceType?->name),
            'price' => (float) $this->price,
            'is_active_default' => $this->is_active_default,
        ];
    }
}
