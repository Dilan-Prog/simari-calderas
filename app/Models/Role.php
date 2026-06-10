<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name_role',
        'name_role_es',
        'description_role',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;


    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public function hasPermission(string $module): bool
    {
        return $this->permissions()
            ->where('module', $module)
            ->exists();
    }

    // Verifica si es administrador
    public function isAdmin(): bool
    {
        return strtolower($this->name_role) === 'administrador'
            || strtolower($this->name_role) === 'admin';
    }

    // Accessor para obtener el nombre limpio
    public function getNameAttribute(): string
    {
        return $this->name_role;
    }

    // Accessor para obtener la descripción limpia
    public function getDescriptionAttribute(): string
    {
        return $this->description_role ?? '';
    }
}
