<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'bom';
    }

    protected $fillable = ['product_id', 'version', 'name', 'is_active', 'yield_quantity', 'notes'];

    protected $casts = [
        'version'        => 'integer',
        'is_active'      => 'boolean',
        'yield_quantity' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getLabelAttribute(): string
    {
        return 'v' . $this->version . ($this->name ? ' · ' . $this->name : '');
    }
}
