<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'note' => $this->note,
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'items' => StockOpnameItemResource::collection($this->whenLoaded('items')),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
