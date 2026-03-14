<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StocktakeItem extends Model
{
    protected $fillable = [
        'stocktake_id',
        'product_id',
        'system_qty',
        'actual_qty',
        'variance_qty',
    ];

    protected function casts(): array
    {
        return [
            'system_qty' => 'decimal:3',
            'actual_qty' => 'decimal:3',
            'variance_qty' => 'decimal:3',
        ];
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
