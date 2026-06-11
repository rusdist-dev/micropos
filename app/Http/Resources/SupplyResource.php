<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'total_cost' => (float) $this->total_cost,
            'status' => $this->status,
            'note' => $this->note,
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'items' => SupplyItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
