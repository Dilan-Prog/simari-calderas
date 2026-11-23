<?php

namespace Tests\Feature;

use App\Http\Resources\Mobile\ServiceReportDetailResource;
use App\Models\ServiceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Survey\SurveyFixtures;
use Tests\TestCase;

/**
 * Equipo, lecturas, materiales y pies de foto capturados en la app móvil:
 * se muestran en el PDF, en el detalle del panel y en el recurso de la app;
 * los reportes anteriores (sin esos datos) se siguen viendo igual.
 */
class ServiceReportPdfFieldDataTest extends TestCase
{
    use RefreshDatabase, SurveyFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    /** Reporte capturado en la app. Se escribe con DB::table para no depender de los casts del modelo. */
    private function appReport($admin): ServiceReport
    {
        $report = $this->makeReport($admin);

        DB::table('service_reports')->where('id', $report->id)->update([
            'equipment' => json_encode([
                'tipo' => 'Caldera pirotubular', 'marca' => 'Cleaver-Brooks', 'modelo' => 'CB-700',
                'numero_serie' => 'SN-12345', 'capacidad' => '150 CC',
                // 'ubicacion' ausente a propósito: las claves pueden faltar.
            ]),
            'readings' => json_encode([
                'presion_operacion' => 120, 'temperatura_chimenea' => '210.5', 'tds' => 'No medido', 'ph' => 10.8,
            ]),
            'materials' => json_encode([
                ['descripcion' => 'Empaque de registro de mano', 'cantidad' => 2, 'unidad' => 'pza'],
                ['descripcion' => 'Fluxómetro de purga', 'cantidad' => 1],
            ]),
            'origin' => 'app_movil',
            'captured_at' => '2026-11-20 09:15:00',
        ]);

        $report->images()->create(['path' => 'service-reports/no-existe.jpg', 'caption' => 'Registro de mano antes de limpieza', 'sort_order' => 1]);

        return $this->fresh($report);
    }

    private function fresh(ServiceReport $report): ServiceReport
    {
        return ServiceReport::with(['measurements', 'activity', 'customFields', 'assignedUser', 'createdBy', 'images'])
            ->findOrFail($report->id);
    }

    public function test_pdf_muestra_equipo_lecturas_materiales_y_pies_de_foto(): void
    {
        $report = $this->appReport($this->adminUser());

        $html = view('admin.service-reports.pdf', compact('report'))->render();

        $this->assertStringContainsString('Equipo, lecturas y materiales', $html);
        foreach (['Caldera pirotubular', 'Cleaver-Brooks', 'CB-700', 'SN-12345', '150 CC', 'N.º de serie'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('Ubicación</td>', $html, 'Una clave ausente no debe pintar fila vacía');
        $this->assertStringContainsString('120 psi', $html);
        $this->assertStringContainsString('210.5 °C', $html);
        $this->assertStringContainsString('No medido', $html);
        $this->assertStringNotContainsString('No medido ppm', $html);
        $this->assertStringContainsString('10.8', $html);
        $this->assertStringContainsString('Empaque de registro de mano', $html);
        $this->assertStringContainsString('pza', $html);
        $this->assertStringContainsString('Fluxómetro de purga', $html);
        $this->assertStringContainsString('Registro de mano antes de limpieza', $html);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))->setPaper('a4', 'portrait')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_pdf_de_reporte_anterior_no_muestra_la_seccion(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);
        $report->images()->create(['path' => 'service-reports/no-existe.jpg', 'sort_order' => 1]);
        $report = $this->fresh($report);

        $html = view('admin.service-reports.pdf', compact('report'))->render();

        $this->assertStringNotContainsString('Equipo, lecturas y materiales', $html);
        $this->assertStringNotContainsString('Materiales utilizados', $html);
        $this->assertStringContainsString('Evidencia Fotográfica (1)', $html);

        $pdf = Pdf::loadView('admin.service-reports.pdf', compact('report'))->setPaper('a4', 'portrait')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_detalle_del_panel_muestra_datos_de_campo_y_origen_app(): void
    {
        $admin = $this->adminUser();
        $report = $this->appReport($admin);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertSee('Equipo, lecturas y materiales')
            ->assertSee('Cleaver-Brooks')
            ->assertSee('120 psi')
            ->assertSee('Materiales utilizados')
            ->assertSee('Empaque de registro de mano')
            ->assertSee('Capturado en la app')
            ->assertSee('20/11/2026 09:15')
            ->assertSee('Registro de mano antes de limpieza');
    }

    public function test_detalle_del_panel_de_reporte_anterior_no_cambia(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertDontSee('Equipo, lecturas y materiales')
            ->assertDontSee('Capturado en la app');
    }

    public function test_recurso_movil_expone_datos_de_campo(): void
    {
        $admin = $this->adminUser();

        $data = (new ServiceReportDetailResource($this->appReport($admin)))->toArray(Request::create('/'));
        $this->assertSame('Cleaver-Brooks', $data['equipment']['marca']);
        $this->assertSame(120, $data['readings']['presion_operacion']);
        $this->assertCount(2, $data['materials']);
        $this->assertSame('pza', $data['materials'][0]['unidad']);
        $this->assertSame('app_movil', $data['origin']);
        $this->assertStringStartsWith('2026-11-20T09:15:00', $data['captured_at']);
        $this->assertSame('Registro de mano antes de limpieza', $data['images'][0]['caption']);

        $old = (new ServiceReportDetailResource($this->fresh($this->makeReport($admin))))->toArray(Request::create('/'));
        $this->assertNull($old['equipment']);
        $this->assertNull($old['readings']);
        $this->assertSame([], $old['materials']);
        $this->assertNull($old['origin']);
        $this->assertNull($old['captured_at']);
    }
}
