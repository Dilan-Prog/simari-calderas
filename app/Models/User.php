<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Role;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'phone',
        'password',
        'avatar_url',
        'status',
        'rfc',
        'curp',
        'social_segurity_number',
        'birthdate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function contactEmergency()
    {
        return $this->hasMany(ContactEmergency::class, 'user_id');
    }

    public function hasPermission(string $module): bool
    {
        // Admin siempre tiene acceso a todo
        if ($this->isAdmin()) {
            return true;
        }

        // Cache por 60 minutos para evitar queries repetidas
        $permissions = cache()->remember(
            "user_{$this->id}_permissions",
            now()->addMinutes(60),
            fn() => $this->role?->permissions()
                        ->pluck('module')
                        ->toArray() ?? []
        );

        return in_array($module, $permissions);
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    // Limpia el cache de permisos de este usuario
    public function clearPermissionsCache(): void
    {
        cache()->forget("user_{$this->id}_permissions");
    }

    // Accessor para nombre completo
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
