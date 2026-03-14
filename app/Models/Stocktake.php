<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Stocktake extends Model
{
    protected $fillable = [
        'code',
        'warehouse_id',
        'checked_at',
        'note',
        'apply_adjustment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'date',
            'apply_adjustment' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class);
    }
}
