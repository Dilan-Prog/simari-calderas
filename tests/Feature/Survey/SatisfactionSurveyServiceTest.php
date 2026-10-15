<?php

namespace Tests\Feature\Survey;

use App\Models\ServiceRating;
use App\Models\Setting;
use App\Models\SurveyCard;
use App\Models\SurveyQuestion;
use App\Services\SatisfactionSurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SatisfactionSurveyServiceTest extends TestCase
{
    use RefreshDatabase, SurveyFixtures;

    private SatisfactionSurveyService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettingCache();
        $this->svc = app(SatisfactionSurveyService::class);
    }

    public function test_datos_por_defecto_sembrados(): void
    {
        $this->assertSame(5, SurveyCard::count());
        $this->assertSame([1, 2, 3, 4, 5], SurveyCard::orderBy('sort_order')->pluck('stars')->all());
        $this->assertSame('Excelente', SurveyCard::where('stars', 5)->value('label'));
        $this->assertEqualsCanonicalizing(
            ['Limpieza', 'Ordenado', 'Resolvió mi problema', 'Tiempo estimado', 'Calidad-precio'],
            SurveyQuestion::pluck('label')->all()
        );
        $this->assertTrue($this->svc->enabled());
        $this->assertSame('Califica este servicio', $this->svc->text('survey.title'));
        $this->assertSame('survey', Setting::where('key', 'survey.title')->value('group_name'));
    }

    public function test_seed_es_idempotente_y_no_pisa_datos_existentes(): void
    {
        SurveyCard::query()->delete();
        SurveyCard::create(['label' => 'Única', 'emoji' => '👍', 'stars' => 5, 'color' => '#00ff00', 'sort_order' => 1]);
        $migration = require base_path('database/migrations/2026_10_13_110000_seed_survey_defaults.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, SurveyCard::count());
        $this->assertSame(5, SurveyQuestion::count());
        $this->assertSame(1, Setting::where('key', 'survey.title')->count());
    }

    public function test_submit_feliz_guarda_calificacion_respuestas_y_snapshots(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, $this->makeCustomer());
        $card = SurveyCard::where('stars', 4)->first();
        [$q1, $q2] = SurveyQuestion::orderBy('sort_order')->take(2)->get();

        $rating = $this->svc->submit($report, [
            'card_id' => $card->id,
            'answers' => [$q1->id => 5, $q2->id => '3'],
            'comment' => '  Excelente trabajo  ',
        ]);

        $this->assertSame(ServiceRating::STATUS_SUBMITTED, $rating->status);
        $this->assertSame(4, $rating->rating);
        $this->assertSame('Muy bueno', $rating->card_label);
        $this->assertSame('Excelente trabajo', $rating->comment);
        $this->assertSame('Planta Norte S.A.', $rating->customer_name);
        $this->assertNotNull($rating->submitted_at);
        $this->assertEquals([$q1->label => 5, $q2->label => 3], $rating->answers->pluck('score', 'question_label')->all());
    }

    public function test_el_nombre_del_cliente_nunca_viene_del_request(): void
    {
        $report = $this->makeReport($this->adminUser(), null, ['customer_name' => 'Nombre Real']);
        $card = SurveyCard::first();

        $rating = $this->svc->submit($report, ['card_id' => $card->id, 'customer_name' => 'Hacker', 'answers' => []]);

        $this->assertSame('Nombre Real', $rating->customer_name);
    }

    public function test_nombre_cae_al_cliente_si_el_reporte_no_lo_trae(): void
    {
        $report = $this->makeReport($this->adminUser(), $this->makeCustomer('Industrias Beta'), ['customer_name' => '']);

        $this->assertSame('Industrias Beta', $this->svc->ratingFor($report)->customer_name);
    }

    public function test_validaciones(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);
        $q = SurveyQuestion::first();
        $card = SurveyCard::first();

        $fails = function (array $data, ?\App\Models\ServiceReport $r = null) use ($report) {
            try {
                $this->svc->submit($r ?? $report, $data);
            } catch (ValidationException $e) {
                return true;
            }

            return false;
        };

        $this->assertTrue($fails(['card_id' => null]), 'sin tarjeta');
        $this->assertTrue($fails(['card_id' => 99999]), 'tarjeta inexistente');
        $this->assertTrue($fails(['card_id' => $card->id, 'answers' => [$q->id => 6]]), 'score > 5');
        $this->assertTrue($fails(['card_id' => $card->id, 'answers' => [$q->id => 0]]), 'score < 1');
        $this->assertTrue($fails(['card_id' => $card->id, 'answers' => [$q->id => 'abc']]), 'score no numérico');
        $this->assertTrue($fails(['card_id' => $card->id, 'answers' => [99999 => 3]]), 'variable inexistente');
        $this->assertTrue($fails(['card_id' => $card->id, 'comment' => str_repeat('a', 1001)]), 'comentario largo');

        $card->update(['is_active' => false]);
        $this->assertTrue($fails(['card_id' => $card->id]), 'tarjeta inactiva');

        $q->update(['is_active' => false]);
        $other = SurveyCard::active()->first();
        $this->assertTrue($fails(['card_id' => $other->id, 'answers' => [$q->id => 3]]), 'variable inactiva');

        $draft = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null]);
        $this->assertTrue($fails(['card_id' => $other->id], $draft), 'reporte sin firmar');

        $this->assertSame(0, ServiceRating::where('status', 'submitted')->count());
    }

    public function test_doble_envio_rechazado(): void
    {
        $report = $this->makeReport($this->adminUser());
        $card = SurveyCard::first();
        $this->svc->submit($report, ['card_id' => $card->id]);

        $this->expectException(ValidationException::class);
        $this->svc->submit($report, ['card_id' => $card->id]);
    }

    public function test_is_rateable_y_skip_permite_calificar_despues(): void
    {
        $admin = $this->adminUser();
        $signed = $this->makeReport($admin);
        $draft = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null]);

        $this->assertTrue($this->svc->isRateable($signed));
        $this->assertFalse($this->svc->isRateable($draft));

        $this->svc->skip($signed);
        $this->assertSame('skipped', ServiceRating::where('service_report_id', $signed->id)->value('status'));
        $this->assertTrue($this->svc->isRateable($signed), 'omitida sigue disponible');

        $this->svc->submit($signed, ['card_id' => SurveyCard::first()->id]);
        $this->assertFalse($this->svc->isRateable($signed));

        $this->svc->skip($signed); // no degrada una calificación enviada
        $this->assertSame('submitted', ServiceRating::where('service_report_id', $signed->id)->value('status'));
    }

    public function test_encuesta_desactivada_no_es_calificable(): void
    {
        $report = $this->makeReport($this->adminUser());
        Setting::set('survey.enabled', '0');

        $this->assertFalse($this->svc->enabled());
        $this->assertFalse($this->svc->isRateable($report));
    }

    public function test_mapeo_por_tipo_asigna_servicio_web(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage('Limpieza química');
        Setting::set('survey.default_service_by_type', [ 'cleaning' => $page->id ]);

        $cleaning = $this->makeReport($admin, null, ['service_type' => 'cleaning']);
        $maint = $this->makeReport($admin);

        $this->assertSame($page->id, $this->svc->ratingFor($cleaning)->service_page_id);
        $this->assertNull($this->svc->ratingFor($maint)->service_page_id);
    }

    public function test_service_page_del_reporte_tiene_prioridad_sobre_el_mapeo(): void
    {
        $admin = $this->adminUser();
        $own = $this->makeServicePage('Propio');
        $mapped = $this->makeServicePage('Mapeado');
        Setting::set('survey.default_service_by_type', ['cleaning' => $mapped->id]);

        $report = $this->makeReport($admin, null, ['service_type' => 'cleaning', 'service_page_id' => $own->id]);

        $this->assertSame($own->id, $this->svc->ratingFor($report)->service_page_id);
    }

    public function test_pending_for_lista_firmados_sin_calificar_del_cliente(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCustomer();
        $otro = $this->makeCustomer('Otro');
        $a = $this->makeReport($admin, $c);
        $b = $this->makeReport($admin, $c);
        $this->makeReport($admin, $c, ['status' => 'completed', 'signed_at' => null]);
        $this->makeReport($admin, $otro);

        $this->svc->submit($a, ['card_id' => SurveyCard::first()->id]);

        $this->assertEquals([$b->id], $this->svc->pendingFor($c)->pluck('id')->all());
    }

    public function test_un_cliente_puede_dejar_varias_opiniones_una_por_reporte(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCustomer();
        $card = SurveyCard::first();

        foreach (range(1, 3) as $_) {
            $this->svc->submit($this->makeReport($admin, $c), ['card_id' => $card->id]);
        }

        $this->assertSame(3, ServiceRating::where('customer_id', $c->id)->where('status', 'submitted')->count());
    }
}
