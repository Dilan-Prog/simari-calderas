<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransferLine extends Model
{
    protected $fillable = ['transfer_id', 'product_id', 'quantity', 'received_quantity'];

    public function transfer()
    {
        return $this->belongsTo(WarehouseTransfer::class, 'transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
