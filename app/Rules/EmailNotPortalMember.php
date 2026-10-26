<?php

namespace App\Rules;

use App\Models\CustomerPortalUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El correo de un Customer (titular) no puede ser el de un usuario del equipo del portal
 * (customer_portal_users): el inicio de sesión del portal resuelve a una sola persona por correo.
 * El portal ya bloquea el sentido contrario (un usuario del equipo con correo de un titular).
 * Comparación sin distinguir mayúsculas, igual que se guardan los correos (minúsculas).
 */
class EmailNotPortalMember implements ValidationRule
{
    public const MESSAGE = 'Este correo ya pertenece a un usuario del portal de un cliente. Usa otro correo.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::taken($value)) {
            $fail(self::MESSAGE);
        }
    }

    /** ¿Hay un usuario del equipo del portal con este correo? */
    public static function taken(mixed $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && CustomerPortalUser::whereRaw('LOWER(email) = ?', [$email])->exists();
    }
}
