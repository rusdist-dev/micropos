<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'kasir_id' => $this->kasir_id,
            'kasir_name' => $this->whenLoaded('kasir', fn () => $this->kasir?->name),
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name ?? 'Pelanggan Umum'),
            'total' => (float) $this->total,
            'payment_amount' => (float) $this->payment_amount,
            'change_amount' => (float) $this->change_amount,
            'note' => $this->note,
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
