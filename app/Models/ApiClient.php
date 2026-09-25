<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

/**
 * Cuenta de servicio para integraciones externas (N8N, etc.). NO extiende
 * la clase base User del panel -- no inicia sesión web, solo emite tokens
 * Sanctum con abilities (ver config/api_abilities.php). Middleware de
 * referencia: App\Http\Middleware\EnsureTokenAbility, que exige que
 * $request->user() sea instancia de ESTE modelo (no un User humano) antes
 * de revisar la ability del token -- mantiene separados el sistema de
 * permisos de panel (roles/permissions) y el de tokens de API.
 *
 * Implementa Authenticatable (vía el trait estándar de Laravel, no el de
 * User) porque el guard 'sanctum' lo exige explícitamente para cualquier
 * modelo tokenable: en un request real Sanctum resuelve el usuario sin
 * pasar por setUser() con type-check estricto, pero el helper de test
 * Sanctum::actingAs() sí llama Auth::guard('sanctum')->setUser($user), que
 * tiene el parámetro tipado a Authenticatable -- sin esto, cualquier test
 * que use ese helper con un ApiClient truena con TypeError. Los métodos que
 * aporta el trait (getAuthPassword()/remember token) no se usan nunca en la
 * práctica -- este modelo no tiene columnas password/remember_token y solo
 * se autentica por token Sanctum.
 */
class ApiClient extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
