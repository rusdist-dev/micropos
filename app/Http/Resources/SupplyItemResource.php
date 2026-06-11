<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'qty' => (int) $this->qty,
            'purchase_price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
            'prices' => $this->prices,
            'line_cost' => (float) $this->line_cost,
            'note' => $this->note,
        ];
    }
}
