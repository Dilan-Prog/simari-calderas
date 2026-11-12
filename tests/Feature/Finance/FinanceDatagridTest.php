<?php

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los listados de Finanzas (Cuentas por cobrar/pagar, Pagos, Órdenes de compra) usan la tabla de datos
 * estilo hoja de cálculo (misma vista que Ventas > Listado de Productos): el servidor marca la tabla y sus
 * valores, y erp-datagrid.js agrega filtros, orden, totales y columnas. Aquí se comprueba el contrato del HTML
 * y que se conserven las funciones de cada pantalla.
 */
class FinanceDatagridTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private FinanceService $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
        $this->finance = app(FinanceService::class);
    }

    private function customer(string $company): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'test', 'company' => $company,
        ]);
    }

    public function test_cuentas_por_cobrar_marca_la_tabla_sus_columnas_y_valores_y_conserva_las_acciones(): void
    {
        $c = $this->customer('Cliente Uno SA');
        $doc = $this->finance->createReceivable([
            'customer_id' => $c->id, 'issue_date' => now()->toDateString(), 'credit_days' => 30, 'currency' => 'USD',
            'exchange_rate' => 17.5, 'subtotal' => 1000, 'tax_total' => 160,
        ]);

        $html = $this->actingAs($this->admin)->get(route('admin.receivables.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-dg="finanzas-receivable"', $html);
        $this->assertStringContainsString('data-dg-noun="cuentas por cobrar"', $html);
        // Columnas y tipos
        foreach (['folio' => 'text', 'party' => 'list', 'issue' => 'date', 'due' => 'date', 'total' => 'num', 'paid' => 'num', 'balance' => 'num', 'status' => 'list'] as $k => $t) {
            $this->assertStringContainsString('data-dg-key="' . $k . '" data-dg-type="' . $t . '"', $html, "columna $k");
        }
        $this->assertSame(3, substr_count($html, 'data-dg-sum>'));
        // Fila con valores limpios (sin símbolos) y moneda para el Σ
        $this->assertStringContainsString('data-dg-row', $html);
        $this->assertStringContainsString('data-dg-v="' . $doc->number . '"', $html);
        $this->assertStringContainsString('data-dg-v="Cliente Uno SA"', $html);
        $this->assertStringContainsString('data-dg-v="1160.00" data-dg-cur="USD"', $html);
        // Se conservan las funciones: selección en lote, estado de cuenta, KPIs, exportar y paginación/pie
        $this->assertStringContainsString('data-erp-select-all', $html);
        $this->assertStringContainsString('class="erp-row-check"', $html);
        $this->assertStringContainsString(route('admin.receivables.statement', $c->id), $html);
        $this->assertStringContainsString(route('admin.receivables.export'), $html);
        $this->assertStringContainsString('Mostrando 1–1 de 1 cuentas por cobrar', $html);
        $this->assertStringContainsString('Flujo próximos 30 días', $html);
    }

    public function test_cuentas_por_pagar_usa_la_misma_tabla_con_su_proveedor(): void
    {
        $s = Supplier::create(['company_name' => 'Proveedor Uno', 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
        $this->finance->createPayable(['supplier_id' => $s->id, 'issue_date' => now()->toDateString(), 'credit_days' => 5, 'currency' => 'MXN', 'subtotal' => 500, 'tax_total' => 80]);

        $html = $this->actingAs($this->admin)->get(route('admin.payables.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-dg="finanzas-payable"', $html);
        $this->assertStringContainsString('data-dg-v="Proveedor Uno"', $html);
        $this->assertStringContainsString('data-dg-v="580.00" data-dg-cur="MXN"', $html);
    }

    public function test_pagos_marca_la_tabla_y_conserva_la_seleccion_en_lote(): void
    {
        $c = $this->customer('Cliente Pago SA');
        $this->finance->registerPayment(['direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(), 'amount' => 1500, 'currency' => 'MXN']);

        $html = $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-dg="finanzas-pagos"', $html);
        foreach (['folio' => 'text', 'type' => 'list', 'party' => 'list', 'date' => 'date', 'method' => 'list', 'amount' => 'num', 'free' => 'num', 'status' => 'list'] as $k => $t) {
            $this->assertStringContainsString('data-dg-key="' . $k . '" data-dg-type="' . $t . '"', $html, "columna $k");
        }
        $this->assertStringContainsString('data-dg-v="Recibido"', $html);
        $this->assertStringContainsString('data-dg-v="1500.00" data-dg-cur="MXN"', $html);
        $this->assertStringContainsString('data-erp-select-all', $html);
        $this->assertStringContainsString('data-fin-batch-patch', $html);
    }

    public function test_ordenes_de_compra_tiene_una_sola_tabla_con_su_cabecera_buscador_y_acciones(): void
    {
        $s = Supplier::create(['company_name' => 'Proveedor OC', 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
        $po = PurchaseOrder::create(['po_number' => 'PO-9001', 'supplier_id' => $s->id, 'created_by_user_id' => $this->admin->id, 'status' => 'accepted', 'order_date' => now(), 'currency' => 'MXN', 'exchange_rate' => 1, 'subtotal' => 100, 'total' => 116]);

        $html = $this->actingAs($this->admin)->get(route('admin.purchase-orders.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-dg="finanzas-ordenes-compra"', $html);
        $this->assertStringContainsString('data-dg-search="#poSearch"', $html);
        $this->assertStringContainsString('id="poSearch"', $html);
        $this->assertStringNotContainsString('brand-table', $html, 'la cabecera ya vive dentro de la misma tabla');
        $this->assertStringContainsString('data-dg-v="PO-9001"', $html);
        $this->assertStringContainsString('data-dg-v="116.00" data-dg-cur="MXN"', $html);
        $this->assertStringContainsString('data-dg-v="PROVEEDOR OC"', $html);
        // Se conservan las acciones de cada fila y el modal de eliminar
        $this->assertStringContainsString(route('admin.purchase-orders.show', $po->id), $html);
        $this->assertStringContainsString(route('admin.purchase-orders.edit', $po->id), $html);
        $this->assertStringContainsString(route('admin.purchase-orders.pdf', $po->id), $html);
        $this->assertStringContainsString('btn-delete-po', $html);
        $this->assertStringContainsString('id="deletePoModal"', $html);
    }

    public function test_las_pantallas_sin_datos_siguen_mostrando_su_mensaje_vacio(): void
    {
        $this->actingAs($this->admin)->get(route('admin.receivables.index'))->assertOk()->assertSee('No hay cuentas por cobrar con estos filtros');
        $this->actingAs($this->admin)->get(route('admin.payments.index'))->assertOk()->assertSee('No hay pagos con estos filtros');
    }
}
