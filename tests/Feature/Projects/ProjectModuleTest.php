<?php

namespace Tests\Feature\Projects;

use App\Models\Category;
use App\Models\DocumentLink;
use App\Models\Products;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Services\Erp\Kpis\ProjectsKpi;
use App\Services\ProjectService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function quote(array $over = []): Quote
    {
        return Quote::create(array_merge([
            'quote_number' => 'COT-' . uniqid(), 'status' => 'sent', 'created_by_user_id' => User::first()->id,
            'guest_name' => 'Cliente', 'guest_email' => 'c@example.com', 'currency' => 'MXN',
            'subtotal' => 100, 'discount_total' => 0, 'tax_rate' => 16, 'tax_total' => 16, 'total' => 116,
        ], $over));
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Instalación de caldera',
            'description' => 'Proyecto de prueba',
            'status' => 'planeado',
            'start_date' => '2026-10-01',
            'due_date' => '2026-12-01',
            'phases' => [
                ['name' => 'Levantamiento', 'status' => 'completada', 'progress' => 0, 'start_date' => '2026-10-01', 'end_date' => '2026-10-10'],
                ['name' => 'Instalación', 'status' => 'en_curso', 'progress' => 50, 'start_date' => '2026-10-11', 'end_date' => '2026-10-20', 'requirements' => 'Material en sitio'],
            ],
        ], $over);
    }

    public function test_folio_secuencial_por_anio(): void
    {
        $svc = app(ProjectService::class);
        $a = $svc->create(['name' => 'A']);
        $b = $svc->create(['name' => 'B']);

        $y = date('Y');
        $this->assertSame("PRY-{$y}-0001", $a->project_number);
        $this->assertSame("PRY-{$y}-0002", $b->project_number);
    }

    public function test_crear_proyecto_con_fases_y_encargados_desde_el_formulario(): void
    {
        $admin = $this->admin();
        $data = $this->payload();
        $data['phases'][1]['assignees'] = [$admin->id];

        $this->actingAs($admin)->post(route('admin.projects.store'), $data)->assertRedirect();

        $project = Project::first();
        $this->assertNotNull($project);
        $this->assertSame('planeado', $project->status);
        $this->assertSame(['Levantamiento', 'Instalación'], $project->phases->pluck('name')->all());
        $this->assertSame([0, 1], $project->phases->pluck('sort_order')->all());
        $this->assertSame([$admin->id], $project->phases[1]->assignees->pluck('id')->all());
        $this->assertSame(100, $project->phases[0]->progress); // completada => 100
    }

    public function test_actualizar_reemplaza_fases_en_orden(): void
    {
        $admin = $this->admin();
        $project = app(ProjectService::class)->create(['name' => 'X'], $this->payload()['phases']);

        $this->actingAs($admin)->put(route('admin.projects.update', $project), [
            'name' => 'X2',
            'phases' => [['name' => 'Nueva A'], ['name' => 'Nueva B']],
        ])->assertRedirect();

        $this->assertSame('X2', $project->fresh()->name);
        $this->assertSame(['Nueva A', 'Nueva B'], $project->fresh()->phases->pluck('name')->all());
    }

    public function test_avance_global_ponderado_por_duracion(): void
    {
        $svc = app(ProjectService::class);
        $p = $svc->create(['name' => 'P'], [
            ['name' => 'Corta', 'status' => 'completada', 'start_date' => '2026-10-01', 'end_date' => '2026-10-10'],  // 10 días al 100
            ['name' => 'Larga', 'status' => 'en_curso', 'progress' => 0, 'start_date' => '2026-10-11', 'end_date' => '2026-10-30'], // 20 días al 0
        ]);

        $this->assertSame(33, $svc->progress($p->fresh()));
        $this->assertSame(0, $svc->progress($svc->create(['name' => 'vacío'])));
    }

    public function test_listado_filtra_por_estado_y_busqueda_y_muestra_tablero(): void
    {
        $admin = $this->admin();
        $svc = app(ProjectService::class);
        $svc->create(['name' => 'Alberca Norte', 'status' => 'en_ejecucion', 'due_date' => now()->subDays(3)->toDateString()]);
        $svc->create(['name' => 'Spa Sur', 'status' => 'cerrado']);

        $this->actingAs($admin)->get(route('admin.projects.index', ['status' => 'en_ejecucion']))
            ->assertOk()->assertSee('Alberca Norte')->assertDontSee('Spa Sur');

        $this->actingAs($admin)->get(route('admin.projects.index', ['search' => 'Spa']))
            ->assertOk()->assertSee('Spa Sur')->assertDontSee('Alberca Norte');

        $this->actingAs($admin)->get(route('admin.projects.index', ['view' => 'board']))
            ->assertOk()->assertSee('Alberca Norte')->assertSee('Spa Sur');

        $this->actingAs($admin)->get(route('admin.projects.index', ['due' => 'vencido']))
            ->assertOk()->assertSee('Alberca Norte')->assertDontSee('Spa Sur');
    }

    public function test_semaforo_de_fecha_limite(): void
    {
        $mk = fn ($days, $status = 'planeado') => new Project(['due_date' => now()->addDays($days), 'status' => $status]);

        $this->assertSame('ok', $mk(30)->dueTone());
        $this->assertSame('warn', $mk(5)->dueTone());
        $this->assertSame('bad', $mk(-2)->dueTone());
        $this->assertSame('neutral', $mk(-2, 'cerrado')->dueTone());
        $this->assertSame('neutral', (new Project(['status' => 'planeado']))->dueTone());
    }

    public function test_show_renderiza_pestanas_y_flujo_y_cambia_estado(): void
    {
        $admin = $this->admin();
        $project = app(ProjectService::class)->create(['name' => 'Proyecto Z', 'start_date' => '2026-10-01', 'due_date' => '2026-12-01'], $this->payload()['phases']);
        $quote = $this->quote();
        app(ProjectService::class)->attach($project, $quote);

        $this->actingAs($admin)->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee($project->project_number)
            ->assertSee('Flujo del documento')
            ->assertSee($quote->quote_number)
            ->assertSee('Línea de tiempo');

        $this->actingAs($admin)->patch(route('admin.projects.update-status', $project), ['status' => 'en_ejecucion'])->assertRedirect();
        $this->assertSame('en_ejecucion', $project->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.projects.update-status', $project), ['status' => 'inventado'])->assertSessionHasErrors('status');

        // Queda registro en el historial (LogsActivity).
        $this->actingAs($admin)->get(route('admin.projects.show', $project))->assertSee('en_ejecucion', false);
    }

    public function test_asociar_y_desasociar_cotizacion_actualiza_el_flujo(): void
    {
        $admin = $this->admin();
        $project = app(ProjectService::class)->create(['name' => 'P']);
        $quote = $this->quote();

        $this->actingAs($admin)->post(route('admin.projects.attach', $project), ['type' => 'quote', 'id' => $quote->id])->assertRedirect();
        $this->assertSame($project->id, $quote->fresh()->project_id);
        $this->assertSame(1, DocumentLink::where(['from_type' => 'quote', 'to_type' => 'project'])->count());
        $this->assertCount(2, DocumentFlow::flow($project));

        $this->actingAs($admin)->delete(route('admin.projects.detach', $project), ['type' => 'quote', 'id' => $quote->id])->assertRedirect();
        $this->assertNull($quote->fresh()->project_id);
        $this->assertSame(0, DocumentLink::count());
    }

    public function test_link_document_desde_la_cotizacion_cambia_de_proyecto(): void
    {
        $admin = $this->admin();
        $a = app(ProjectService::class)->create(['name' => 'A']);
        $b = app(ProjectService::class)->create(['name' => 'B']);
        $quote = $this->quote();

        $this->actingAs($admin)->post(route('admin.projects.link-document'), ['type' => 'quote', 'id' => $quote->id, 'project_id' => $a->id])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.projects.link-document'), ['type' => 'quote', 'id' => $quote->id, 'project_id' => $b->id])->assertRedirect();

        $this->assertSame($b->id, $quote->fresh()->project_id);
        $this->assertSame(1, DocumentLink::where('to_type', 'project')->count());
        $this->assertSame($b->id, (int) DocumentLink::where('to_type', 'project')->value('to_id'));

        $this->actingAs($admin)->post(route('admin.projects.link-document'), ['type' => 'quote', 'id' => $quote->id, 'project_id' => ''])->assertRedirect();
        $this->assertNull($quote->fresh()->project_id);
    }

    public function test_aceptar_cotizacion_con_proyecto_crea_pedido_ligado_al_proyecto(): void
    {
        $this->admin();
        $category = Category::create(['name' => 'Bombas', 'slug' => 'bombas-' . uniqid(), 'is_active' => true]);
        $product = Products::create([
            'category_id' => $category->id, 'name' => 'Bomba 5HP', 'slug' => 'bomba-' . uniqid(),
            'sku' => 'B-' . uniqid(), 'price' => 15000, 'stock' => 10, 'is_active' => true,
        ]);
        $project = app(ProjectService::class)->create(['name' => 'P']);
        $quote = $this->quote(['project_id' => $project->id, 'status' => 'accepted']);
        QuoteItem::create([
            'quote_id' => $quote->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'product_sku' => $product->sku, 'quantity' => 1, 'unit_price' => 15000, 'line_total' => 15000, 'sort_order' => 0,
        ]);

        $order = app(QuoteService::class)->processAcceptance($quote);

        $this->assertNotNull($order);
        $this->assertSame($project->id, $order->project_id);
        $this->assertTrue(DocumentLink::where(['from_type' => 'quote', 'from_id' => $quote->id, 'to_type' => 'sales_order', 'to_id' => $order->id])->exists());
        $this->assertTrue(DocumentLink::where(['from_type' => 'project', 'from_id' => $project->id, 'to_type' => 'sales_order', 'to_id' => $order->id])->exists());

        // syncDocumentFlow es idempotente.
        app(ProjectService::class)->syncDocumentFlow($project);
        $this->assertSame(3, DocumentLink::count());
    }

    public function test_kpi_del_launchpad(): void
    {
        $svc = app(ProjectService::class);
        $svc->create(['name' => 'A', 'status' => 'en_ejecucion', 'due_date' => now()->subDay()->toDateString()]);
        $svc->create(['name' => 'B', 'status' => 'en_ejecucion']);
        $svc->create(['name' => 'C', 'status' => 'cerrado', 'due_date' => now()->subDay()->toDateString()]);

        $kpi = ProjectsKpi::kpi();
        $this->assertSame(2, $kpi['value']);
        $this->assertSame('bad', $kpi['tone']);
    }

    public function test_formularios_y_vistas_de_documento_renderizan(): void
    {
        $admin = $this->admin();
        $project = app(ProjectService::class)->create(['name' => 'P'], $this->payload()['phases']);

        $this->actingAs($admin)->get(route('admin.projects.create'))->assertOk()->assertSee('Nuevo proyecto');
        $this->actingAs($admin)->get(route('admin.projects.edit', $project))->assertOk()->assertSee('Instalación');
        $this->actingAs($admin)->get(route('admin.projects.search', ['q' => 'PRY']))->assertOk()->assertJsonCount(1);

        $quote = $this->quote();
        $this->actingAs($admin)->get(route('admin.projects.documents', ['type' => 'quote', 'q' => $quote->quote_number]))
            ->assertOk()->assertJsonPath('0.number', $quote->quote_number);
    }

    public function test_eliminar_proyecto_conserva_documentos(): void
    {
        $admin = $this->admin();
        $project = app(ProjectService::class)->create(['name' => 'P']);
        $quote = $this->quote();
        app(ProjectService::class)->attach($project, $quote);

        $this->actingAs($admin)->delete(route('admin.projects.destroy', $project))->assertRedirect(route('admin.projects.index'));

        $this->assertNull(Project::find($project->id));
        $this->assertNull($quote->fresh()->project_id);
        $this->assertSame(0, DocumentLink::count());
    }
}
