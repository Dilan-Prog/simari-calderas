<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Models\CustomerPortalRolePermission;
use App\Models\CustomerPortalUser;
use App\Models\CustomerPurchaseOrder;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Órdenes de Compra del portal: el cliente sube el PDF de su OC (disco privado), solo ve las suyas,
 * y el personal las descarga con permission:clients,view.
 */
class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'email' => 'c-' . uniqid() . '@example.com',
            'phone' => '5555555555', 'password_hash' => bcrypt('secret'), 'document_type' => 'RFC',
            'document_numer' => 'XAXX010101000', 'birth_date' => '1990-01-01', 'status' => 'active',
            'source' => 'admin', 'company' => $company, 'portal_access' => true,
        ]);
    }

    private function staff(string $roleName = 'Administrador'): User
    {
        $role = Role::firstOrCreate(['name_role' => $roleName], ['name_role_es' => $roleName]);

        return User::create([
            'first_name' => 'Staff', 'last_name' => 'Test', 'position' => $roleName, 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => bcrypt('password'),
            'status' => 'active', 'rfc' => 'XAXX' . random_int(100000, 999999) . 'AB1', 'role_id' => $role->id,
        ]);
    }

    private function quote(Customer $customer, string $status = 'sent', string $number = null): Quote
    {
        return Quote::create([
            'quote_number' => $number ?? 'COT-' . uniqid(), 'created_by_user_id' => $this->staff()->id,
            'customer_id' => $customer->id, 'status' => $status, 'guest_name' => 'Juan Pérez',
            'currency' => 'MXN', 'subtotal' => 100, 'discount_total' => 0, 'tax_rate' => 16,
            'tax_total' => 16, 'total' => 116,
        ]);
    }

    private function pdf(string $name = 'oc-123.pdf', ?string $content = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content ?? self::PDF);
    }

    private function stored(Customer $customer, array $extra = []): CustomerPurchaseOrder
    {
        $path = 'customer-purchase-orders/' . $customer->id . '/' . uniqid() . '.pdf';
        Storage::disk('local')->put($path, self::PDF);

        return CustomerPurchaseOrder::create($extra + [
            'customer_id' => $customer->id, 'po_number' => 'OC-1', 'file_path' => $path,
            'original_name' => 'oc-1.pdf', 'mime' => 'application/pdf', 'size' => strlen(self::PDF),
        ]);
    }

    public function test_sube_un_pdf_valido_y_guarda_fila_y_archivo_privado(): void
    {
        $customer = $this->customer();
        $quote = $this->quote($customer, 'accepted');

        $this->actingAs($customer, 'customer')
            ->post(route('customer.purchase-orders.store'), [
                'file' => $this->pdf('Mi OC 2026.pdf'), 'po_number' => ' OC-778 ', 'po_date' => now()->toDateString(),
                'quote_id' => $quote->id, 'notes' => 'Entrega en planta',
            ])
            ->assertRedirect(route('customer.purchase-orders.index'))
            ->assertSessionHas('success');

        $po = CustomerPurchaseOrder::firstOrFail();
        $this->assertSame($customer->id, $po->customer_id);
        $this->assertSame('OC-778', $po->po_number);
        $this->assertSame($quote->id, $po->quote_id);
        $this->assertSame('Mi OC 2026.pdf', $po->original_name);
        $this->assertSame('application/pdf', $po->mime);
        $this->assertNull($po->uploaded_by_member_id);
        $this->assertStringStartsWith('customer-purchase-orders/' . $customer->id . '/', $po->file_path);
        Storage::disk('local')->assertExists($po->file_path);
        $this->assertNotSame('Mi OC 2026.pdf', basename($po->file_path)); // nombre aleatorio en disco
    }

    public function test_rechaza_archivos_que_no_son_pdf(): void
    {
        $customer = $this->customer();

        // Extensión equivocada.
        $this->actingAs($customer, 'customer')
            ->post(route('customer.purchase-orders.store'), ['file' => UploadedFile::fake()->image('foto.png')])
            ->assertSessionHasErrors('file');

        // Un .txt cualquiera.
        $this->post(route('customer.purchase-orders.store'), ['file' => UploadedFile::fake()->createWithContent('notas.txt', 'hola')])
            ->assertSessionHasErrors('file');

        // Un .exe (cabecera MZ) renombrado .pdf.
        $this->post(route('customer.purchase-orders.store'), ['file' => $this->pdf('virus.pdf', "MZ\x90\x00\x03\x00\x00\x00 programa")])
            ->assertSessionHasErrors('file');

        // Texto plano renombrado .pdf.
        $this->post(route('customer.purchase-orders.store'), ['file' => $this->pdf('falso.pdf', 'esto no es un pdf')])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, CustomerPurchaseOrder::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_rechaza_pdf_de_mas_de_10_mb_y_requiere_archivo(): void
    {
        $customer = $this->customer();

        $big = UploadedFile::fake()->createWithContent('grande.pdf', self::PDF . str_repeat('0', 10 * 1024 * 1024));
        $this->actingAs($customer, 'customer')
            ->post(route('customer.purchase-orders.store'), ['file' => $big])
            ->assertSessionHasErrors('file');

        $this->post(route('customer.purchase-orders.store'), ['po_number' => 'X'])->assertSessionHasErrors('file');

        $this->assertSame(0, CustomerPurchaseOrder::count());
    }

    public function test_no_acepta_ligar_cotizacion_de_otro_cliente_ni_un_borrador(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $foreign = $this->quote($other);
        $draft = $this->quote($customer, 'draft');

        foreach ([$foreign, $draft] as $q) {
            $this->actingAs($customer, 'customer')
                ->post(route('customer.purchase-orders.store'), ['file' => $this->pdf(), 'quote_id' => $q->id])
                ->assertSessionHasErrors('quote_id');
        }

        $this->assertSame(0, CustomerPurchaseOrder::count());
    }

    public function test_el_listado_solo_muestra_las_ordenes_propias_y_busca_por_folio(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $this->stored($customer, ['po_number' => 'MIA-001', 'original_name' => 'mia.pdf']);
        $this->stored($customer, ['po_number' => 'MIA-002', 'original_name' => 'otra-mia.pdf']);
        $this->stored($other, ['po_number' => 'AJENA-999', 'original_name' => 'ajena.pdf']);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.purchase-orders.index'))
            ->assertOk()
            ->assertSee('MIA-001')->assertSee('MIA-002')
            ->assertDontSee('AJENA-999')->assertDontSee('ajena.pdf');

        $this->get(route('customer.purchase-orders.index', ['q' => 'MIA-002']))
            ->assertOk()->assertSee('MIA-002')->assertDontSee('MIA-001');

        $this->get(route('customer.purchase-orders.index', ['q' => 'AJENA']))
            ->assertOk()->assertDontSee('AJENA-999');
    }

    public function test_el_selector_de_cotizaciones_solo_lista_las_propias_enviadas_o_aceptadas(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $this->quote($customer, 'sent', 'COT-PROPIA-S');
        $this->quote($customer, 'draft', 'COT-PROPIA-BORRADOR');
        $this->quote($other, 'sent', 'COT-AJENA');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.purchase-orders.index'))
            ->assertOk()->assertSee('COT-PROPIA-S')
            ->assertDontSee('COT-PROPIA-BORRADOR')->assertDontSee('COT-AJENA');
    }

    public function test_descarga_propia_hace_stream_inline_y_ajena_es_404(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $mine = $this->stored($customer, ['original_name' => 'oc-mia.pdf']);
        $theirs = $this->stored($other, ['original_name' => 'oc-ajena.pdf']);

        $res = $this->actingAs($customer, 'customer')->get(route('customer.purchase-orders.download', $mine->id));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('oc-mia.pdf', $res->headers->get('Content-Disposition'));

        $res = $this->get(route('customer.purchase-orders.download', $mine->id) . '?download=1');
        $res->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));

        $this->get(route('customer.purchase-orders.download', $theirs->id))->assertNotFound();
        $this->get(route('customer.purchase-orders.download', 'abc'))->assertNotFound();
    }

    public function test_eliminar_borra_fila_y_archivo_y_no_toca_las_ajenas(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $mine = $this->stored($customer);
        $theirs = $this->stored($other);

        $this->actingAs($customer, 'customer')
            ->delete(route('customer.purchase-orders.destroy', $theirs->id))
            ->assertNotFound();
        $this->assertDatabaseHas('customer_purchase_orders', ['id' => $theirs->id]);
        Storage::disk('local')->assertExists($theirs->file_path);

        $this->delete(route('customer.purchase-orders.destroy', $mine->id))
            ->assertRedirect(route('customer.purchase-orders.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('customer_purchase_orders', ['id' => $mine->id]);
        Storage::disk('local')->assertMissing($mine->file_path);
    }

    public function test_un_usuario_del_equipo_sin_permiso_no_puede_ver_subir_descargar_ni_eliminar(): void
    {
        $customer = $this->customer();
        $po = $this->stored($customer);
        $role = CustomerPortalRole::create(['customer_id' => $customer->id, 'name' => 'Sin OC']);
        $member = $this->member($customer, $role);
        $this->actingAs($customer, 'customer')->withSession(['portal_member_id' => $member->id]);

        $this->get(route('customer.purchase-orders.index'))->assertRedirect(route('customer.dashboard'));
        $this->post(route('customer.purchase-orders.store'), ['file' => $this->pdf()])->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.purchase-orders.download', $po->id))->assertRedirect(route('customer.dashboard'));
        $this->delete(route('customer.purchase-orders.destroy', $po->id))->assertRedirect(route('customer.dashboard'));

        $this->assertSame(1, CustomerPurchaseOrder::count());
        Storage::disk('local')->assertExists($po->file_path);
    }

    public function test_una_sesion_de_equipo_inconsistente_no_accede(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer')->withSession(['portal_member_id' => 999999]);

        $this->get(route('customer.purchase-orders.index'))->assertRedirect();
        $this->post(route('customer.purchase-orders.store'), ['file' => $this->pdf()])->assertRedirect();
        $this->assertSame(0, CustomerPurchaseOrder::count());
    }

    public function test_un_usuario_del_equipo_con_permiso_de_ver_y_subir_pero_no_eliminar(): void
    {
        $customer = $this->customer();
        $po = $this->stored($customer);
        $role = CustomerPortalRole::create(['customer_id' => $customer->id, 'name' => 'Compras']);
        foreach (['view', 'create'] as $action) {
            CustomerPortalRolePermission::create(['role_id' => $role->id, 'module' => 'purchase-orders', 'action' => $action]);
        }
        $member = $this->member($customer, $role);
        $this->actingAs($customer, 'customer')->withSession(['portal_member_id' => $member->id]);

        $this->get(route('customer.purchase-orders.index'))
            ->assertOk()->assertSee('Subir orden de compra')->assertDontSee('data-name=', false);

        $this->stored($customer, ['uploaded_by_member_id' => $member->id, 'po_number' => 'OC-MIEMBRO']);
        $this->get(route('customer.purchase-orders.index'))->assertSee('Ana Compras');

        $this->post(route('customer.purchase-orders.store'), ['file' => $this->pdf()])
            ->assertRedirect(route('customer.purchase-orders.index'));
        $this->assertSame($member->id, CustomerPurchaseOrder::where('id', '!=', $po->id)->firstOrFail()->uploaded_by_member_id);

        $this->delete(route('customer.purchase-orders.destroy', $po->id))->assertRedirect(route('customer.dashboard'));
        $this->assertDatabaseHas('customer_purchase_orders', ['id' => $po->id]);
    }

    private function member(Customer $customer, CustomerPortalRole $role): CustomerPortalUser
    {
        return CustomerPortalUser::create([
            'customer_id' => $customer->id, 'role_id' => $role->id, 'first_name' => 'Ana', 'last_name' => 'Compras',
            'email' => 'm-' . uniqid() . '@example.com', 'password' => 'secret-pass', 'status' => 'active',
        ]);
    }

    public function test_la_descarga_del_personal_exige_sesion_y_permiso_de_clientes(): void
    {
        $customer = $this->customer();
        $po = $this->stored($customer, ['original_name' => 'oc-staff.pdf']);
        $url = route('admin.customer-purchase-orders.download', $po->id);

        // Sin sesión.
        $this->get($url)->assertRedirect();

        // Usuario del panel sin permiso sobre Clientes.
        $this->actingAs($this->staff('Vendedor'))->get($url)->assertRedirect();

        // Administrador.
        $res = $this->actingAs($this->staff('Administrador'))->get($url);
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('oc-staff.pdf', $res->headers->get('Content-Disposition'));
    }

    public function test_el_nombre_original_se_sanea(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')
            ->post(route('customer.purchase-orders.store'), ['file' => $this->pdf('..\\..\\evil<script>.pdf')])
            ->assertSessionHasNoErrors();

        $name = CustomerPurchaseOrder::firstOrFail()->original_name;
        $this->assertStringNotContainsString('<', $name);
        $this->assertStringNotContainsString('\\', $name);
        $this->assertStringEndsWith('.pdf', $name);
    }
}
