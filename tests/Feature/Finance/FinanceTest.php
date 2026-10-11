<?php

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\PaymentApplication;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Services\FinanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3 — Finanzas: CXC, CXP, pagos y aplicaciones.
 */
class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private FinanceService $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finance = app(FinanceService::class);
    }

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

    private function supplier(string $name = 'Proveedor SA'): Supplier
    {
        return Supplier::create(['company_name' => $name, 'status' => 'active', 'payment_terms' => 'Crédito 30 días']);
    }

    private function receivable(Customer $c, array $o = []): Receivable
    {
        return $this->finance->createReceivable(array_merge([
            'customer_id' => $c->id, 'issue_date' => now()->toDateString(), 'credit_days' => 30,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ], $o));
    }

    private function payable(Supplier $s, array $o = []): Payable
    {
        return $this->finance->createPayable(array_merge([
            'supplier_id' => $s->id, 'issue_date' => now()->toDateString(), 'credit_days' => 30,
            'currency' => 'MXN', 'subtotal' => 1000, 'tax_total' => 160,
        ], $o));
    }

    private function incoming(Customer $c, float $amount, array $o = []): Payment
    {
        return $this->finance->registerPayment(array_merge([
            'direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(),
            'amount' => $amount, 'currency' => 'MXN',
        ], $o));
    }

    public function test_crea_cxc_con_folio_total_y_vencimiento(): void
    {
        $r = $this->receivable($this->customer());

        $this->assertMatchesRegularExpression('/^CXC-' . date('Y') . '-0001$/', $r->number);
        $this->assertEquals(1160.00, (float) $r->total);
        $this->assertEquals(1160.00, (float) $r->balance);
        $this->assertSame('pendiente', $r->status);
        $this->assertSame(now()->addDays(30)->toDateString(), $r->due_date->toDateString());
        $this->assertSame('CXC-' . date('Y') . '-0002', $this->receivable($this->customer())->number);
    }

    public function test_aplicacion_parcial_y_luego_total(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c);
        $p = $this->incoming($c, 1160);

        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 400]]);
        $r->refresh();
        $this->assertSame('parcial', $r->status);
        $this->assertEquals(400.00, (float) $r->paid_amount);
        $this->assertEquals(760.00, (float) $r->balance);
        $this->assertSame('aplicado', $p->fresh()->status);
        $this->assertEquals(760.00, $p->fresh()->unappliedAmount());

        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 760]]);
        $r->refresh();
        $this->assertSame('pagada', $r->status);
        $this->assertEquals(0.00, (float) $r->balance);
        $this->assertEquals(0.00, $p->fresh()->unappliedAmount());
        // Una sola fila de aplicación por (pago, documento): se acumula.
        $this->assertSame(1, PaymentApplication::count());
    }

    public function test_no_permite_exceder_saldo_ni_monto_del_pago(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c);

        $p = $this->incoming($c, 5000);
        try {
            $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 1200]]);
            $this->fail('Debió rechazar el exceso de saldo');
        } catch (DomainException $e) {
            $this->assertStringContainsString('excede su saldo', $e->getMessage());
        }

        $chico = $this->incoming($c, 500);
        try {
            $this->finance->applyPayment($chico, [['id' => $r->id, 'amount' => 800]]);
            $this->fail('Debió rechazar el exceso del pago');
        } catch (DomainException $e) {
            $this->assertStringContainsString('exceden el monto disponible', $e->getMessage());
        }

        $this->assertEquals(0.00, (float) $r->fresh()->paid_amount);
        $this->assertSame(0, PaymentApplication::count());
    }

    public function test_no_aplica_a_documento_de_otra_contraparte(): void
    {
        $r = $this->receivable($this->customer('Uno'));
        $p = $this->incoming($this->customer('Dos'), 1000);

        $this->expectException(DomainException::class);
        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 100]]);
    }

    public function test_multimoneda_pago_mxn_a_documento_usd_y_viceversa(): void
    {
        $c = $this->customer();
        // Documento de 100 USD (sin IVA) a TC 18.
        $usd = $this->receivable($c, ['currency' => 'USD', 'exchange_rate' => 18, 'subtotal' => 100, 'tax_total' => 0]);
        $pagoMxn = $this->incoming($c, 900); // alcanza para 50 USD
        $this->finance->applyPayment($pagoMxn, [['id' => $usd->id, 'amount' => 50]]);

        $usd->refresh();
        $this->assertEquals(50.00, (float) $usd->paid_amount);
        $this->assertEquals(50.00, (float) $usd->balance);
        $this->assertSame('parcial', $usd->status);
        $this->assertEquals(900.00, (float) $pagoMxn->fresh()->applied_amount);

        // Aplicar de más: 50 USD = 900 MXN, pero el pago ya está agotado.
        try {
            $this->finance->applyPayment($pagoMxn, [['id' => $usd->id, 'amount' => 1]]);
            $this->fail('Debió rechazar: pago agotado');
        } catch (DomainException $e) {
            $this->assertStringContainsString('exceden el monto disponible', $e->getMessage());
        }

        // Pago en USD (TC 17) contra documento MXN de 1160: 40 USD = 680 MXN.
        $mxn = $this->receivable($c);
        $pagoUsd = $this->incoming($c, 40, ['currency' => 'USD', 'exchange_rate' => 17]);
        $this->finance->applyPayment($pagoUsd, [['id' => $mxn->id, 'amount' => 680]]);
        $this->assertEquals(680.00, (float) $mxn->fresh()->paid_amount);
        $this->assertEquals(40.00, (float) $pagoUsd->fresh()->applied_amount);
        $this->assertSame('aplicado', $pagoUsd->fresh()->status);
    }

    public function test_usd_requiere_tipo_de_cambio(): void
    {
        $this->expectException(DomainException::class);
        $this->receivable($this->customer(), ['currency' => 'USD', 'exchange_rate' => null]);
    }

    public function test_vencimiento_al_crear_y_por_comando(): void
    {
        $c = $this->customer();
        $vencida = $this->receivable($c, ['issue_date' => now()->subDays(60)->toDateString()]);
        $this->assertSame('vencida', $vencida->status);

        // Vigente que "envejece": se simula moviendo su fecha al pasado.
        $r = $this->receivable($c);
        $this->assertSame('pendiente', $r->status);
        Receivable::whereKey($r->id)->update(['due_date' => now()->subDays(3)->toDateString()]);

        $this->artisan('finance:mark-overdue')->assertSuccessful();
        $this->assertSame('vencida', $r->fresh()->status);

        // Un pago total sobre una vencida la deja pagada.
        $p = $this->incoming($c, 1160);
        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 1160]]);
        $this->assertSame('pagada', $r->fresh()->status);
    }

    public function test_pago_parcial_sobre_vencida_conserva_vencida_y_reversa_la_restaura(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c, ['issue_date' => now()->subDays(60)->toDateString()]);
        $p = $this->incoming($c, 100);
        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 100]]);
        $this->assertSame('vencida', $r->fresh()->status);
        $this->assertEquals(1060.00, (float) $r->fresh()->balance);

        $app = PaymentApplication::first();
        $this->finance->unapply($app);
        $this->assertEquals(1160.00, (float) $r->fresh()->balance);
        $this->assertEquals(0.00, (float) $r->fresh()->paid_amount);
        $this->assertSame('pendiente', $p->fresh()->status);
    }

    public function test_cancelar_pago_revierte_todas_las_aplicaciones(): void
    {
        $c = $this->customer();
        $a = $this->receivable($c);
        $b = $this->receivable($c);
        $p = $this->incoming($c, 2320);
        $this->finance->applyPayment($p, [['id' => $a->id, 'amount' => 1160], ['id' => $b->id, 'amount' => 1160]]);
        $this->assertSame('pagada', $a->fresh()->status);
        $this->assertSame('pagada', $b->fresh()->status);

        $this->finance->cancelPayment($p);

        $this->assertSame('cancelado', $p->fresh()->status);
        $this->assertSame(0, PaymentApplication::count());
        foreach ([$a, $b] as $doc) {
            $this->assertSame('pendiente', $doc->fresh()->status);
            $this->assertEquals(1160.00, (float) $doc->fresh()->balance);
        }
        // Un pago cancelado ya no aplica.
        $this->expectException(DomainException::class);
        $this->finance->applyPayment($p->fresh(), [['id' => $a->id, 'amount' => 10]]);
    }

    public function test_conciliacion_bloquea_cambios_hasta_quitarla(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c);

        $sinAplicar = $this->incoming($c, 100);
        try {
            $this->finance->reconcile($sinAplicar);
            $this->fail('No debe conciliar un pago sin aplicar');
        } catch (DomainException $e) {
            $this->assertStringContainsString('aplicado', $e->getMessage());
        }

        $p = $this->incoming($c, 500);
        $this->finance->applyPayment($p, [['id' => $r->id, 'amount' => 500]]);
        $this->finance->reconcile($p);
        $this->assertSame('conciliado', $p->fresh()->status);
        $this->assertNotNull($p->fresh()->reconciled_at);

        try {
            $this->finance->unapply(PaymentApplication::first());
            $this->fail('No debe desaplicar un pago conciliado');
        } catch (DomainException $e) {
            $this->assertStringContainsString('conciliado', $e->getMessage());
        }

        $this->finance->unreconcile($p);
        $this->finance->unapply(PaymentApplication::first());
        $this->assertSame('pendiente', $p->fresh()->status);
    }

    public function test_fifo_distribuye_primero_al_vencimiento_mas_antiguo(): void
    {
        $c = $this->customer();
        $nueva = $this->receivable($c, ['credit_days' => 60]);
        $vieja = $this->receivable($c, ['credit_days' => 5]);
        $p = $this->incoming($c, 1500);

        $this->finance->applyPayment($p, $this->finance->fifoAllocations($p));

        $this->assertSame('pagada', $vieja->fresh()->status);
        $this->assertEquals(340.00, (float) $nueva->fresh()->paid_amount);
        $this->assertSame('parcial', $nueva->fresh()->status);
        $this->assertEquals(0.00, $p->fresh()->unappliedAmount());
    }

    public function test_antiguedad_de_saldos_por_cliente(): void
    {
        $c = $this->customer('ACME');
        $this->receivable($c, ['subtotal' => 100, 'tax_total' => 0]);                                                   // vigente
        $this->receivable($c, ['subtotal' => 200, 'tax_total' => 0, 'issue_date' => now()->subDays(45)->toDateString()]); // 15 d vencido -> d30
        $this->receivable($c, ['subtotal' => 300, 'tax_total' => 0, 'issue_date' => now()->subDays(80)->toDateString()]); // 50 d -> d60
        $this->receivable($c, ['subtotal' => 400, 'tax_total' => 0, 'issue_date' => now()->subDays(110)->toDateString()]); // 80 d -> d90
        $this->receivable($c, ['subtotal' => 500, 'tax_total' => 0, 'issue_date' => now()->subDays(200)->toDateString()]); // 170 d -> d90p
        $pagada = $this->receivable($c, ['subtotal' => 999, 'tax_total' => 0]);
        $this->finance->applyPayment($this->incoming($c, 999), [['id' => $pagada->id, 'amount' => 999]]);
        // Documento en USD cuenta en MXN: 10 USD a 20 = 200 MXN, vigente.
        $this->receivable($this->customer('Otro'), ['currency' => 'USD', 'exchange_rate' => 20, 'subtotal' => 10, 'tax_total' => 0]);

        $report = $this->finance->agingReport('receivable');
        $this->assertCount(2, $report['rows']);
        $row = collect($report['rows'])->firstWhere('party', 'ACME');
        $this->assertEquals(100.0, $row['current']);
        $this->assertEquals(200.0, $row['d30']);
        $this->assertEquals(300.0, $row['d60']);
        $this->assertEquals(400.0, $row['d90']);
        $this->assertEquals(500.0, $row['d90p']);
        $this->assertEquals(1500.0, $row['total']);
        $this->assertEquals(1700.0, $report['totals']['total']);
        $this->assertEquals(300.0, $report['totals']['current']);
    }

    public function test_estado_de_cuenta_con_saldo_corrido_por_moneda(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c, ['subtotal' => 1000, 'tax_total' => 0]);
        $this->finance->applyPayment($this->incoming($c, 300), [['id' => $r->id, 'amount' => 300]]);
        $usd = $this->receivable($c, ['currency' => 'USD', 'exchange_rate' => 18, 'subtotal' => 50, 'tax_total' => 0]);

        $st = $this->finance->statement('receivable', $c->id);
        $this->assertEquals(700.0, $st['MXN']['closing']);
        $this->assertEquals(1000.0, $st['MXN']['charges']);
        $this->assertEquals(300.0, $st['MXN']['credits']);
        $this->assertCount(2, $st['MXN']['lines']);
        $this->assertEquals(50.0, $st['USD']['closing']);
        $this->assertNotNull($usd);
    }

    public function test_cxc_desde_pedido_usa_totales_de_cotizacion_y_enlaza_el_flujo(): void
    {
        $c = $this->customer();
        $quote = Quote::create([
            'quote_number' => 'COT-TEST-0001', 'guest_name' => 'Invitado', 'guest_email' => 'g@example.com', 'guest_phone' => '555', 'created_by_user_id' => $this->adminUser()->id, 'customer_id' => $c->id, 'status' => 'accepted', 'currency' => 'USD',
            'exchange_rate' => 18.5, 'subtotal' => 1000, 'discount_total' => 0, 'tax_rate' => 16, 'tax_total' => 160, 'total' => 1160,
        ]);
        $order = SalesOrder::create([
            'order_number' => SalesOrder::generateOrderNumber(), 'quote_id' => $quote->id, 'customer_id' => $c->id, 'status' => 'pendiente',
        ]);

        $r = $this->finance->createReceivableFromSalesOrder($order, 45);

        $this->assertSame('sales_order', $r->source_type);
        $this->assertSame($order->id, (int) $r->source_id);
        $this->assertSame('USD', $r->currency);
        $this->assertEquals(18.5, (float) $r->exchange_rate);
        $this->assertEquals(1160.00, (float) $r->total);
        $this->assertSame(45, $r->credit_days);
        $this->assertSame(now()->addDays(45)->toDateString(), $r->due_date->toDateString());

        $nodes = collect(DocumentFlow::flow($order))->pluck('type')->all();
        $this->assertContains('receivable', $nodes);

        // Un solo CXC activo por pedido.
        $this->expectException(DomainException::class);
        $this->finance->createReceivableFromSalesOrder($order);
    }

    public function test_cxp_desde_oc_aceptada_toma_dias_de_credito_del_proveedor(): void
    {
        $admin = $this->adminUser();
        $s = $this->supplier();
        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . date('Y') . '-0001', 'supplier_id' => $s->id, 'created_by_user_id' => $admin->id,
            'status' => 'pending', 'order_date' => now()->toDateString(), 'currency' => 'MXN',
            'subtotal' => 2000, 'tax_total' => 320, 'total' => 2320,
        ]);

        try {
            $this->finance->createPayableFromPurchaseOrder($po);
            $this->fail('Una OC pendiente no genera CXP');
        } catch (DomainException $e) {
            $this->assertStringContainsString('aceptada', $e->getMessage());
        }

        $po->update(['status' => 'accepted']);
        $cxp = $this->finance->createPayableFromPurchaseOrder($po);

        $this->assertMatchesRegularExpression('/^CXP-\d{4}-0001$/', $cxp->number);
        $this->assertSame($po->id, (int) $cxp->purchase_order_id);
        $this->assertSame(30, $cxp->credit_days);
        $this->assertEquals(2320.00, (float) $cxp->total);
        $this->assertContains('payable', collect(DocumentFlow::flow($po))->pluck('type')->all());

        // Pago a proveedor y enlace CXP -> pago.
        $pago = $this->finance->registerPayment([
            'direction' => 'out', 'supplier_id' => $s->id, 'payment_date' => now()->toDateString(), 'amount' => 2320, 'currency' => 'MXN',
        ], null, [['id' => $cxp->id, 'amount' => 2320]]);
        $this->assertSame('pagada', $cxp->fresh()->status);
        $this->assertContains('payment', collect(DocumentFlow::flow($cxp))->pluck('type')->all());
        $this->assertMatchesRegularExpression('/^PAG-\d{4}-0001$/', $pago->number);
    }

    public function test_cancelar_documento_con_pagos_aplicados_se_rechaza(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c);
        $this->finance->applyPayment($this->incoming($c, 100), [['id' => $r->id, 'amount' => 100]]);

        $this->expectException(DomainException::class);
        $this->finance->cancelDocument($r);
    }

    public function test_editar_total_no_puede_quedar_debajo_de_lo_pagado(): void
    {
        $c = $this->customer();
        $r = $this->receivable($c);
        $this->finance->applyPayment($this->incoming($c, 500), [['id' => $r->id, 'amount' => 500]]);

        $updated = $this->finance->updateDocument($r, ['subtotal' => 2000, 'tax_total' => 0]);
        $this->assertEquals(2000.00, (float) $updated->total);
        $this->assertEquals(1500.00, (float) $updated->balance);

        $this->expectException(DomainException::class);
        $this->finance->updateDocument($r->fresh(), ['subtotal' => 100, 'tax_total' => 0]);
    }

    // ── HTTP ───────────────────────────────────────────────────────────────

    public function test_pantallas_responden_y_flujo_http_de_cobro(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $c = $this->customer();
        $r = $this->receivable($c);

        $this->actingAs($admin)->get(route('admin.receivables.index'))->assertOk()->assertSee($r->number);
        $this->actingAs($admin)->get(route('admin.receivables.index', ['status' => 'abiertas', 'customer_id' => $c->id]))->assertOk();
        $this->actingAs($admin)->get(route('admin.receivables.aging'))->assertOk()->assertSee('Antigüedad');
        $this->actingAs($admin)->get(route('admin.receivables.statement', $c->id))->assertOk()->assertSee('Estado de cuenta');
        $this->actingAs($admin)->get(route('admin.receivables.show', $r->id))->assertOk()->assertSee($r->number);
        $this->actingAs($admin)->get(route('admin.receivables.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.receivables.edit', $r->id))->assertOk();
        $this->actingAs($admin)->get(route('admin.payments.create', ['direction' => 'in']))->assertOk();
        $this->actingAs($admin)->get(route('admin.payables.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payables.aging'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payables.create'))->assertOk();

        $csv = $this->actingAs($admin)->get(route('admin.receivables.export'));
        $csv->assertOk();
        $this->assertStringContainsString($r->number, $csv->streamedContent());

        $this->actingAs($admin)->getJson(route('admin.payments.open-documents', ['direction' => 'in', 'party' => $c->id]))
            ->assertOk()->assertJsonPath('0.number', $r->number);

        $this->actingAs($admin)->post(route('admin.payments.store'), [
            'direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(),
            'amount' => 1000, 'currency' => 'MXN', 'reference' => 'SPEI-1', 'alloc' => [$r->id => 600],
        ])->assertRedirect();

        $p = Payment::first();
        $this->assertSame('aplicado', $p->status);
        $this->assertEquals(560.00, (float) $r->fresh()->balance);
        $this->actingAs($admin)->get(route('admin.payments.show', $p->id))->assertOk()->assertSee($p->number);
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee($p->number);
        $this->actingAs($admin)->get(route('admin.receivables.show', $r->id))->assertOk()->assertSee($p->number);

        // Excede el saldo restante -> error, sin crear otro pago.
        $this->actingAs($admin)->post(route('admin.payments.store'), [
            'direction' => 'in', 'customer_id' => $c->id, 'payment_date' => now()->toDateString(),
            'amount' => 5000, 'currency' => 'MXN', 'alloc' => [$r->id => 2000],
        ])->assertSessionHas('error');
        $this->assertSame(1, Payment::count());

        // FIFO sobre el saldo libre del primer pago.
        $this->actingAs($admin)->post(route('admin.payments.apply', $p->id), ['fifo' => 1])->assertSessionHas('success');
        $this->assertSame('aplicado', $p->fresh()->status);
        $this->assertEquals(1000.00, (float) $r->fresh()->paid_amount);
        $this->assertEquals(160.00, (float) $r->fresh()->balance);
        $this->assertEquals(0.00, $p->fresh()->unappliedAmount());

        $this->actingAs($admin)->patch(route('admin.payments.reconcile', $p->id))->assertSessionHas('success');
        $this->assertSame('conciliado', $p->fresh()->status);
    }

    public function test_hooks_de_pedido_y_oc_crean_documentos(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $c = $this->customer();
        $quote = Quote::create([
            'quote_number' => 'COT-TEST-0002', 'guest_name' => 'Invitado', 'guest_email' => 'g@example.com', 'guest_phone' => '555', 'created_by_user_id' => $admin->id, 'customer_id' => $c->id, 'status' => 'accepted', 'currency' => 'MXN',
            'exchange_rate' => 1, 'subtotal' => 500, 'tax_rate' => 16, 'tax_total' => 80, 'total' => 580,
        ]);
        $order = SalesOrder::create([
            'order_number' => SalesOrder::generateOrderNumber(), 'quote_id' => $quote->id, 'customer_id' => $c->id, 'status' => 'pendiente',
        ]);

        $this->actingAs($admin)->get(route('admin.sales-orders.show', $order->id))->assertOk()->assertSee('Generar cuenta por cobrar');
        $this->actingAs($admin)->post(route('admin.receivables.from-sales-order', $order->id), ['credit_days' => 15])->assertRedirect();
        $this->assertSame(1, Receivable::count());
        $this->actingAs($admin)->get(route('admin.sales-orders.show', $order->id))->assertOk()->assertDontSee('Generar cuenta por cobrar');

        $s = $this->supplier();
        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . date('Y') . '-0009', 'supplier_id' => $s->id, 'created_by_user_id' => $admin->id,
            'status' => 'accepted', 'order_date' => now()->toDateString(), 'currency' => 'MXN',
            'subtotal' => 100, 'tax_total' => 16, 'total' => 116,
        ]);
        $this->actingAs($admin)->get(route('admin.purchase-orders.show', $po->id))->assertOk()->assertSee('Generar cuenta por pagar');
        $this->actingAs($admin)->post(route('admin.payables.from-purchase-order', $po->id))->assertRedirect();
        $this->assertSame(1, Payable::count());
    }
}
