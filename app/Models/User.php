<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Role;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'user';
    }

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
        'notification_toast_seconds',
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

    /** Usuarios que reciben también las notificaciones de este usuario ("Notificar también a estos usuarios"). */
    public function notificationCopies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_notification_copies', 'user_id', 'copy_user_id');
    }

    public function contactEmergency()
    {
        return $this->hasMany(ContactEmergency::class, 'user_id');
    }

    public function hasPermission(string $module, string $action = 'view'): bool
    {
        // Admin siempre tiene acceso a todo
        if ($this->isAdmin()) {
            return true;
        }

        // Cache por 60 minutos para evitar queries repetidas
        // Estructura: ['module' => ['view', 'create', ...], ...]
        $permissions = cache()->remember(
            "user_{$this->id}_permissions",
            now()->addMinutes(60),
            fn() => $this->role?->permissions()
                        ->get(['module', 'action'])
                        ->groupBy('module')
                        ->map(fn($rows) => $rows->pluck('action')->all())
                        ->all() ?? []
        );

        return in_array($action, $permissions[$module] ?? [], true);
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

    /**
     * La tabla users no tiene columna `name` (usa first_name / last_name), pero varias vistas leen
     * `$user->name` (responsables de negocios, metas, secuencias...). Se expone como nombre completo
     * para que esas vistas muestren el nombre en vez de quedar vacias. Solo lectura: no sirve para consultas SQL.
     */
    public function getNameAttribute(): string
    {
        return trim($this->full_name);
    }
}
