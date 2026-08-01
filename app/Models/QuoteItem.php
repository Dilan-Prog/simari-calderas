<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id',
        'product_id',
        'service_page_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'discount_percent',
        'tax_percent',
        'line_total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'unit_price'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_percent'      => 'decimal:2',
        'line_total'       => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class);
    }

    public function isService(): bool
    {
        return $this->service_page_id !== null;
    }

    public function isProduct(): bool
    {
        return $this->product_id !== null;
    }
}
