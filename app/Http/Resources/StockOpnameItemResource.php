<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'sku' => $this->whenLoaded('product', fn () => $this->product?->sku),
            'system_qty' => (int) $this->system_qty,
            'counted_qty' => $this->counted_qty !== null ? (int) $this->counted_qty : null,
            'difference' => (int) $this->difference,
            'note' => $this->note,
        ];
    }
}
