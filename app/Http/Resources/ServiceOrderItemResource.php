<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_type' => $this->item_type->value,
            'product_id' => $this->product_id,
            'service_id' => $this->service_id,
            'item_name' => $this->item_name,
            'price_snapshot' => (float) $this->price_snapshot,
            'qty' => $this->qty,
            'subtotal' => (float) $this->subtotal,
            'note' => $this->note,
        ];
    }
}
