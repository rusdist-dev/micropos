<?php

namespace App\Models;

use App\Enums\ReturnDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'direction',
        'product_id',
        'transaction_item_id',
        'item_name',
        'price_type_used',
        'price_snapshot',
        'qty',
        'subtotal',
        'restock',
        'note',
    ];

    protected $casts = [
        'direction' => ReturnDirection::class,
        'price_snapshot' => 'decimal:2',
        'qty' => 'integer',
        'subtotal' => 'decimal:2',
        'restock' => 'boolean',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnTransaction::class, 'return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(TransactionItem::class);
    }
}
