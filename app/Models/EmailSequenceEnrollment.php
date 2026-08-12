<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSequenceEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'customer_id',
        'current_step',
        'status',
        'next_send_at',
    ];

    protected $casts = [
        'next_send_at' => 'datetime',
    ];

    public function sequence()
    {
        return $this->belongsTo(EmailSequence::class, 'sequence_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
