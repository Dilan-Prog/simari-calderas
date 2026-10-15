<?php

namespace Tests\Feature\Survey;

use App\Models\ServiceRating;
use App\Models\Setting;
use App\Models\SurveyCard;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyHttpTest extends TestCase
{
    use RefreshDatabase, SurveyFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    // ── Panel de Configuración ERP ──────────────────────────────────────────

    public function test_panel_muestra_la_seccion_de_encuesta(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.erp-settings.index'))
            ->assertOk()
            ->assertSee('Encuesta de satisfacción')
            ->assertSee('Resolvió mi problema')
            ->assertSee('Excelente');
    }

    public function test_crud_de_tarjetas(): void
    {
        $this->actingAs($this->adminUser());

        $this->post(route('admin.erp-settings.survey.cards.store'), ['label' => 'Increíble', 'stars' => 5, 'color' => '#112233'])
            ->assertRedirect();
        $card = SurveyCard::where('label', 'Increíble')->firstOrFail();
        $this->assertSame(6, $card->sort_order);
        $this->assertTrue($card->is_active);

        $this->put(route('admin.erp-settings.survey.cards.update', $card), ['label' => 'Increíble!', 'stars' => 4, 'color' => '#445566'])
            ->assertRedirect();
        $card->refresh();
        $this->assertSame(4, $card->stars);
        $this->assertFalse($card->is_active, 'sin checkbox queda inactiva');

        $this->post(route('admin.erp-settings.survey.cards.store'), ['label' => 'x', 'stars' => 9, 'color' => 'rojo'])
            ->assertSessionHasErrors(['stars', 'color']);

        $this->delete(route('admin.erp-settings.survey.cards.destroy', $card))->assertRedirect();
        $this->assertNull(SurveyCard::find($card->id));
    }

    public function test_mover_reordena_tarjetas_y_variables(): void
    {
        $this->actingAs($this->adminUser());
        $first = SurveyQuestion::orderBy('sort_order')->first();
        $second = SurveyQuestion::orderBy('sort_order')->skip(1)->first();

        $this->post(route('admin.erp-settings.survey.questions.move', $second), ['direction' => 'up'])->assertRedirect();

        $this->assertSame($second->id, SurveyQuestion::orderBy('sort_order')->first()->id);
        $this->assertSame(2, $first->fresh()->sort_order);

        $last = SurveyCard::orderBy('sort_order')->get()->last();
        $this->post(route('admin.erp-settings.survey.cards.move', $last), ['direction' => 'down'])->assertRedirect();
        $this->assertSame($last->id, SurveyCard::orderBy('sort_order')->get()->last()->id);
    }

    public function test_crud_de_variables_y_desactivar(): void
    {
        $this->actingAs($this->adminUser());

        $this->post(route('admin.erp-settings.survey.questions.store'), ['label' => 'Puntualidad'])->assertRedirect();
        $q = SurveyQuestion::where('label', 'Puntualidad')->firstOrFail();

        $this->put(route('admin.erp-settings.survey.questions.update', $q), ['label' => 'Puntualidad del técnico', 'is_active' => 1])->assertRedirect();
        $this->assertSame('Puntualidad del técnico', $q->fresh()->label);
        $this->assertTrue($q->fresh()->is_active);

        $this->put(route('admin.erp-settings.survey.questions.update', $q), ['label' => 'Puntualidad del técnico'])->assertRedirect();
        $this->assertFalse($q->fresh()->is_active);

        $this->post(route('admin.erp-settings.survey.questions.store'), ['label' => ''])->assertSessionHasErrors('label');

        $this->delete(route('admin.erp-settings.survey.questions.destroy', $q))->assertRedirect();
        $this->assertNull(SurveyQuestion::find($q->id));
    }

    public function test_guardar_textos_y_mapeo(): void
    {
        $this->actingAs($this->adminUser());
        $page = $this->makeServicePage();

        $this->put(route('admin.erp-settings.survey.settings'), [
            'survey_enabled' => 0,
            'survey_title' => 'Evalúa tu servicio', 'survey_subtitle' => 'Sub', 'survey_comment_label' => 'Comentario',
            'survey_submit_label' => 'Mandar', 'survey_thanks_message' => 'Gracias totales', 'survey_name_label' => 'Nombre',
            'service_by_type' => ['cleaning' => $page->id, 'inspection' => '', 'tipo_falso' => $page->id],
        ])->assertRedirect();

        $svc = app(\App\Services\SatisfactionSurveyService::class);
        $this->assertFalse($svc->enabled());
        $this->assertSame('Evalúa tu servicio', $svc->text('survey.title'));
        $this->assertSame('Gracias totales', $svc->text('survey.thanks_message'));
        $this->assertSame(['cleaning' => $page->id], $svc->defaultServiceMap());
    }

    public function test_sin_permiso_no_entra_al_panel_ni_modifica(): void
    {
        $this->actingAs($this->plainUser());

        $this->post(route('admin.erp-settings.survey.cards.store'), ['label' => 'x', 'stars' => 3, 'color' => '#000000'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(5, SurveyCard::count());
    }

    // ── Firma → pantalla de calificación ────────────────────────────────────

    public function test_al_firmar_redirige_a_califica_este_servicio(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null, 'signature_data' => null, 'current_step' => 5]);

        $this->actingAs($admin)
            ->post(route('admin.service-reports.sign', $report), [
                'signature_data' => 'data:image/png;base64,AAAA', 'signature_name' => 'Cliente', 'signature_position' => 'Gerente',
            ])
            ->assertRedirect(route('admin.service-reports.rate', $report));

        $this->get(route('admin.service-reports.rate', $report))
            ->assertOk()
            ->assertSee('Califica este servicio')
            ->assertSee('Planta Norte S.A.')
            ->assertSee('Limpieza');
    }

    public function test_si_la_encuesta_esta_desactivada_firmar_va_al_detalle(): void
    {
        Setting::set('survey.enabled', '0');
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null, 'signature_data' => null, 'current_step' => 5]);

        $this->actingAs($admin)
            ->post(route('admin.service-reports.sign', $report), [
                'signature_data' => 'data:image/png;base64,AAAA', 'signature_name' => 'Cliente', 'signature_position' => 'Gerente',
            ])
            ->assertRedirect(route('admin.service-reports.show', $report));
    }

    public function test_admin_califica_con_nombre_bloqueado_aunque_envien_otro(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);
        $card = SurveyCard::where('stars', 5)->first();
        $q = SurveyQuestion::first();

        $this->actingAs($admin)->post(route('admin.service-reports.rate', $report), [
            'card_id' => $card->id, 'answers' => [$q->id => 4], 'comment' => 'Todo bien', 'customer_name' => 'Otro Nombre',
        ])->assertRedirect(route('admin.service-reports.rate', $report));

        $rating = ServiceRating::where('service_report_id', $report->id)->firstOrFail();
        $this->assertSame('Planta Norte S.A.', $rating->customer_name);
        $this->assertSame(5, $rating->rating);

        $this->get(route('admin.service-reports.rate', $report))
            ->assertOk()->assertSee('Gracias')->assertSee('Todo bien');

        // un segundo envío se rechaza y conserva el primero
        $this->post(route('admin.service-reports.rate', $report), ['card_id' => SurveyCard::where('stars', 1)->first()->id])
            ->assertSessionHasErrors('report');
        $this->assertSame(5, $rating->fresh()->rating);
    }

    public function test_pantalla_marca_el_nombre_como_solo_lectura(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);

        $html = $this->actingAs($admin)->get(route('admin.service-reports.rate', $report))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]*value="Planta Norte S\.A\."[^>]*readonly[^>]*disabled/', $html);
    }

    public function test_admin_omitir_deja_pendiente_para_despues(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin);

        $this->actingAs($admin)->post(route('admin.service-reports.rate.skip', $report))
            ->assertRedirect(route('admin.service-reports.show', $report));

        $this->assertSame('skipped', ServiceRating::where('service_report_id', $report->id)->value('status'));
        $this->get(route('admin.service-reports.rate', $report))->assertOk()->assertSee('Califica este servicio');
    }

    public function test_reporte_sin_firmar_no_se_califica(): void
    {
        $admin = $this->adminUser();
        $report = $this->makeReport($admin, null, ['status' => 'completed', 'signed_at' => null]);

        $this->actingAs($admin)->get(route('admin.service-reports.rate', $report))
            ->assertRedirect(route('admin.service-reports.show', $report));
        $this->post(route('admin.service-reports.rate', $report), ['card_id' => SurveyCard::first()->id])
            ->assertSessionHasErrors('report');
        $this->assertSame(0, ServiceRating::count());
    }

    // ── Portal de clientes ──────────────────────────────────────────────────

    public function test_portal_solo_califica_sus_reportes(): void
    {
        $admin = $this->adminUser();
        $mine = $this->makeCustomer('Mía');
        $other = $this->makeCustomer('Ajena');
        $myReport = $this->makeReport($admin, $mine);
        $theirReport = $this->makeReport($admin, $other);
        $card = SurveyCard::first();

        $this->actingAs($mine, 'customer');

        $this->get(route('customer.service-reports.rate', $theirReport))->assertForbidden();
        $this->post(route('customer.service-reports.rate', $theirReport), ['card_id' => $card->id])->assertForbidden();
        $this->post(route('customer.service-reports.rate.skip', $theirReport))->assertForbidden();
        $this->assertSame(0, ServiceRating::count());

        $this->get(route('customer.service-reports.rate', $myReport))->assertOk()->assertSee('Califica este servicio');
        $this->post(route('customer.service-reports.rate', $myReport), ['card_id' => $card->id, 'comment' => 'ok'])
            ->assertRedirect(route('customer.service-reports.rate', $myReport));

        $rating = ServiceRating::firstOrFail();
        $this->assertSame($myReport->id, $rating->service_report_id);
        $this->assertSame($mine->id, $rating->customer_id);
    }

    public function test_portal_aviso_en_listado_y_detalle_y_lectura_despues_de_calificar(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCustomer();
        $report = $this->makeReport($admin, $c);

        $this->actingAs($c, 'customer');
        $this->get(route('customer.service-reports.index'))->assertOk()->assertSee('Califica este servicio');
        $this->get(route('customer.service-reports.show', $report))->assertOk()->assertSee('Califica este servicio');

        $this->post(route('customer.service-reports.rate.skip', $report))->assertRedirect(route('customer.service-reports.show', $report));
        $this->get(route('customer.service-reports.index'))->assertSee('Califica este servicio'); // omitida: sigue disponible

        $this->post(route('customer.service-reports.rate', $report), ['card_id' => SurveyCard::where('stars', 3)->first()->id]);

        $this->get(route('customer.service-reports.index'))->assertOk()->assertDontSee('Califica este servicio');
        $this->get(route('customer.service-reports.show', $report))->assertOk()->assertSee('Tu calificación');
        $this->get(route('customer.service-reports.rate', $report))->assertOk()->assertSee('Tu calificación')->assertSee('Bueno');
    }

    // ── Opiniones de servicio (admin) ───────────────────────────────────────

    public function test_listado_filtros_detalle_asignacion_y_exportacion(): void
    {
        $admin = $this->adminUser();
        $svc = app(\App\Services\SatisfactionSurveyService::class);
        $page = $this->makeServicePage('Servicio A');

        $r1 = $this->makeReport($admin, $this->makeCustomer(), ['customer_name' => 'Cliente Cinco', 'service_page_id' => $page->id]);
        $r2 = $this->makeReport($admin, $this->makeCustomer(), ['customer_name' => '=HYPERLINK("x")']);
        $q = SurveyQuestion::first();
        $svc->submit($r1, ['card_id' => SurveyCard::where('stars', 5)->first()->id, 'answers' => [$q->id => 5], 'comment' => 'Genial']);
        $svc->submit($r2, ['card_id' => SurveyCard::where('stars', 2)->first()->id]);

        $this->actingAs($admin);
        $this->get(route('admin.service-ratings.index'))->assertOk()->assertSee('Cliente Cinco')->assertSee('Opiniones de servicio');
        $this->get(route('admin.service-ratings.index', ['stars' => 5]))->assertOk()->assertSee('Cliente Cinco')->assertDontSee('HYPERLINK');
        $this->get(route('admin.service-ratings.index', ['service_page_id' => $page->id]))->assertSee('Cliente Cinco')->assertDontSee('HYPERLINK');
        $this->get(route('admin.service-ratings.index', ['service_page_id' => 'none']))->assertSee('HYPERLINK', false)->assertDontSee('Cliente Cinco');
        $this->get(route('admin.service-ratings.index', ['status' => 'skipped']))->assertOk()->assertDontSee('Cliente Cinco');
        $this->get(route('admin.service-ratings.index', ['date_from' => now()->addDay()->toDateString()]))->assertDontSee('Cliente Cinco');

        $rating = ServiceRating::where('service_report_id', $r1->id)->first();
        $this->get(route('admin.service-ratings.show', $rating))->assertOk()->assertSee('Genial')->assertSee($q->label);

        $noPage = ServiceRating::where('service_report_id', $r2->id)->first();
        $this->put(route('admin.service-ratings.assign-service', $noPage), ['service_page_id' => $page->id])->assertRedirect();
        $this->assertSame($page->id, $noPage->fresh()->service_page_id);

        $csv = $this->get(route('admin.service-ratings.export', ['stars' => 2]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Cliente', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'se neutralizan fórmulas');
        $this->assertStringNotContainsString('Cliente Cinco', $csv);
    }

    public function test_sin_permiso_no_ve_opiniones(): void
    {
        $this->actingAs($this->plainUser())->get(route('admin.service-ratings.index'))->assertRedirect(route('admin.dashboard'));
    }
}
