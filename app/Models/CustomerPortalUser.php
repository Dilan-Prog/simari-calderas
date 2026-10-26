<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Usuario adicional del equipo de un cliente en el portal /customer. NO es un
 * usuario del guard: inicia sesión mediante App\Services\Portal\PortalMemberAuth,
 * que autentica al titular (Customer) y guarda este id en la sesión.
 */
class CustomerPortalUser extends Model
{
    protected $table = 'customer_portal_users';

    protected $fillable = [
        'customer_id', 'role_id', 'first_name', 'last_name', 'email', 'phone',
        'password', 'status', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function role()
    {
        return $this->belongsTo(CustomerPortalRole::class, 'role_id');
    }

    /** Los correos se guardan siempre en minúsculas (igual que customers.email). */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower(trim((string) $value));
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
