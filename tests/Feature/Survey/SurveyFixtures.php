<?php

namespace Tests\Feature\Survey;

use App\Models\Customer;
use App\Models\Role;
use App\Models\ServicePage;
use App\Models\ServiceReport;
use App\Models\Setting;
use App\Models\User;
use ReflectionProperty;

/** Utilidades compartidas por las pruebas de "Califica este servicio". */
trait SurveyFixtures
{
    protected function resetSettingCache(): void
    {
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
    }

    protected function adminUser(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return $this->makeUser($role);
    }

    /** Usuario con rol sin permisos. */
    protected function plainUser(): User
    {
        $role = Role::create(['name_role' => 'Sin permisos', 'name_role_es' => 'Sin permisos']);

        return $this->makeUser($role);
    }

    private function makeUser(Role $role): User
    {
        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    protected function makeCustomer(string $company = 'ACME', bool $portal = true): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'test', 'company' => $company, 'portal_access' => $portal,
        ]);
    }

    protected function makeReport(User $by, ?Customer $customer = null, array $over = []): ServiceReport
    {
        static $n = 0;
        $n++;

        return ServiceReport::create(array_merge([
            'report_number' => 'RS-T-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) . substr(uniqid(), -6),
            'created_by_user_id' => $by->id, 'assigned_user_id' => $by->id,
            'customer_id' => $customer?->id, 'customer_name' => 'Planta Norte S.A.',
            'service_type' => 'maintenance_preventive', 'status' => 'signed',
            'service_date' => now()->toDateString(), 'current_step' => 6, 'signed_at' => now(),
            'signature_data' => 'data:image/png;base64,AAAA', 'signature_name' => 'Firmante', 'signature_position' => 'Jefe',
        ], $over));
    }

    protected function makeServicePage(string $name = 'Mantenimiento de calderas'): ServicePage
    {
        return ServicePage::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'page_type' => 'service', 'is_active' => true]);
    }
}
