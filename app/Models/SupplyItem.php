<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id',
        'product_id',
        'qty',
        'purchase_price',
        'prices',
        'line_cost',
        'note',
    ];

    protected $casts = [
        'qty' => 'integer',
        'purchase_price' => 'decimal:2',
        'prices' => 'array',
        'line_cost' => 'decimal:2',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
