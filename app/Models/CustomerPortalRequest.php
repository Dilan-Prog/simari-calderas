<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'purchase_frequency',
        'purchase_amount',
        'reason',
        'responsible_name',
        'rfc',
        'tax_regime',
        'contact_email',
        'phone',
        'fiscal_certificate_path',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
