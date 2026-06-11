<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'qty_change' => (int) $this->qty_change,
            'stock_before' => (int) $this->stock_before,
            'stock_after' => (int) $this->stock_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
