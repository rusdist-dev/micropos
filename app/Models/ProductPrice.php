<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'price_type',
        'price',
        'is_active_default',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Relasi ke master tipe harga via kode. */
    public function priceType(): BelongsTo
    {
        return $this->belongsTo(PriceType::class, 'price_type', 'code');
    }
}
