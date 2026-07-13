<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'document_type',
        'status',
        'source',
        'company',
        'notes'
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function customer_addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    // FIX QA-11: Added missing relations — destroy() in ClientManageController
    // called withCount(['orders', 'quotes', 'serviceReports']), but none of
    // those relation methods existed on this model, so every delete attempt
    // crashed with a BadMethodCallException. 'services' (table `services`,
    // App\Models\TechnicalService) is this domain's actual "order" concept —
    // there is no separate Order model. Quote has no customer_id (guest-only
    // fields), so it cannot be related here.
    public function services()
    {
        return $this->hasMany(TechnicalService::class);
    }

    public function serviceReports()
    {
        return $this->hasMany(ServiceReport::class);
    }
}
