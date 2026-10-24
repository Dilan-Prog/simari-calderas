<?php

namespace Tests\Feature\Notifications;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Notif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class NotificationTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notif::flush();
    }

    /** Usuario activo; $perms = módulos notif-* (acción view) que su rol tiene. Sin rol admin. */
    protected function user(array $perms = [], string $name = 'Usuario', string $status = 'active'): User
    {
        $role = Role::create(['name_role' => 'Rol ' . uniqid(), 'name_role_es' => 'Rol']);
        if ($perms) {
            $role->permissions()->attach(Permission::whereIn('module', $perms)->where('action', 'view')->pluck('id'));
        }

        return User::create([
            'first_name' => $name, 'last_name' => 'Prueba', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => $status,
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    protected function admin(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    protected function copy(User $from, User $to): void
    {
        $from->notificationCopies()->attach($to->id);
    }
}
