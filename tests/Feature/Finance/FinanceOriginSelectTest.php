<?php

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payable;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Receivable;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\FinanceOriginOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuentas por cobrar / por pagar: el documento de origen, el proyecto y la orden de compra se eligen de un
 * select en lugar de teclear su número interno.
 */
class FinanceOriginSelectTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
    }

    private function customer(string $company): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'test', 'company' => $company,
        ]);
    }

    private function salesOrder(Customer $c, string $number): SalesOrder
    {
        return SalesOrder::create(['order_number' => $number, 'customer_id' => $c->id, 'status' => 'pendiente']);
    }

    private function project(Customer $c, string $number, string $name): Project
    {
        return Project::create(['project_number' => $number, 'name' => $name, 'customer_id' => $c->id, 'status' => 'levantamiento']);
    }

    private function payload(Customer $c, array $extra = []): array
    {
        return $extra + [
            'customer_id' => $c->id, 'issue_date' => now()->toDateString(), 'credit_days' => 15,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ];
    }

    public function test_el_formulario_muestra_selects_con_los_documentos_agrupados_y_sin_ids_numericos(): void
    {
        $c = $this->customer('Cliente Uno');
        $this->salesOrder($c, 'PED-0001');
        $this->project($c, 'PRY-0001', 'Caldera nueva');

        $html = $this->actingAs($this->admin)->get(route('admin.receivables.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="source_document"', $html);
        $this->assertStringContainsString('name="project_id"', $html);
        $this->assertStringContainsString('<optgroup label="Pedidos">', $html);
        $this->assertStringContainsString('PED-0001 · Cliente Uno', $html);
        $this->assertStringContainsString('PRY-0001 · Caldera nueva', $html);
        $this->assertStringNotContainsString('name="source_id"', $html);
        $this->assertStringNotContainsString('ID del origen', $html);
        $this->assertStringNotContainsString('ID de proyecto', $html);
    }

    public function test_crear_con_un_documento_elegido_guarda_tipo_id_y_proyecto(): void
    {
        $c = $this->customer('Cliente Uno');
        $order = $this->salesOrder($c, 'PED-0002');
        $project = $this->project($c, 'PRY-0002', 'Proyecto X');

        $this->actingAs($this->admin)->post(route('admin.receivables.store'), $this->payload($c, [
            'source_document' => 'sales_order:' . $order->id, 'project_id' => $project->id,
        ]))->assertRedirect();

        $doc = Receivable::firstOrFail();
        $this->assertSame('sales_order', $doc->source_type);
        $this->assertSame($order->id, (int) $doc->source_id);
        $this->assertSame($project->id, (int) $doc->project_id);
        $this->assertSame($order->id, $doc->source()->id);
    }

    public function test_sin_documento_queda_manual_y_uno_inexistente_da_error(): void
    {
        $c = $this->customer('Cliente Uno');

        $this->actingAs($this->admin)->post(route('admin.receivables.store'), $this->payload($c, ['source_document' => '']))->assertRedirect();
        $doc = Receivable::firstOrFail();
        $this->assertNull($doc->source_type);
        $this->assertNull($doc->source_id);

        $this->actingAs($this->admin)->post(route('admin.receivables.store'), $this->payload($c, ['source_document' => 'quote:99999']))
            ->assertSessionHasErrors('source_document');
        $this->actingAs($this->admin)->post(route('admin.receivables.store'), $this->payload($c, ['source_document' => 'cualquier-cosa:5']))
            ->assertSessionHasErrors('source_document');
        $this->assertSame(1, Receivable::count());
    }

    public function test_editar_preselecciona_el_documento_y_se_puede_quitar(): void
    {
        $c = $this->customer('Cliente Uno');
        $order = $this->salesOrder($c, 'PED-0003');
        $this->actingAs($this->admin)->post(route('admin.receivables.store'), $this->payload($c, ['source_document' => 'sales_order:' . $order->id]));
        $doc = Receivable::firstOrFail();

        $html = $this->actingAs($this->admin)->get(route('admin.receivables.edit', $doc->id))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="sales_order:' . $order->id . '"[^>]*selected[^>]*>PED-0003/', $html);

        $this->actingAs($this->admin)->put(route('admin.receivables.update', $doc->id), $this->payload($c, ['source_document' => '']))->assertRedirect();
        $doc->refresh();
        $this->assertNull($doc->source_type);
        $this->assertNull($doc->source_id);
    }

    public function test_el_documento_ligado_aparece_aunque_no_este_entre_los_mas_recientes(): void
    {
        $c = $this->customer('Cliente Uno');
        $old = $this->salesOrder($c, 'PED-VIEJO');
        $doc = Receivable::create([
            'number' => 'CXC-T-1', 'customer_id' => $c->id, 'source_type' => 'sales_order', 'source_id' => $old->id,
            'issue_date' => now(), 'due_date' => now()->addDays(30), 'credit_days' => 30, 'currency' => 'MXN', 'exchange_rate' => 1,
            'subtotal' => 100, 'tax_total' => 0, 'total' => 100, 'balance' => 100, 'status' => 'pendiente',
        ]);

        $groups = FinanceOriginOptions::receivableOrigins($doc);
        $this->assertContains('sales_order:' . $old->id, array_column($groups['sales_order']['items'], 'value'));
    }

    public function test_cuentas_por_pagar_elige_la_orden_de_compra_de_un_select(): void
    {
        $s = Supplier::create(['company_name' => 'Proveedor Uno', 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
        $po = PurchaseOrder::create(['po_number' => 'PO-0001', 'supplier_id' => $s->id, 'created_by_user_id' => $this->admin->id, 'status' => 'accepted', 'order_date' => now(), 'currency' => 'MXN', 'exchange_rate' => 1, 'subtotal' => 100, 'total' => 116]);

        $html = $this->actingAs($this->admin)->get(route('admin.payables.create'))->assertOk()->getContent();
        $this->assertStringContainsString('<select name="purchase_order_id"', $html);
        $this->assertStringContainsString('PO-0001 · Proveedor Uno · $116.00 MXN', $html);
        $this->assertStringNotContainsString('Orden de compra (ID)', $html);

        $this->actingAs($this->admin)->post(route('admin.payables.store'), [
            'supplier_id' => $s->id, 'purchase_order_id' => $po->id, 'issue_date' => now()->toDateString(),
            'credit_days' => 10, 'currency' => 'MXN', 'subtotal' => 100, 'tax_total' => 16,
        ])->assertRedirect();

        $this->assertSame($po->id, (int) Payable::firstOrFail()->purchase_order_id);
    }
}
