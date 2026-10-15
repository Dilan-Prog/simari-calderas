<?php

namespace Tests\Feature\ServiceMigration;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Products;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\ServicePage;
use App\Models\ServiceReport;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\TechnicalService;
use App\Models\User;
use ReflectionProperty;

/** Utilidades compartidas por las pruebas de la migración a Servicio ID. */
trait ServiceMigrationFixtures
{
    protected function resetSettingCache(): void
    {
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
    }

    private function makeUser(Role $role): User
    {
        return User::create([
            'first_name' => 'U', 'last_name' => 'Test', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => strtoupper(substr(md5(uniqid('', true)), 0, 13)), 'role_id' => $role->id,
        ]);
    }

    protected function adminUser(): User
    {
        return $this->makeUser(Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']));
    }

    /** Usuario con rol que solo tiene los permisos dados: [['quotes','view'], ...]. */
    protected function userWithPermissions(array $perms): User
    {
        $role = Role::create(['name_role' => 'R-' . uniqid(), 'name_role_es' => 'Rol']);
        foreach ($perms as [$module, $action]) {
            $perm = Permission::firstOrCreate(
                ['code' => "{$module}.{$action}"],
                ['name' => "{$module} {$action}", 'module' => $module, 'action' => $action]
            );
            $role->permissions()->attach($perm->id);
        }

        return $this->makeUser($role);
    }

    protected function makeServicePage(string $name = 'Mantenimiento preventivo de calderas', array $over = []): ServicePage
    {
        return ServicePage::create(array_merge([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'page_type' => 'service', 'is_active' => true,
        ], $over));
    }

    protected function makeCategory(string $name, ?int $parentId = null): Category
    {
        return Category::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'is_active' => true, 'parent_id' => $parentId]);
    }

    protected function makeProduct(string $name, ?Category $category = null, array $over = []): Products
    {
        return Products::create(array_merge([
            'category_id' => $category?->id ?? $this->makeCategory('Refacciones ' . uniqid())->id,
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'sku' => 'SKU-' . uniqid(), 'price' => 1000, 'stock' => 5, 'is_active' => true,
        ], $over));
    }

    protected function makeQuote(array $over = []): Quote
    {
        $createdAt = $over['created_at'] ?? null;
        unset($over['created_at']);

        $quote = Quote::create(array_merge([
            'quote_number' => 'COT-' . uniqid(), 'status' => 'accepted', 'created_by_user_id' => User::first()->id,
            'guest_name' => 'Cliente Prueba', 'guest_email' => 'c@example.com', 'currency' => 'MXN',
            'subtotal' => 100, 'discount_total' => 0, 'tax_rate' => 16, 'tax_total' => 16, 'total' => 116,
        ], $over));

        if ($createdAt) {
            \Illuminate\Support\Facades\DB::table('quotes')->where('id', $quote->id)->update(['created_at' => $createdAt]);
            $quote = $quote->fresh();
        }

        return $quote;
    }

    protected function addLine(Quote $quote, string $name, ?Products $product = null, array $over = []): QuoteItem
    {
        return QuoteItem::create(array_merge([
            'quote_id' => $quote->id, 'product_id' => $product?->id, 'product_name' => $name,
            'product_sku' => $product?->sku, 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100,
            'sort_order' => $quote->items()->count(),
        ], $over));
    }

    protected function makeCustomer(): Customer
    {
        return Customer::first() ?? Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Diaz', 'email' => 'ana-' . uniqid() . '@example.com', 'phone' => '1', 'password_hash' => 'x',
            'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01', 'status' => 'active', 'source' => 'web', 'company' => 'ACME',
        ]);
    }

    protected function makeTechnicalService(array $over = []): TechnicalService
    {
        static $n = 0;
        $n++;
        $type = ServiceType::first();
        if (!$type) {
            $type = new ServiceType(['name' => 'Instalación', 'is_active' => true]);
            $type->timestamps = false;
            $type->save();
        }

        return TechnicalService::create(array_merge([
            'service_number' => 'SERV-T-' . $n . '-' . substr(uniqid(), -5), 'service_code' => 'SC-' . $n . '-' . substr(uniqid(), -5),
            'customer_id' => $this->makeCustomer()->id, 'service_type_id' => $type->id,
            'status' => 'scheduled', 'service_date' => now()->addDays(3)->toDateString(),
        ], $over));
    }

    protected function makeReport(User $by, array $over = []): ServiceReport
    {
        static $n = 0;
        $n++;

        return ServiceReport::create(array_merge([
            'report_number' => 'RS-M-' . $n . substr(uniqid(), -6),
            'created_by_user_id' => $by->id, 'assigned_user_id' => $by->id,
            'customer_name' => 'Planta Norte', 'service_type' => 'maintenance_preventive', 'status' => 'draft',
            'service_date' => now()->toDateString(), 'current_step' => 1,
        ], $over));
    }
}
