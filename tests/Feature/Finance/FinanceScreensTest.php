<?php

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payable;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Interfaz de Finanzas: panel (FIN1), listados, objeto y conciliación (FIN5).
 */
class FinanceScreensTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'test', 'company' => $company,
        ]);
    }

    public function test_panel_de_finanzas_muestra_kpis_antiguedad_y_listas(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $finance = app(FinanceService::class);
        $c = $this->customer('Cliente Moroso SA');
        $finance->createReceivable([
            'customer_id' => $c->id, 'issue_date' => now()->subDays(80)->toDateString(), 'credit_days' => 30,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ]);
        $s = Supplier::create(['company_name' => 'Proveedor Urgente', 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
        $finance->createPayable([
            'supplier_id' => $s->id, 'issue_date' => now()->toDateString(), 'credit_days' => 5,
            'currency' => 'MXN', 'subtotal' => 500, 'tax_total' => 80,
        ]);

        $this->actingAs($admin)->get(route('admin.finance.dashboard'))
            ->assertOk()
            ->assertSee('Panel de finanzas')
            ->assertSee('Antigüedad de saldos por cobrar')
            ->assertSee('Cliente Moroso SA')
            ->assertSee('Proveedor Urgente')
            ->assertSee('$1,160.00');
    }

    public function test_panel_requiere_algun_permiso_financiero(): void
    {
        $this->withoutVite();
        $role = Role::create(['name_role' => 'Vendedor', 'name_role_es' => 'Vendedor']);
        $user = User::create([
            'first_name' => 'Sin', 'last_name' => 'Permiso', 'position' => 'Vendedor', 'phone' => '5555555555',
            'email' => 'sp-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);

        $this->actingAs($user)->get(route('admin.finance.dashboard'))->assertStatus(403);
    }

    public function test_conciliacion_lista_pagos_aplicados_y_permite_conciliar_y_desconciliar(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $finance = app(FinanceService::class);
        $c = $this->customer();
        $doc = $finance->createReceivable([
            'customer_id' => $c->id, 'issue_date' => now()->toDateString(), 'credit_days' => 30,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ]);
        $applied = $finance->registerPayment(['direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(), 'amount' => 500, 'currency' => 'MXN']);
        $finance->applyPayment($applied, [['id' => $doc->id, 'amount' => 500]]);
        $unapplied = $finance->registerPayment(['direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(), 'amount' => 90, 'currency' => 'MXN']);

        $this->actingAs($admin)->get(route('admin.payments.reconciliation'))
            ->assertOk()
            ->assertSee('Conciliación')
            ->assertSee($applied->number)
            ->assertSee($doc->number)
            ->assertDontSee($unapplied->number);

        $this->actingAs($admin)->patch(route('admin.payments.reconcile', $applied->id))->assertSessionHas('success');
        $this->actingAs($admin)->get(route('admin.payments.reconciliation', ['view' => 'pendientes']))->assertOk()->assertDontSee($applied->number);
        $this->actingAs($admin)->get(route('admin.payments.reconciliation', ['view' => 'conciliados']))->assertOk()->assertSee($applied->number);

        $this->actingAs($admin)->patch(route('admin.payments.unreconcile', $applied->id))->assertSessionHas('success');
        $this->assertSame('aplicado', $applied->fresh()->status);
        $this->actingAs($admin)->get(route('admin.payments.reconciliation'))->assertOk()->assertSee($applied->number);
    }

    public function test_listados_con_vistas_y_filtros_responden(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $finance = app(FinanceService::class);
        $s = Supplier::create(['company_name' => 'Prov SA', 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
        $payable = $finance->createPayable([
            'supplier_id' => $s->id, 'issue_date' => now()->toDateString(), 'credit_days' => 30,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ]);

        foreach (['abiertas', 'vencida', 'pagada'] as $status) {
            $this->actingAs($admin)->get(route('admin.payables.index', ['status' => $status]))->assertOk()->assertSee('Vistas');
        }
        $this->actingAs($admin)->get(route('admin.payables.index'))->assertOk()->assertSee($payable->number)->assertSee('Cuentas por pagar');
        $this->actingAs($admin)->get(route('admin.payables.show', $payable->id))->assertOk()->assertSee('Procure-to-Pay');
        $this->actingAs($admin)->get(route('admin.payments.index', ['direction' => 'out']))->assertOk()->assertSee('Por conciliar');
        $this->assertInstanceOf(Payable::class, $payable);
    }
}
