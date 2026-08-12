<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Customer extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'customer';
    }

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
        'rfc',
        'tipo_persona',
        'notes',
        'portal_access',
    ];

    protected $casts = [
        'portal_access' => 'boolean',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword(): string
    {
        // Nullable: los clientes creados desde el admin pueden no tener
        // contraseña hasta que se les otorga acceso (o la establecen vía
        // "olvidé mi contraseña"). Cadena vacía => attempt() siempre falla.
        return $this->password_hash ?? '';
    }

    /**
     * La notificación default de Laravel enlaza a route('password.reset')
     * (flujo del admin); los clientes deben recibir el enlace de la tienda.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\CustomerResetPassword($token));
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

    public function portalRequest()
    {
        return $this->hasOne(CustomerPortalRequest::class);
    }

    public function deals()
    {
        return $this->hasMany(\App\Models\Deal::class);
    }

    public function quotes()
    {
        return $this->hasMany(\App\Models\Quote::class);
    }
}
