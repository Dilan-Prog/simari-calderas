<?php

namespace Tests\Feature\CustomerPortal;

use App\Mail\PortalStaffAlertMail;
use App\Models\AdminNotification;
use App\Models\Customer;
use App\Models\CustomerPurchaseOrder;
use App\Models\Permission;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Services\Portal\PortalStaffAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Avisos al personal por eventos del portal: tarea ligada al cliente + correo a administración
 * al subir una orden de compra o registrar una lectura de agua fuera de rango.
 */
class PortalStaffAlertsTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    private const TO = 'administracion@equitermindustries.com.mx';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function customer(string $company = 'ACME Calderas'): Customer
    {
        return Customer::create([
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'email' => 'c-' . uniqid() . '@example.com',
            'phone' => '5555555555', 'password_hash' => bcrypt('secret'), 'document_type' => 'RFC',
            'document_numer' => 'XAXX010101000', 'birth_date' => '1990-01-01', 'status' => 'active',
            'source' => 'admin', 'company' => $company, 'rfc' => 'XAXX010101000', 'portal_access' => true,
        ]);
    }

    private function user(string $roleName = 'Administrador', array $perms = []): User
    {
        $role = Role::firstOrCreate(['name_role' => $roleName], ['name_role_es' => $roleName]);
        if ($perms) {
            $role->permissions()->syncWithoutDetaching(Permission::whereIn('module', $perms)->where('action', 'view')->pluck('id'));
        }

        return User::create([
            'first_name' => 'Staff', 'last_name' => 'Test', 'position' => $roleName, 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => bcrypt('password'),
            'status' => 'active', 'rfc' => 'XAXX' . random_int(100000, 999999) . 'AB1', 'role_id' => $role->id,
        ]);
    }

    private function quote(Customer $customer): Quote
    {
        return Quote::create([
            'quote_number' => 'COT-' . uniqid(), 'created_by_user_id' => $this->user()->id,
            'customer_id' => $customer->id, 'status' => 'accepted', 'guest_name' => 'Juan Pérez',
            'currency' => 'MXN', 'subtotal' => 100, 'discount_total' => 0, 'tax_rate' => 16,
            'tax_total' => 16, 'total' => 116,
        ]);
    }

    private function upload(Customer $customer, array $extra = [])
    {
        return $this->actingAs($customer, 'customer')->post(route('customer.purchase-orders.store'), $extra + [
            'file'      => UploadedFile::fake()->createWithContent('oc-778.pdf', self::PDF),
            'po_number' => 'OC-778',
            'po_date'   => now()->toDateString(),
            'notes'     => 'Entrega en planta',
        ]);
    }

    private function param(string $code): WaterAnalysisParameter
    {
        return WaterAnalysisParameter::where('code', $code)->firstOrFail();
    }

    private function setRange(string $code, ?float $min, ?float $max): void
    {
        $this->param($code)->update(['min_value' => $min, 'max_value' => $max]);
    }

    private function saveReading(Customer $customer, array $byCode, ?string $date = null)
    {
        $values = [];
        foreach ($byCode as $code => $v) {
            $values[$this->param($code)->id] = $v;
        }

        return $this->actingAs($customer, 'customer')->post(route('customer.water-analysis.store'), [
            'report_date' => $date ?? now()->toDateString(),
            'values'      => $values,
        ]);
    }

    // ── Orden de compra ─────────────────────────────────────────────────────

    public function test_subir_una_oc_crea_una_tarea_y_envia_un_correo_con_el_pdf(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $quote = $this->quote($customer);

        $this->upload($customer, ['quote_id' => $quote->id])
            ->assertRedirect(route('customer.purchase-orders.index'))
            ->assertSessionHas('success');

        $po = CustomerPurchaseOrder::firstOrFail();

        $this->assertSame(1, Task::count());
        $task = Task::firstOrFail();
        $this->assertSame(Customer::class, $task->taskable_type);
        $this->assertSame($customer->id, (int) $task->taskable_id);
        $this->assertSame('Orden de compra recibida: ACME Calderas OC-778', $task->title);
        $this->assertSame('open', $task->status);
        $this->assertSame('correo', $task->type);
        $this->assertSame('media', $task->priority);
        $this->assertNull($task->assigned_to);
        $this->assertNull($task->related_reference);
        $this->assertEqualsWithDelta(now()->addDay()->timestamp, $task->due_at->timestamp, 120);
        $this->assertStringContainsString('Confirma la recepción', $task->description);
        $this->assertStringContainsString($quote->quote_number, $task->description);
        $this->assertStringContainsString('Entrega en planta', $task->description);
        $this->assertStringContainsString(route('admin.customer-purchase-orders.download', $po->id), $task->description);

        Mail::assertSent(PortalStaffAlertMail::class, 1);
        Mail::assertSent(PortalStaffAlertMail::class, function (PortalStaffAlertMail $m) use ($po, $customer, $quote) {
            $this->assertTrue($m->hasTo(self::TO));
            $this->assertStringStartsWith('[Portal Equiterm]', $m->subjectLine);
            $this->assertStringContainsString('OC-778', $m->subjectLine);
            $this->assertSame('ACME Calderas', $m->rows['Empresa']);
            $this->assertSame($customer->email, $m->rows['Correo del cliente']);
            $this->assertStringContainsString($quote->quote_number, $m->rows['Cotización ligada']);
            $this->assertSame(route('admin.customer-purchase-orders.download', $po->id), $m->actions['Descargar PDF']);
            $this->assertStringContainsString('/clientes/informacion/' . $customer->id, $m->actions['Ver ficha del cliente']);

            $this->assertCount(1, $m->attachments());
            $this->assertSame($po->file_path, $m->attachment['path']);
            $this->assertSame('oc-778.pdf', $m->attachment['name']);
            $this->assertTrue($m->hasAttachment(
                \Illuminate\Mail\Mailables\Attachment::fromStorageDisk('local', $po->file_path)->as('oc-778.pdf')->withMime('application/pdf')
            ));

            return true;
        });
    }

    public function test_el_correo_se_renderiza_con_los_datos_del_cliente(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $this->upload($customer)->assertSessionHas('success');

        Mail::assertSent(PortalStaffAlertMail::class, function (PortalStaffAlertMail $m) use ($customer) {
            $html = $m->render();
            $this->assertStringContainsString('Orden de compra recibida', $html);
            $this->assertStringContainsString('ACME Calderas', $html);
            $this->assertStringContainsString($customer->email, $html);

            return true;
        });
    }

    public function test_la_direccion_destino_sale_de_la_configuracion_y_su_default_es_administracion(): void
    {
        $this->assertSame(self::TO, config('portal.alert_email'));

        Mail::fake();
        config(['portal.alert_email' => 'otro@example.com']);
        $this->upload($this->customer())->assertSessionHas('success');

        Mail::assertSent(PortalStaffAlertMail::class, fn ($m) => $m->hasTo('otro@example.com') && ! $m->hasTo(self::TO));
    }

    public function test_se_notifica_en_el_panel_a_quien_tiene_el_permiso_notif_tarea(): void
    {
        Mail::fake();
        $with = $this->user('Con aviso', ['notif-tarea']);
        $without = $this->user('Sin aviso');

        $this->upload($this->customer())->assertSessionHas('success');

        $task = Task::firstOrFail();
        $this->assertSame(1, AdminNotification::where('user_id', $with->id)->where('type', 'task')->count());
        $this->assertSame(0, AdminNotification::where('user_id', $without->id)->count());
        $n = AdminNotification::where('user_id', $with->id)->first();
        $this->assertSame('Tarea nueva', $n->title);
        $this->assertSame($task->title, $n->body);
    }

    public function test_un_fallo_del_correo_no_rompe_la_subida(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $customer = $this->customer();
        $this->upload($customer)
            ->assertRedirect(route('customer.purchase-orders.index'))
            ->assertSessionHas('success', 'Recibimos tu orden de compra; nuestro equipo la revisará.');

        $po = CustomerPurchaseOrder::firstOrFail();
        Storage::disk('local')->assertExists($po->file_path);
        // La tarea sí queda creada aunque el correo falle.
        $this->assertSame(1, Task::count());
    }

    public function test_el_pdf_no_se_adjunta_si_pesa_mas_de_10_mb(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $path = 'customer-purchase-orders/' . $customer->id . '/grande.pdf';
        Storage::disk('local')->put($path, self::PDF . str_repeat('0', 10 * 1024 * 1024));
        $po = CustomerPurchaseOrder::create([
            'customer_id' => $customer->id, 'po_number' => 'OC-GRANDE', 'file_path' => $path,
            'original_name' => 'grande.pdf', 'mime' => 'application/pdf', 'size' => 10 * 1024 * 1024 + 10,
        ]);

        app(PortalStaffAlerts::class)->purchaseOrderUploaded($po);

        Mail::assertSent(PortalStaffAlertMail::class, fn ($m) => $m->attachment === null && $m->attachments() === []);
        $this->assertSame(1, Task::count());
    }

    // ── Análisis químico fuera de rango ─────────────────────────────────────

    public function test_lectura_fuera_de_rango_crea_tarea_alta_y_correo(): void
    {
        Mail::fake();
        $this->setRange('ph', 6.5, 8.5);
        $customer = $this->customer();

        $this->saveReading($customer, ['ph' => '9.2', 'hardness' => '100'])
            ->assertRedirect(route('customer.water-analysis.index'))
            ->assertSessionHas('success');

        $date = now()->toDateString();
        $task = Task::firstOrFail();
        $this->assertSame(Customer::class, $task->taskable_type);
        $this->assertSame($customer->id, (int) $task->taskable_id);
        $this->assertSame('alta', $task->priority);
        $this->assertSame('open', $task->status);
        $this->assertNull($task->assigned_to);
        $this->assertSame("water-analysis:{$customer->id}:{$date}", $task->related_reference);
        $this->assertSame('Lectura fuera de rango: ACME Calderas (' . now()->format('d/m/Y') . ')', $task->title);
        $this->assertEqualsWithDelta(now()->addDay()->timestamp, $task->due_at->timestamp, 120);
        $this->assertStringContainsString('9.20', $task->description);
        $this->assertStringContainsString('alto', $task->description);
        $this->assertStringContainsString('6.50 a 8.50', $task->description);
        // La dureza no tiene rango: no se menciona.
        $this->assertStringNotContainsString('Dureza', $task->description);

        Mail::assertSent(PortalStaffAlertMail::class, 1);
        Mail::assertSent(PortalStaffAlertMail::class, function (PortalStaffAlertMail $m) {
            return $m->hasTo(self::TO)
                && str_starts_with($m->subjectLine, '[Portal Equiterm] Lectura fuera de rango')
                && $m->attachment === null;
        });
    }

    public function test_sin_rangos_cargados_no_hay_alerta(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->saveReading($customer, ['ph' => '13', 'tds' => '99999'])->assertSessionHas('success');

        $this->assertSame(0, Task::count());
        Mail::assertNothingSent();
    }

    public function test_una_lectura_dentro_de_rango_no_genera_alerta(): void
    {
        Mail::fake();
        $this->setRange('ph', 6.5, 8.5);
        $this->setRange('tds', null, 500);

        $this->saveReading($this->customer(), ['ph' => '7.2', 'tds' => '300'])->assertSessionHas('success');

        $this->assertSame(0, Task::count());
        Mail::assertNothingSent();
    }

    public function test_guardar_otra_vez_el_mismo_dia_no_duplica_la_alerta(): void
    {
        Mail::fake();
        $this->setRange('ph', 6.5, 8.5);
        $this->setRange('tds', null, 500);
        $customer = $this->customer();

        $this->saveReading($customer, ['ph' => '9']);
        $this->saveReading($customer, ['ph' => '9.4']);            // sigue fuera de rango
        $this->saveReading($customer, ['ph' => '9.4', 'tds' => '900']); // otro parámetro fuera, mismo día

        $this->assertSame(1, Task::count());
        Mail::assertSent(PortalStaffAlertMail::class, 1);

        // Otro día sí genera su propia alerta.
        $this->saveReading($customer, ['ph' => '9'], now()->subDay()->toDateString());
        $this->assertSame(2, Task::count());
        Mail::assertSent(PortalStaffAlertMail::class, 2);
    }

    public function test_no_se_repite_si_la_tarea_ya_se_cerro_o_se_elimino(): void
    {
        Mail::fake();
        $this->setRange('ph', 6.5, 8.5);
        $customer = $this->customer();

        $this->saveReading($customer, ['ph' => '9']);
        Task::firstOrFail()->delete(); // borrado lógico
        $this->saveReading($customer, ['ph' => '9.5']);

        $this->assertSame(0, Task::count());
        $this->assertSame(1, Task::withTrashed()->count());
        Mail::assertSent(PortalStaffAlertMail::class, 1);
    }

    public function test_corregir_el_dia_a_dentro_de_rango_no_crea_nada_y_actualizar_por_put_avisa_una_vez(): void
    {
        Mail::fake();
        $this->setRange('ph', 6.5, 8.5);
        $customer = $this->customer();

        // Primer guardado en rango y corrección posterior (PUT) fuera de rango: avisa una vez.
        $this->saveReading($customer, ['ph' => '7']);
        $this->assertSame(0, Task::count());

        $report = WaterAnalysisReport::firstOrFail();
        $this->actingAs($customer, 'customer')->put(route('customer.water-analysis.update', $report->id), [
            'values' => [$this->param('ph')->id => '5.9'],
        ])->assertSessionHas('success');
        $this->assertSame(1, Task::count());

        // Corregido de nuevo a dentro de rango: no se toca la alerta existente ni se envía otra.
        $this->actingAs($customer, 'customer')->put(route('customer.water-analysis.update', $report->id), [
            'values' => [$this->param('ph')->id => '7.1'],
        ])->assertSessionHas('success');
        $this->assertSame(1, Task::count());
        Mail::assertSent(PortalStaffAlertMail::class, 1);
    }

    public function test_un_fallo_del_correo_no_rompe_el_guardado_del_analisis(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));
        $this->setRange('ph', 6.5, 8.5);
        $customer = $this->customer();

        $this->saveReading($customer, ['ph' => '9'])
            ->assertRedirect(route('customer.water-analysis.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, WaterAnalysisReport::count());
        $this->assertSame(1, Task::count());
    }

    // ── Pantallas de tareas ─────────────────────────────────────────────────

    public function test_las_pantallas_de_tareas_muestran_una_tarea_ligada_a_un_cliente(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $this->upload($customer)->assertSessionHas('success');
        $task = Task::firstOrFail();
        $admin = $this->user('Administrador');

        $this->assertSame('Cliente Juan Pérez · ACME Calderas', $task->taskableLabel());
        $this->assertSame(route('admin.clients.information', $customer->id), $task->taskableUrl());
        $this->assertSame([
            'name' => 'Juan Pérez', 'phone' => '5555555555', 'email' => $customer->email,
            'company' => 'ACME Calderas', 'rfc' => 'XAXX010101000',
        ], $task->taskableCustomerInfo());

        $this->actingAs($admin)->get(route('admin.tasks.show', $task))
            ->assertOk()
            ->assertSee('Orden de compra recibida: ACME Calderas OC-778')
            ->assertSee('Cliente Juan Pérez')
            ->assertSee(route('admin.clients.information', $customer->id), false);

        $this->actingAs($admin)->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertSee('Orden de compra recibida')
            ->assertSee('Cliente Juan Pérez · ACME Calderas');

        // También la alerta de análisis (sin tipo de tarea).
        $this->setRange('ph', 6.5, 8.5);
        $this->saveReading($customer, ['ph' => '9']);
        $analysis = Task::where('related_reference', 'like', 'water-analysis:%')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.tasks.show', $analysis))->assertOk()->assertSee('Lectura fuera de rango');
        $this->actingAs($admin)->get(route('admin.tasks.index', ['view' => 'all']))->assertOk();
    }
}
