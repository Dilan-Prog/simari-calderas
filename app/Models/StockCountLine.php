<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountLine extends Model
{
    protected $fillable = ['count_id', 'product_id', 'system_quantity', 'counted_quantity', 'difference'];

    public function count()
    {
        return $this->belongsTo(StockCount::class, 'count_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
