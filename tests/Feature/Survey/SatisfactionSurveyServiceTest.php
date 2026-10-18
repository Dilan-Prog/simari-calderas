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
            ['Limpieza', 'Ordenado', 'Resolvió mi problema', 'Tiempo estimado', 'Calidad-precio',
                'Puntualidad', 'Trato del técnico', 'Rapidez', 'Orden y limpieza', 'Explicación clara', 'Calidad del trabajo'],
            SurveyQuestion::where('kind', 'positive')->pluck('label')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['Llegó tarde', 'Trabajo incompleto', 'Poca comunicación', 'Área sucia', 'Tiempo de espera', 'La falla persiste'],
            SurveyQuestion::where('kind', 'negative')->pluck('label')->all()
        );
        $this->assertSame('¿Qué fue lo mejor?', $this->svc->text('survey.good_title'));
        $this->assertSame('¿Qué podemos mejorar?', $this->svc->text('survey.bad_title'));
        $this->assertStringContainsString('{tecnico}', $this->svc->text('survey.thanks_good'));
        $this->assertStringContainsString('24 horas', $this->svc->text('survey.thanks_bad'));
        $this->assertTrue($this->svc->enabled());
        $this->assertSame('Califica este servicio', $this->svc->text('survey.title'));
        $this->assertSame('survey', Setting::where('key', 'survey.title')->value('group_name'));
    }

    public function test_seed_es_idempotente_y_no_pisa_datos_existentes(): void
    {
        SurveyCard::query()->delete();
        SurveyCard::create(['label' => 'Única', 'stars' => 5, 'color' => '#00ff00', 'sort_order' => 1]);
        $migration = require base_path('database/migrations/2026_10_13_110000_seed_survey_defaults.php');
        $migration->up();
        $migration->up();

        $kindMigration = require base_path('database/migrations/2026_10_14_100000_add_kind_to_survey_questions.php');
        $kindMigration->up();
        $kindMigration->up();

        $this->assertSame(1, SurveyCard::count());
        $this->assertSame(17, SurveyQuestion::count(), 'sin duplicar tras correr de nuevo');
        $this->assertSame(1, Setting::where('key', 'survey.title')->count());
    }

    public function test_submit_feliz_guarda_calificacion_respuestas_y_snapshots(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, $this->makeCustomer());
        $card = SurveyCard::where('stars', 4)->first();
        [$q1, $q2] = SurveyQuestion::where('kind', 'positive')->orderBy('sort_order')->take(2)->get();

        $rating = $this->svc->submit($report, [
            'card_id' => $card->id,
            'chips'   => [$q1->id, (string) $q2->id],
            'comment' => '  Excelente trabajo  ',
        ]);

        $this->assertSame(ServiceRating::STATUS_SUBMITTED, $rating->status);
        $this->assertSame(4, $rating->rating);
        $this->assertSame('Muy bueno', $rating->card_label);
        $this->assertSame('Excelente trabajo', $rating->comment);
        $this->assertSame('Planta Norte S.A.', $rating->customer_name);
        $this->assertNotNull($rating->submitted_at);
        $this->assertEquals([$q1->label => 5, $q2->label => 5], $rating->answers->pluck('score', 'question_label')->all());
        $this->assertSame(['positive'], $rating->answers->pluck('kind')->unique()->values()->all());
    }

    public function test_menos_de_cuatro_estrellas_guarda_chips_negativos_con_score_1(): void
    {
        $report = $this->makeReport($this->adminUser());
        $card = SurveyCard::where('stars', 2)->first();
        $neg = SurveyQuestion::where('kind', 'negative')->orderBy('sort_order')->take(2)->get();

        $rating = $this->svc->submit($report, ['card_id' => $card->id, 'chips' => $neg->pluck('id')->all()]);

        $this->assertEquals($neg->pluck('label')->map(fn ($l) => [$l, 1])->all(), $rating->answers->map(fn ($a) => [$a->question_label, $a->score])->all());
        $this->assertSame(['negative'], $rating->answers->pluck('kind')->unique()->values()->all());
    }

    public function test_los_chips_son_opcionales(): void
    {
        $report = $this->makeReport($this->adminUser());
        $rating = $this->svc->submit($report, ['card_id' => SurveyCard::where('stars', 5)->first()->id]);

        $this->assertCount(0, $rating->answers);
    }

    public function test_el_tipo_del_chip_debe_coincidir_con_el_nivel(): void
    {
        $admin = $this->adminUser();
        $pos = SurveyQuestion::where('kind', 'positive')->first();
        $neg = SurveyQuestion::where('kind', 'negative')->first();

        foreach ([[5, $neg], [4, $neg], [3, $pos], [1, $pos]] as [$stars, $chip]) {
            $report = $this->makeReport($admin);
            try {
                $this->svc->submit($report, ['card_id' => SurveyCard::where('stars', $stars)->first()->id, 'chips' => [$chip->id]]);
                $this->fail("{$stars} estrellas aceptó un chip {$chip->kind}");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('chips', $e->errors());
            }
            $this->assertSame(0, \App\Models\ServiceRatingAnswer::count());
        }
    }

    public function test_se_puede_editar_antes_de_publicar_y_se_rechaza_despues(): void
    {
        $report = $this->makeReport($this->adminUser());
        $pos = SurveyQuestion::where('kind', 'positive')->first();
        $neg = SurveyQuestion::where('kind', 'negative')->first();

        $this->svc->submit($report, ['card_id' => SurveyCard::where('stars', 5)->first()->id, 'chips' => [$pos->id], 'comment' => 'Primero']);
        $this->assertTrue($this->svc->isEditable($this->svc->submittedFor($report)));

        $edited = $this->svc->submit($report, ['card_id' => SurveyCard::where('stars', 2)->first()->id, 'chips' => [$neg->id], 'comment' => 'Segundo']);
        $this->assertSame(1, ServiceRating::where('service_report_id', $report->id)->count());
        $this->assertSame(2, $edited->rating);
        $this->assertSame('Segundo', $edited->comment);
        $this->assertSame([$neg->label], $edited->answers->pluck('question_label')->all());
        $this->assertSame('Planta Norte S.A.', $edited->customer_name);

        // al publicarse como reseña ya no se puede modificar
        $page = $this->makeServicePage('Publicable');
        $review = \App\Models\ServicePageReview::create([
            'service_page_id' => $page->id, 'customer_name' => 'X', 'review_date' => now(), 'rating' => 2,
            'is_visible' => true, 'comment' => 'x', 'service_rating_id' => $edited->id, 'source' => 'client',
        ]);
        $edited->update(['service_page_review_id' => $review->id]);
        $this->assertFalse($this->svc->isEditable($edited->fresh()));

        try {
            $this->svc->submit($report, ['card_id' => SurveyCard::where('stars', 5)->first()->id]);
            $this->fail('permitió editar una calificación publicada');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('publicó', $e->errors()['report'][0]);
        }
        $this->assertSame(2, $edited->fresh()->rating);
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
        $q = SurveyQuestion::where('kind', 'positive')->first();
        $card = SurveyCard::where('stars', 5)->first();

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
        $this->assertTrue($fails(['card_id' => $card->id, 'chips' => ['abc']]), 'chip no numérico');
        $this->assertTrue($fails(['card_id' => $card->id, 'chips' => [99999]]), 'variable inexistente');
        $this->assertTrue($fails(['card_id' => $card->id, 'comment' => str_repeat('a', 1001)]), 'comentario largo');

        $card->update(['is_active' => false]);
        $this->assertTrue($fails(['card_id' => $card->id]), 'tarjeta inactiva');

        $q->update(['is_active' => false]);
        $other = SurveyCard::active()->first();
        $this->assertTrue($fails(['card_id' => $other->id, 'chips' => [$q->id]]), 'variable inactiva');

        $draft = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null]);
        $this->assertTrue($fails(['card_id' => $other->id], $draft), 'reporte sin firmar');

        $this->assertSame(0, ServiceRating::where('status', 'submitted')->count());
    }

    public function test_una_sola_calificacion_por_reporte_al_reenviar(): void
    {
        $report = $this->makeReport($this->adminUser());
        $card = SurveyCard::first();
        $this->svc->submit($report, ['card_id' => $card->id]);
        $this->svc->submit($report, ['card_id' => $card->id]);

        $this->assertSame(1, ServiceRating::where('service_report_id', $report->id)->count());
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
