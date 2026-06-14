<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'technician_id',
        'operator_id',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'payment_status',
        'service_status',
        'due_date',
        'completed_at',
        'cancel_note',
        'cancellation_fee',
        'canceled_at',
        'note',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'payment_status' => PaymentStatus::class,
        'service_status' => ServiceStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'customer_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function isProcess(): bool
    {
        return $this->service_status === ServiceStatus::Process;
    }

    /** Sisa yang belum dibayar. */
    public function remaining(): float
    {
        return max(0.0, (float) $this->total - (float) $this->paid_amount);
    }

    /** Dana yang dikembalikan saat batal = DP dibayar - biaya pembatalan yang ditahan. */
    public function refundAmount(): float
    {
        return max(0.0, (float) $this->paid_amount - (float) $this->cancellation_fee);
    }
}
