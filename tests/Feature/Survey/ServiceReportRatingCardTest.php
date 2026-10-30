<?php

namespace Tests\Feature\Survey;

use App\Models\ServiceRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** El detalle del reporte de servicio muestra la calificación que dio el cliente. */
class ServiceReportRatingCardTest extends TestCase
{
    use RefreshDatabase, SurveyFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    public function test_reporte_calificado_muestra_estrellas_opciones_y_comentario(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);
        $rating = ServiceRating::create([
            'service_report_id' => $report->id, 'customer_name' => 'Planta Norte S.A.', 'rating' => 4,
            'card_label' => 'Muy bien', 'comment' => 'El técnico llegó puntual', 'status' => 'submitted', 'submitted_at' => now(),
        ]);
        $rating->answers()->createMany([
            ['question_label' => 'Puntualidad', 'kind' => 'positive', 'score' => 1],
            ['question_label' => 'Limpieza', 'kind' => 'negative', 'score' => 1],
        ]);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertSee('Calificación del servicio')
            ->assertSee('4/5')
            ->assertSee('Muy bien')
            ->assertSee('Puntualidad')
            ->assertSee('Limpieza')
            ->assertSee('El técnico llegó puntual')
            ->assertSee(route('admin.service-ratings.show', $rating), false);
    }

    public function test_reporte_firmado_sin_calificar_lo_indica(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertSee('Calificación del servicio')
            ->assertSee('Este servicio aún no ha sido calificado.');
    }

    public function test_calificacion_omitida_se_muestra_como_omitida(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);
        ServiceRating::create(['service_report_id' => $report->id, 'customer_name' => 'X', 'status' => 'skipped']);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertSee('El cliente omitió la calificación');
    }

    public function test_reporte_no_firmado_no_muestra_la_tarjeta(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, null, ['status' => 'in_progress', 'signed_at' => null, 'current_step' => 3]);

        $this->actingAs($admin)->get(route('admin.service-reports.show', $report))
            ->assertOk()
            ->assertDontSee('Calificación del servicio');
    }
}
