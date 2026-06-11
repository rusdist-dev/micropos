<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction->value,
            'direction_label' => $this->direction->label(),
            'product_id' => $this->product_id,
            'item_name' => $this->item_name,
            'price_type_used' => $this->price_type_used,
            'price_snapshot' => (float) $this->price_snapshot,
            'qty' => (int) $this->qty,
            'subtotal' => (float) $this->subtotal,
            'restock' => (bool) $this->restock,
            'note' => $this->note,
        ];
    }
}
