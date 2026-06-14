<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name ?? 'Pelanggan Umum'),
            'technician_id' => $this->technician_id,
            'technician_name' => $this->whenLoaded('technician', fn () => $this->technician?->name),
            'operator_id' => $this->operator_id,
            'operator_name' => $this->whenLoaded('operator', fn () => $this->operator?->name),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'remaining' => $this->remaining(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'service_status' => $this->service_status->value,
            'service_status_label' => $this->service_status->label(),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'cancel_note' => $this->cancel_note,
            'cancellation_fee' => (float) $this->cancellation_fee,
            'refund_amount' => $this->refundAmount(),
            'note' => $this->note,
            'items_count' => $this->when(isset($this->items_count), $this->items_count),
            'items' => ServiceOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
