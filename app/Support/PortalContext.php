<?php

namespace App\Support;

use App\Models\CustomerPortalUser;

/**
 * Quién está usando el portal /customer: el titular de la cuenta (Customer) o un
 * usuario de su equipo con un rol (CustomerPortalUser). El guard `customer` siempre
 * autentica al titular; si entró un usuario del equipo, su id va en
 * session('portal_member_id') (lo escribe App\Services\Portal\PortalMemberAuth).
 *
 * Contrato que usan los demás módulos del portal:
 *   PortalContext::can('water-analysis', 'create')  => bool
 *   PortalContext::memberId()                       => ?int (null = titular)
 */
class PortalContext
{
    /** ID del usuario del equipo en sesión, o null si quien entró es el titular. */
    public static function memberId(): ?int
    {
        $id = session('portal_member_id');

        return $id ? (int) $id : null;
    }

    public static function isOwner(): bool
    {
        return self::memberId() === null;
    }

    /**
     * Usuario del equipo en sesión, o null (titular, o sesión inconsistente: el
     * usuario no existe o no pertenece a la cuenta autenticada). Se guarda en el
     * request para consultar la BD una sola vez por petición.
     */
    public static function member(): ?CustomerPortalUser
    {
        $id = self::memberId();
        if ($id === null) {
            return null;
        }

        $bag = app('request')->attributes;
        $cached = $bag->get('portal_member');
        if (is_array($cached) && $cached['id'] === $id) {
            return $cached['member'];
        }

        $member = CustomerPortalUser::with('role.permissions')->find($id);
        $ownerId = auth('customer')->id();
        if ($member && (! $ownerId || (int) $member->customer_id !== (int) $ownerId)) {
            $member = null;
        }

        $bag->set('portal_member', ['id' => $id, 'member' => $member]);
        $bag->remove('portal_permissions');

        return $member;
    }

    /** Descarta lo cacheado en el request (tras editar un rol o usuario). */
    public static function flush(): void
    {
        $bag = app('request')->attributes;
        $bag->remove('portal_member');
        $bag->remove('portal_permissions');
    }

    public static function can(string $module, string $action = 'view'): bool
    {
        if (self::memberId() === null) {
            return true; // titular de la cuenta: acceso total
        }

        $def = config('portal_modules.' . $module);
        if (! $def || ! empty($def['owner_only'])) {
            return false; // desconocido o exclusivo del titular
        }

        $member = self::member();
        if (! $member || ! $member->isActive() || ! $member->role) {
            return false;
        }

        $bag = app('request')->attributes;
        $perms = $bag->get('portal_permissions');
        if (! is_array($perms) || $perms['member'] !== $member->id) {
            $perms = [
                'member' => $member->id,
                'set'    => array_fill_keys($member->role->permissionKeys(), true),
            ];
            $bag->set('portal_permissions', $perms);
        }

        return isset($perms['set'][$module . ':' . $action]);
    }

    /** Nombre a mostrar de quien está usando el portal. */
    public static function displayName(): string
    {
        $member = self::member();
        if ($member) {
            return $member->full_name;
        }

        $customer = auth('customer')->user();

        return $customer ? trim($customer->first_name . ' ' . $customer->last_name) : '';
    }

    public static function displayEmail(): string
    {
        $member = self::member();
        if ($member) {
            return $member->email;
        }

        return (string) auth('customer')->user()?->email;
    }

    /** Catálogo de módulos que se pueden asignar a un rol (sin los exclusivos del titular). */
    public static function assignableModules(): array
    {
        return collect(config('portal_modules', []))
            ->reject(fn ($m) => ! empty($m['owner_only']))
            ->all();
    }
}
