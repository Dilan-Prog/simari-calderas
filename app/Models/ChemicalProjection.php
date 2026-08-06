<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChemicalProjection extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'chemical_projection';
    }

    protected $fillable = ['customer_id', 'product_id', 'projected_quantity', 'period'];

    protected $casts = [
        'projected_quantity' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
