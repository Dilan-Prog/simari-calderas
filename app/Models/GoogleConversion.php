<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleConversion extends Model
{
    protected $fillable = [
        'gclid',
        'conversion_name',
        'conversion_value',
        'currency_code',
        'conversion_time',
        'order_id',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'conversion_time' => 'datetime',
        'sent_at'         => 'datetime',
        'conversion_value'=> 'decimal:2',
    ];

    // Scope para conversiones pendientes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope para conversiones fallidas
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
}
