<?php

namespace App\Models;

use App\Enums\OpnameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'status',
        'user_id',
        'note',
        'completed_at',
    ];

    protected $casts = [
        'status' => OpnameStatus::class,
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === OpnameStatus::Draft;
    }
}
