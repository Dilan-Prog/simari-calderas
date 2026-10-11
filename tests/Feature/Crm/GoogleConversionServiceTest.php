<?php

namespace Tests\Feature\Crm;

use App\Contracts\GoogleConversionUploader;
use App\Jobs\SendGoogleConversionsJob;
use App\Models\Deal;
use App\Models\GoogleConversion;
use App\Models\GoogleConversionUpload;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\DealService;
use App\Services\Google\DataManagerUploader;
use App\Services\Google\FakeGoogleConversionUploader;
use App\Services\GoogleConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeGoogleConversionUploader $fake;
    private Pipeline $pipeline;
    private array $stage = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new FakeGoogleConversionUploader();
        $this->app->instance(GoogleConversionUploader::class, $this->fake);

        $this->pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        foreach ([
            ['nuevo', 'Nuevo', 1, false, false],
            ['cotizado', 'Cotizado', 2, false, false],
            ['ganado', 'Venta ganada', 3, true, false],
            ['perdido', 'Perdido', 4, false, true],
        ] as [$slug, $name, $order, $won, $lost]) {
            $this->stage[$slug] = PipelineStage::create([
                'pipeline_id' => $this->pipeline->id, 'name' => $name, 'slug' => $slug,
                'order' => $order, 'is_won' => $won, 'is_lost' => $lost,
            ]);
        }
    }

    private function deal(array $over = []): Deal
    {
        return app(DealService::class)->create(array_merge([
            'pipeline_id' => $this->pipeline->id, 'name' => 'Caldera', 'amount' => 10000, 'currency' => 'MXN',
            'contact_snapshot_email' => 'cliente@example.com', 'contact_snapshot_phone' => '5512345678',
        ], $over));
    }

    private function leadWithGclid(Deal $deal, string $gclid = 'GCL-1', $seenAt = null): Lead
    {
        $seen = ($seenAt ?? now()->subDays(3))->toIso8601String();

        return Lead::create([
            'source' => 'formulario', 'status' => 'calificado', 'deal_id' => $deal->id,
            'contact_email' => 'cliente@example.com', 'phone_normalized' => '+525512345678',
            'gclid' => $gclid, 'click_at' => $seen,
            'last_click' => ['gclid' => $gclid, 'seen_at' => $seen],
            'first_click' => ['gclid' => $gclid, 'seen_at' => $seen],
        ]);
    }

    public function test_hash_sha256_de_correo_y_telefono(): void
    {
        $this->assertSame(hash('sha256', 'ana@example.com'), GoogleConversionService::hashEmail('  Ana@Example.COM '));
        $this->assertSame(hash('sha256', 'juanperez@gmail.com'), GoogleConversionService::hashEmail('Juan.Perez+ads@Gmail.com'));
        $this->assertSame(hash('sha256', '+525512345678'), GoogleConversionService::hashPhone('55 1234 5678'));
        $this->assertSame(64, strlen(GoogleConversionService::hashPhone('5512345678')));
        $this->assertNull(GoogleConversionService::hashEmail('x'));
        $this->assertNull(GoogleConversionService::hashPhone('12'));
    }

    public function test_selecciona_identificador_gclid_luego_gbraid_wbraid_luego_hash(): void
    {
        $svc = app(GoogleConversionService::class);
        $deal = $this->deal();
        $now = now();

        $lead = new Lead([
            'contact_email' => 'a@b.com', 'phone_normalized' => '+525512345678',
            'last_click' => ['gclid' => 'G1', 'gbraid' => 'GB1', 'wbraid' => 'WB1', 'seen_at' => $now->copy()->subDay()->toIso8601String()],
        ]);
        $this->assertSame(['gclid', 'G1'], [$svc->resolveIdentifier($lead, $deal, $now)['type'], $svc->resolveIdentifier($lead, $deal, $now)['value']]);

        $lead->last_click = ['gbraid' => 'GB1', 'wbraid' => 'WB1', 'seen_at' => $now->toIso8601String()];
        $this->assertSame('gbraid', $svc->resolveIdentifier($lead, $deal, $now)['type']);

        $lead->last_click = ['wbraid' => 'WB1', 'seen_at' => $now->toIso8601String()];
        $this->assertSame('wbraid', $svc->resolveIdentifier($lead, $deal, $now)['type']);

        $lead->last_click = null;
        $r = $svc->resolveIdentifier($lead, $deal, $now);
        $this->assertSame('user_data', $r['type']);
        $this->assertSame(hash('sha256', 'a@b.com'), $r['user_data']['email_sha256']);
        $this->assertSame(hash('sha256', '+525512345678'), $r['user_data']['phone_sha256']);

        $bare = new Deal(['name' => 'x']);
        $this->assertSame('none', $svc->resolveIdentifier(null, $bare, $now)['type']);
    }

    public function test_gclid_vencido_cae_a_hash_y_sin_nada_se_omite(): void
    {
        $svc = app(GoogleConversionService::class);
        $deal = $this->deal();
        $old = now()->subDays(100);

        $lead = new Lead(['contact_email' => 'a@b.com', 'last_click' => ['gclid' => 'OLD', 'seen_at' => $old->toIso8601String()]]);
        $this->assertSame('user_data', $svc->resolveIdentifier($lead, new Deal(), now())['type']);

        $lead2 = new Lead(['last_click' => ['gclid' => 'OLD', 'seen_at' => $old->toIso8601String()]]);
        $r = $svc->resolveIdentifier($lead2, new Deal(), now());
        $this->assertSame('none', $r['type']);
        $this->assertTrue($r['expired']);
    }

    public function test_cotizado_y_venta_ganada_suben_conversion_una_sola_vez(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        $dealSvc = app(DealService::class);

        $dealSvc->moveStage($deal, $this->stage['cotizado']);
        $this->assertCount(1, $this->fake->uploaded);
        $this->assertSame('cotizado', $this->fake->uploaded[0]['action']);
        $this->assertSame('GCL-1', $this->fake->uploaded[0]['identifier']);

        $dealSvc->moveStage($deal->fresh(), $this->stage['ganado']);
        $this->assertCount(2, $this->fake->uploaded);
        $won = GoogleConversionUpload::where('action', 'venta_ganada')->first();
        $this->assertSame('deal-' . $deal->id . '-venta_ganada', $won->order_id);
        $this->assertSame('enviada', $won->status);
        $this->assertEquals(10000.00, (float) $won->conversion_value);
        $this->assertSame('MXN', $won->currency);

        // volver a pasar por cotizado/ganado NO duplica (anti-duplicados)
        $dealSvc->moveStage($deal->fresh(), $this->stage['cotizado']);
        $this->assertSame(2, GoogleConversionUpload::where('operation', 'upload')->count());
        $this->assertSame('convertido', Lead::first()->status);
    }

    public function test_venta_en_usd_se_convierte_a_mxn(): void
    {
        Setting::set('ecommerce.usd_to_mxn_rate', '20');
        $deal = $this->deal(['amount' => 1000, 'currency' => 'USD']);
        $this->leadWithGclid($deal);

        app(DealService::class)->moveStage($deal, $this->stage['ganado']);

        $this->assertEquals(20000.00, (float) GoogleConversionUpload::first()->conversion_value);
        $this->assertSame('MXN', GoogleConversionUpload::first()->currency);
    }

    public function test_sin_identificador_queda_sin_atribucion(): void
    {
        $deal = $this->deal(['contact_snapshot_email' => null, 'contact_snapshot_phone' => null]);
        app(DealService::class)->moveStage($deal, $this->stage['cotizado']);

        $up = GoogleConversionUpload::first();
        $this->assertSame('sin_atribucion', $up->status);
        $this->assertCount(0, $this->fake->uploaded);
    }

    public function test_fecha_de_conversion_es_posterior_al_clic(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal, 'GCL-F', now()->addHour()); // clic "en el futuro" (reloj desfasado)
        app(DealService::class)->moveStage($deal, $this->stage['cotizado']);

        $up = GoogleConversionUpload::first();
        $this->assertTrue($up->conversion_date_time->gt($up->click_at));
    }

    public function test_reintentos_con_espera_exponencial_y_falla_a_la_tercera_con_alerta(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        $this->fake->failTimes(3, 'HTTP 500');

        // La cola de pruebas es sync: la espera exponencial solo se verifica en process() directo.
        \Illuminate\Support\Facades\Queue::fake();
        app(DealService::class)->moveStage($deal, $this->stage['ganado']);
        $up = GoogleConversionUpload::first();
        $this->assertSame('pendiente', $up->status);

        $svc = app(GoogleConversionService::class);
        $svc->process($up);
        $up->refresh();
        $this->assertSame(1, $up->attempts);
        $this->assertSame('pendiente', $up->status);
        $this->assertTrue($up->next_attempt_at->between(now()->addSeconds(100), now()->addSeconds(130))); // 120 s

        $svc->process($up);
        $up->refresh();
        $this->assertSame(2, $up->attempts);
        $this->assertTrue($up->next_attempt_at->gt(now()->addSeconds(200))); // 240 s

        $this->assertSame(0, Task::count());
        $svc->process($up);
        $up->refresh();
        $this->assertSame('fallida', $up->status);
        $this->assertSame(3, $up->attempts);
        $this->assertNotNull($up->alerted_at);
        $this->assertSame(1, Task::count());
        $this->assertStringContainsString('Conversión a Google fallida', Task::first()->title);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendGoogleConversionUploadJob::class);
    }

    public function test_reintento_manual_tras_falla_envia(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        $this->fake->failTimes(3);

        app(DealService::class)->moveStage($deal, $this->stage['ganado']); // cola sync: 3 intentos seguidos
        $up = GoogleConversionUpload::first();
        $this->assertSame('fallida', $up->status);

        app(GoogleConversionService::class)->retry($up);
        $this->assertSame('enviada', $up->fresh()->status);
        $this->assertCount(4, $this->fake->uploaded);
    }

    public function test_sin_credenciales_la_conversion_queda_pendiente_sin_gastar_intentos(): void
    {
        $this->fake->configured = false;
        $deal = $this->deal();
        $this->leadWithGclid($deal);

        app(DealService::class)->moveStage($deal, $this->stage['ganado']);

        $up = GoogleConversionUpload::first();
        $this->assertSame('pendiente', $up->status);
        $this->assertSame(0, $up->attempts);
        $this->assertCount(0, $this->fake->uploaded);
    }

    public function test_cambio_de_monto_genera_restatement_con_el_mismo_order_id(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        app(DealService::class)->moveStage($deal, $this->stage['ganado']);
        $orig = GoogleConversionUpload::where('operation', 'upload')->first();
        $this->assertSame('enviada', $orig->status);

        $deal->fresh()->update(['amount' => 12500]);

        $adj = GoogleConversionUpload::where('operation', 'restatement')->first();
        $this->assertNotNull($adj);
        $this->assertSame($orig->order_id, $adj->order_id);
        $this->assertSame($orig->id, $adj->parent_upload_id);
        $this->assertEquals(12500.00, (float) $adj->conversion_value);
        $this->assertSame('enviada', $adj->status);
        $this->assertEquals($orig->conversion_date_time->timestamp, $adj->conversion_date_time->timestamp);

        // mismo monto otra vez: no duplica el ajuste
        $deal->fresh()->update(['amount' => 12500, 'notes' => 'x']);
        $this->assertSame(1, GoogleConversionUpload::where('operation', 'restatement')->count());
    }

    public function test_venta_cancelada_genera_retraction(): void
    {
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        $dealSvc = app(DealService::class);
        $dealSvc->moveStage($deal, $this->stage['ganado']);

        $dealSvc->moveStage($deal->fresh(), $this->stage['perdido']);

        $r = GoogleConversionUpload::where('operation', 'retraction')->first();
        $this->assertNotNull($r);
        $this->assertSame('deal-' . $deal->id . '-venta_ganada', $r->order_id);
        $this->assertSame('enviada', $r->status);
        $this->assertNull($r->conversion_value);
        $this->assertSame('retraction', $this->fake->uploaded[1]['operation']);
    }

    public function test_cancelar_antes_de_subir_omite_la_conversion(): void
    {
        $this->fake->configured = false;
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        $dealSvc = app(DealService::class);
        $dealSvc->moveStage($deal, $this->stage['ganado']);
        $dealSvc->moveStage($deal->fresh(), $this->stage['perdido']);

        $this->assertSame('omitida', GoogleConversionUpload::where('operation', 'upload')->first()->status);
        $this->assertSame(0, GoogleConversionUpload::where('operation', 'retraction')->count());
    }

    public function test_data_manager_uploader_con_http_fake(): void
    {
        config(['google_conversions.conversion_actions.cotizado' => '999']);
        Setting::create(['key' => 'google_ads.client_id', 'value' => 'cid', 'type' => 'string', 'group_name' => 'g', 'is_public' => false]);
        Setting::create(['key' => 'google_ads.client_secret', 'value' => 'sec', 'type' => 'string', 'group_name' => 'g', 'is_public' => false]);
        Setting::create(['key' => 'google_ads.refresh_token', 'value' => 'ref', 'type' => 'string', 'group_name' => 'g', 'is_public' => false]);
        Setting::create(['key' => 'google_ads.account_id', 'value' => '1234567890', 'type' => 'string', 'group_name' => 'g', 'is_public' => false]);

        Http::fake([
            'oauth2.googleapis.com/*'   => Http::response(['access_token' => 'tok'], 200),
            'datamanager.googleapis.com/*' => Http::response(['requestId' => 'abc'], 200),
        ]);

        $up = GoogleConversionUpload::create([
            'action' => 'cotizado', 'operation' => 'upload', 'order_id' => 'deal-1-cotizado',
            'identifier_type' => 'user_data', 'user_data' => ['email_sha256' => 'aa', 'phone_sha256' => 'bb'],
            'conversion_date_time' => now()->utc(), 'status' => 'pendiente',
        ]);

        $uploader = new DataManagerUploader();
        $this->assertTrue($uploader->isConfigured());
        $res = $uploader->upload($up);

        $this->assertTrue($res['ok']);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'datamanager')) {
                return true;
            }
            $b = $request->data();

            return $request->hasHeader('Authorization', 'Bearer tok')
                && $b['destinations'][0]['productDestinationId'] === '999'
                && $b['events'][0]['transactionId'] === 'deal-1-cotizado'
                && $b['events'][0]['userData']['userIdentifiers'][0]['emailAddress'] === 'aa';
        });
    }

    public function test_data_manager_uploader_reporta_error_http(): void
    {
        config(['google_conversions.conversion_actions.venta_ganada' => '111']);
        foreach (['client_id', 'client_secret', 'refresh_token', 'account_id'] as $k) {
            Setting::create(['key' => 'google_ads.' . $k, 'value' => 'v', 'type' => 'string', 'group_name' => 'g', 'is_public' => false]);
        }
        \Illuminate\Support\Facades\Cache::flush();
        Http::fake([
            'oauth2.googleapis.com/*'      => Http::response(['access_token' => 'tok'], 200),
            'datamanager.googleapis.com/*' => Http::response(['error' => ['message' => 'Invalid GCLID']], 400),
        ]);

        $up = GoogleConversionUpload::create([
            'action' => 'venta_ganada', 'operation' => 'upload', 'order_id' => 'o1', 'identifier_type' => 'gclid',
            'identifier' => 'X', 'conversion_date_time' => now()->utc(), 'conversion_value' => 10, 'status' => 'pendiente',
        ]);

        $res = (new DataManagerUploader())->upload($up);
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('Invalid GCLID', $res['error']);
    }

    public function test_job_legado_sigue_funcionando(): void
    {
        $legacy = GoogleConversion::create([
            'gclid' => 'LEG-1', 'conversion_name' => 'venta_ganada', 'conversion_value' => 500,
            'currency_code' => 'MXN', 'conversion_time' => now(), 'order_id' => 'LEG-ORD', 'status' => 'failed',
        ]);

        SendGoogleConversionsJob::dispatchSync($legacy);

        $this->assertSame('sent', $legacy->fresh()->status);
        $this->assertSame('LEG-ORD', GoogleConversionUpload::first()->order_id);

        // sin credenciales: se queda "stored" como antes
        $this->fake->configured = false;
        $l2 = GoogleConversion::create(['gclid' => 'LEG-2', 'conversion_name' => 'venta_ganada', 'conversion_value' => 1, 'currency_code' => 'MXN', 'conversion_time' => now(), 'status' => 'failed']);
        SendGoogleConversionsJob::dispatchSync($l2);
        $this->assertSame('stored', $l2->fresh()->status);
    }

    public function test_pantallas_y_reintento_manual(): void
    {
        $this->withoutVite();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'T', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'a' . uniqid() . '@example.com', 'password' => bcrypt('p'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
        $this->fake->failTimes(3);
        $deal = $this->deal();
        $this->leadWithGclid($deal);
        app(DealService::class)->moveStage($deal, $this->stage['ganado']);
        $up = GoogleConversionUpload::first();

        $this->actingAs($admin)->get(route('admin.google-conversions.index', ['status' => 'fallida']))
            ->assertOk()->assertSee('deal-' . $deal->id . '-venta_ganada')->assertSee('Estado: Fallida');
        $this->actingAs($admin)->get(route('admin.google-conversions.show', $up))->assertOk()->assertSee('Último error');

        $this->actingAs($admin)->post(route('admin.google-conversions.retry', $up))->assertRedirect();
        $this->assertSame('enviada', $up->fresh()->status);
    }
}
