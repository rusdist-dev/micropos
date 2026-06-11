<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel `returns` (kata "Return" reserved di PHP, jadi nama kelas ReturnTransaction).
 */
class ReturnTransaction extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'code',
        'transaction_id',
        'kasir_id',
        'returned_total',
        'exchange_total',
        'balance',
        'payment_amount',
        'refund_amount',
        'note',
    ];

    protected $casts = [
        'returned_total' => 'decimal:2',
        'exchange_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
