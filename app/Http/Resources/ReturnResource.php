<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'transaction_id' => $this->transaction_id,
            'invoice_number' => $this->whenLoaded('transaction', fn () => $this->transaction?->invoice_number),
            'kasir_name' => $this->whenLoaded('kasir', fn () => $this->kasir?->name),
            'returned_total' => (float) $this->returned_total,
            'exchange_total' => (float) $this->exchange_total,
            'balance' => (float) $this->balance,
            'payment_amount' => (float) $this->payment_amount,
            'refund_amount' => (float) $this->refund_amount,
            'note' => $this->note,
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'items' => ReturnItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
