<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    protected $fillable = ['bom_id', 'component_product_id', 'quantity', 'unit', 'scrap_percent', 'notes', 'sort_order'];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'scrap_percent' => 'decimal:2',
        'sort_order'    => 'integer',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'component_product_id');
    }
}
