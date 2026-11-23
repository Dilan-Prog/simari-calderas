<?php

namespace Tests\Feature\Mobile;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceReport;
use App\Models\ServiceReportImage;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use App\Services\MobileServiceReportService;
use App\Services\ServiceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * POST /api/mobile/v1/reportes — alta de reportes desde la app móvil
 * (App\Services\MobileServiceReportService). El cuerpo imita lo que arma la
 * app (src/features/nuevo-reporte/payload.ts + flatten() de src/lib/outbox.ts):
 * multipart con campos en notación de corchetes, `fotos[]` y el archivo `firma`
 * (SVG) además del arreglo `firma[...]` — por eso se usa call() con archivos
 * separados en lugar de post().
 */
class MobileReportStoreTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/mobile/v1';

    private const TRAZO = 'M10.0 40.0 L30.5 12.0 L52.0 44.0 L70.0 20.0 M80.0 30.0 L120.0 30.0';

    private string $uploads;

    protected function setUp(): void
    {
        parent::setUp();

        // Las fotos se escriben fuera de public/ (dentro de base_path para que
        // el chroot de DomPDF las alcance al generar el PDF).
        $this->uploads = storage_path('framework/testing/mobile-report-uploads-' . uniqid());
        File::ensureDirectoryExists($this->uploads);
        config(['uploads.base_path' => $this->uploads]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->uploads);
        parent::tearDown();
    }

    // ── Fixtures (mismo estilo que MobileApiTest) ─────────────────────────────

    private function staff(string $roleName = 'Tecnico', array $permissions = [['service-reports', 'create']]): User
    {
        $role = Role::firstOrCreate(['name_role' => $roleName], ['name_role_es' => $roleName]);
        foreach ($permissions as [$module, $action]) {
            $perm = Permission::firstOrCreate(
                ['code' => "$module.$action"],
                ['name' => "$module $action", 'module' => $module, 'action' => $action]
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return User::create([
            'first_name' => 'Tec', 'last_name' => 'Nico', 'position' => $roleName, 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => 'secret-123',
            'status' => 'active', 'rfc' => 'XAXX' . random_int(100000, 999999) . 'AB1', 'role_id' => $role->id,
        ]);
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'test',
            'company' => $company, 'rfc' => 'XAXX010101000',
        ]);
    }

    private function technicalService(array $over = []): TechnicalService
    {
        $type = ServiceType::first();
        if (! $type) {
            $type = new ServiceType(['name' => 'Mantenimiento de caldera', 'is_active' => true]);
            $type->timestamps = false;
            $type->save();
        }

        return TechnicalService::create(array_merge([
            'service_number' => 'SERV-RS-' . substr(uniqid(), -6),
            'service_code' => 'SC-RS-' . substr(uniqid(), -6),
            'customer_id' => $this->customer('Servicio SA')->id, 'service_type_id' => $type->id,
            'status' => 'scheduled', 'service_date' => now()->toDateString(),
            'service_time' => '10:30:00', 'address' => 'Av. Siempre Viva 742',
        ], $over));
    }

    /** Foto JPEG con contenido distinto (fake()->image() da imágenes lisas que el dedupe perceptual junta). */
    private function photo(int $seed): UploadedFile
    {
        mt_srand($seed);
        $img = imagecreatetruecolor(320, 240);
        for ($i = 0; $i < 40; $i++) {
            $c = imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledrectangle($img, mt_rand(0, 300), mt_rand(0, 220), mt_rand(0, 320), mt_rand(0, 240), $c);
        }
        $path = tempnam(sys_get_temp_dir(), 'foto') . '.jpg';
        imagejpeg($img, $path, 90);
        imagedestroy($img);

        return new UploadedFile($path, "foto-{$seed}.jpg", 'image/jpeg', null, true);
    }

    /** Mismo SVG que escribe writeSignatureSvg() en la app. */
    private function signatureSvg(string $d = self::TRAZO): UploadedFile
    {
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="160" viewBox="0 0 320 160">'
            . '<rect width="100%" height="100%" fill="#ffffff"/>'
            . '<path d="' . $d . '" stroke="#141516" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        return UploadedFile::fake()->createWithContent('firma.svg', $svg);
    }

    /** Cuerpo como lo arma buildReportBody() (ya aplanado a strings, como el FormData). */
    private function body(array $over = []): array
    {
        return array_replace_recursive([
            'origen' => 'app_movil',
            'capturado_en' => '2026-11-20T16:45:00.000Z',
            'tecnico_id' => '999',
            'cliente' => ['nombre' => 'Hotel Fiesta', 'empresa' => 'Fiesta SA de CV', 'ubicacion' => 'León, Gto.'],
            'equipo' => [
                'tipo' => 'Caldera', 'marca' => 'Cleaver-Brooks', 'modelo' => 'CB-200', 'numero_serie' => 'SN-123',
                'capacidad' => '200 CC', 'ubicacion' => 'Cuarto de máquinas',
            ],
            'tipo_servicio' => 'Preventivo',
            'hallazgos' => "Fuga en válvula\r\nde seguridad",
            'trabajos_realizados' => 'Limpieza de quemador y cambio de empaques.',
            'recomendaciones' => 'Revisar en 3 meses.',
            'materiales' => [
                ['descripcion' => 'Empaque', 'cantidad' => '2', 'unidad' => 'pza'],
                ['descripcion' => 'Sellador', 'cantidad' => '0,5', 'unidad' => 'lt'],
                ['descripcion' => '   ', 'cantidad' => '1'],
            ],
            'lecturas' => ['presion_operacion' => '120', 'temperatura_chimenea' => '180.5', 'tds' => 'alto', 'ph' => ''],
            'fotos_meta' => [['orden' => '1', 'descripcion' => 'Antes'], ['orden' => '2', 'descripcion' => 'Después']],
            'firma' => [
                'nombre' => 'Juan Pérez', 'trazo' => self::TRAZO, 'ancho' => '320', 'alto' => '160',
                'firmado_en' => '2026-11-20T16:44:00.000Z',
            ],
        ], $over);
    }

    private function send(User $user, array $body, array $files = [], ?string $key = 'auto'): TestResponse
    {
        $headers = $this->headersFor($user) + ['Accept' => 'application/json', 'X-Outbox-Type' => 'service_report.create'];
        if ($key === 'auto') {
            $key = (string) Str::uuid();
        }
        if ($key !== null) {
            $headers['Idempotency-Key'] = $key;
            $body['idempotency_key'] = $key;
        }

        // Los guards se recuerdan entre peticiones del mismo test: sin esto el
        // segundo usuario seguiría autenticado como el primero.
        $this->app['auth']->forgetGuards();

        return $this->call('POST', self::BASE . '/reportes', $body, [], $files, $this->transformHeadersToServerVars($headers));
    }

    /**
     * El cliente de pruebas devuelve [] por cada arreglo sin archivos del
     * cuerpo y, con array_merge, eso pisaría el archivo `firma` (que la app
     * manda junto a los campos firma[...]). En PHP real el multipart llega bien.
     */
    protected function extractFilesFromDataArray(&$data)
    {
        return array_filter(parent::extractFilesFromDataArray($data), fn ($f) => $f !== []);
    }

    private function headersFor(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test-device', ['mobile'])->plainTextToken];
    }

    private function fullFiles(): array
    {
        return ['fotos' => [$this->photo(1), $this->photo(2)], 'firma' => $this->signatureSvg()];
    }

    // ── Alta ──────────────────────────────────────────────────────────────────

    public function test_creates_signed_report_with_existing_customer_photos_and_signature(): void
    {
        $tech = $this->staff();
        $customer = $this->customer('Calderas del Bajío');

        $res = $this->send($tech, $this->body([
            'cliente' => ['id' => (string) $customer->id, 'nombre' => 'Texto que se ignora'],
            'cliente_id' => (string) $customer->id,
        ]), $this->fullFiles());

        $res->assertCreated()
            ->assertJsonMissingPath('warnings')
            ->assertJsonPath('data.status', 'signed')
            ->assertJsonPath('data.is_signed', true)
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Cli Ente')
            ->assertJsonPath('data.customer.company', 'Calderas del Bajío')
            ->assertJsonPath('data.customer.rfc', 'XAXX010101000')
            ->assertJsonPath('data.technician.id', $tech->id)
            ->assertJsonPath('data.created_by.id', $tech->id)
            ->assertJsonPath('data.service_type_key', 'maintenance_preventive')
            ->assertJsonPath('data.form_behavior', 'activity')
            ->assertJsonPath('data.activity.content', 'Limpieza de quemador y cambio de empaques.')
            ->assertJsonPath('data.activity.systems_checked', ['calderas'])
            ->assertJsonPath('data.observations', "Fuga en válvula\nde seguridad")
            ->assertJsonPath('data.recommendations', 'Revisar en 3 meses.')
            ->assertJsonPath('data.signature.name', 'Juan Pérez')
            ->assertJsonPath('data.signature.position', 'Cliente')
            ->assertJsonPath('data.images.0.caption', 'Antes')
            ->assertJsonPath('data.images.1.caption', 'Después')
            ->assertJsonCount(2, 'data.images');

        $this->assertMatchesRegularExpression('/^RPT-\d{4}-\d{4}$/', $res->json('data.folio'));
        $this->assertNotNull($res->json('data.id'));
        $this->assertNotNull($res->json('data.pdf_url'));

        // La firma se rasteriza a PNG (lo que pintan DomPDF y el <Image> de RN).
        $image = $res->json('data.signature.image');
        $this->assertStringStartsWith('data:image/png;base64,', $image);
        $info = getimagesizefromstring(base64_decode(substr($image, strlen('data:image/png;base64,'))));
        $this->assertSame('image/png', $info['mime']);
        $this->assertGreaterThan(100, $info[0]);

        $report = ServiceReport::findOrFail($res->json('data.id'));
        $this->assertSame('app_movil', $report->origin);
        $this->assertSame(6, (int) $report->current_step);
        $this->assertSame($tech->id, $report->assigned_user_id);
        $this->assertSame('2026-11-20', $report->service_date->toDateString());
        $this->assertSame(47, (int) $report->week_number);
        $this->assertSame('León, Gto.', $report->location);
        $this->assertTrue($report->captured_at->equalTo(Carbon::parse('2026-11-20T16:45:00Z')));
        $this->assertTrue($report->signed_at->equalTo(Carbon::parse('2026-11-20T16:44:00Z')));
        $this->assertSame('Cleaver-Brooks', $report->equipment['marca']);
        $this->assertSame('SN-123', $report->equipment['numero_serie']);
        // MySQL reordena las llaves de un JSON: se compara sin importar el orden.
        $readings = $report->readings;
        ksort($readings);
        $this->assertSame(['presion_operacion' => 120, 'tds' => 'alto', 'temperatura_chimenea' => 180.5], $readings);
        $this->assertEquals([
            ['descripcion' => 'Empaque', 'cantidad' => 2, 'unidad' => 'pza'],
            ['descripcion' => 'Sellador', 'cantidad' => 0.5, 'unidad' => 'lt'],
        ], $report->materials);

        foreach ($report->images as $img) {
            $this->assertFileExists($this->uploads . '/' . $img->path);
            $this->assertStringStartsWith("service-reports/{$report->id}/", $img->path);
        }
    }

    public function test_creates_report_with_customer_by_name(): void
    {
        $res = $this->send($this->staff(), $this->body(), $this->fullFiles())->assertCreated();

        $res->assertJsonPath('data.customer.id', null)
            ->assertJsonPath('data.customer.name', 'Hotel Fiesta')
            ->assertJsonPath('data.customer.company', 'Fiesta SA de CV')
            ->assertJsonPath('data.location', 'León, Gto.')
            ->assertJsonPath('data.status', 'signed');

        // Sin ubicación del cliente se usa la del equipo.
        $body = $this->body();
        unset($body['cliente']['ubicacion']);
        $this->send($this->staff(), $body)->assertCreated()->assertJsonPath('data.location', 'Cuarto de máquinas');
    }

    public function test_without_signature_report_is_completed(): void
    {
        $body = $this->body();
        unset($body['firma']);

        $res = $this->send($this->staff(), $body)->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.signature', null);

        $this->assertSame(5, (int) ServiceReport::find($res->json('data.id'))->current_step);
    }

    public function test_signature_from_svg_file_when_trazo_missing(): void
    {
        $body = $this->body();
        unset($body['firma']['trazo']);

        $res = $this->send($this->staff(), $body, ['firma' => $this->signatureSvg('m10 10 l40 30 h20 v-15 z')])
            ->assertCreated()->assertJsonPath('data.status', 'signed');

        $this->assertStringStartsWith('data:image/png;base64,', $res->json('data.signature.image'));
    }

    public function test_unreadable_signature_saves_report_without_it_and_warns(): void
    {
        $this->send($this->staff(), $this->body(['firma' => ['trazo' => 'xyz']]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('warnings.0', 'No se pudo interpretar la firma; el reporte se guardó sin firma.');
    }

    public function test_maps_service_types_including_arranque(): void
    {
        $tech = $this->staff();
        $cases = [
            'Preventivo' => 'maintenance_preventive',
            'Correctivo' => 'maintenance_corrective',
            'Inspección' => 'inspection',
            'Inspeccion' => 'inspection',
            'Arranque' => 'startup',
            'inspection' => 'inspection',                            // key directa
            'Mantenimiento Correctivo' => 'maintenance_corrective',  // label
        ];

        foreach ($cases as $tipo => $key) {
            $res = $this->send($tech, $this->body(['tipo_servicio' => $tipo]))->assertCreated();
            $this->assertSame($key, $res->json('data.service_type_key'), $tipo);
        }

        $this->send($tech, $this->body(['tipo_servicio' => 'Arranque']))
            ->assertCreated()
            ->assertJsonPath('data.service_type', 'Arranque')
            ->assertJsonPath('data.form_behavior', 'activity');
    }

    // ── Idempotencia ──────────────────────────────────────────────────────────

    public function test_same_idempotency_key_returns_same_report_with_200(): void
    {
        $tech = $this->staff();
        $key = (string) Str::uuid();

        $first = $this->send($tech, $this->body(), $this->fullFiles(), $key)->assertCreated();
        $this->assertNull($first->headers->get('Idempotent-Replayed'));

        $again = $this->send($tech, $this->body(), $this->fullFiles(), $key);
        $again->assertOk()->assertHeader('Idempotent-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'))
            ->assertJsonPath('data.folio', $first->json('data.folio'));

        // Incluso si el reintento viniera inválido, se responde el ya creado.
        $this->send($tech, ['tipo_servicio' => 'nada'], [], $key)->assertOk()->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, ServiceReport::count());
        $this->assertSame(2, ServiceReportImage::count());
        $this->assertSame($key, ServiceReport::first()->idempotency_key);
    }

    public function test_key_only_in_body_is_accepted(): void
    {
        $key = (string) Str::uuid();
        $headers = $this->headersFor($this->staff()) + ['Accept' => 'application/json'];

        $this->call('POST', self::BASE . '/reportes', $this->body(['idempotency_key' => $key]), [], [], $this->transformHeadersToServerVars($headers))
            ->assertCreated();

        $this->assertSame($key, ServiceReport::first()->idempotency_key);
    }

    public function test_missing_idempotency_key_returns_422(): void
    {
        $this->send($this->staff(), $this->body(), [], null)
            ->assertStatus(422)->assertJsonValidationErrors('idempotency_key');

        $this->send($this->staff(), $this->body(), [], str_repeat('a', 101))
            ->assertStatus(422)->assertJsonValidationErrors('idempotency_key');

        $this->assertSame(0, ServiceReport::count());
    }

    public function test_key_used_by_another_user_returns_422(): void
    {
        $key = (string) Str::uuid();
        $this->send($this->staff(), $this->body(), [], $key)->assertCreated();

        $this->send($this->staff('Otro'), $this->body(), [], $key)
            ->assertStatus(422)->assertJsonValidationErrors('idempotency_key');
    }

    public function test_unique_race_returns_existing_report(): void
    {
        // Simula la carrera: la key ya está en BD cuando create() inserta
        // (otra petición ganó entre findByKey() y el INSERT).
        $tech = $this->staff();
        $key = (string) Str::uuid();
        $existing = $this->send($tech, $this->body(), [], $key)->assertCreated()->json('data.id');

        $svc = app(MobileServiceReportService::class);
        $request = Request::create('/x', 'POST', $this->body());
        $validated = $svc->validate($request);

        [$report, $warnings, $replayed] = $svc->create($validated, $request, $tech, $key);

        $this->assertTrue($replayed);
        $this->assertSame($existing, $report->id);
        $this->assertSame([], $warnings);
        $this->assertSame(1, ServiceReport::count());
    }

    // ── Permisos y validación ─────────────────────────────────────────────────

    public function test_requires_service_reports_create_permission(): void
    {
        $viewer = $this->staff('Solo lectura', [['service-reports', 'view']]);
        $this->send($viewer, $this->body())->assertStatus(403);

        $this->app['auth']->forgetGuards();
        $this->call('POST', self::BASE . '/reportes', $this->body(), [], [], ['HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(401);

        $this->assertSame(0, ServiceReport::count());

        // El admin pasa sin permisos explícitos.
        $this->send($this->staff('Administrador', []), $this->body())->assertCreated();
    }

    public function test_validation_errors(): void
    {
        $tech = $this->staff();

        $body = $this->body();
        unset($body['trabajos_realizados']);
        $this->send($tech, $body)->assertStatus(422)->assertJsonValidationErrors('trabajos_realizados');
        $this->send($tech, $this->body(['trabajos_realizados' => '  <b> </b> ']))->assertStatus(422)->assertJsonValidationErrors('trabajos_realizados');

        $this->send($tech, $this->body(['tipo_servicio' => 'Limpieza profunda']))->assertStatus(422)->assertJsonValidationErrors('tipo_servicio');
        // Tipo existente pero de mediciones: la app no captura mediciones.
        $this->send($tech, $this->body(['tipo_servicio' => 'chemical_analysis']))->assertStatus(422)->assertJsonValidationErrors('tipo_servicio');

        $this->send($tech, $this->body(['cliente_id' => '999999']))->assertStatus(422)->assertJsonValidationErrors('cliente_id');
        $this->send($tech, $this->body(['cliente' => ['nombre' => '']]))->assertStatus(422)->assertJsonValidationErrors('cliente.nombre');
        $this->send($tech, $this->body(['firma' => ['nombre' => '']]))->assertStatus(422)->assertJsonValidationErrors('firma.nombre');
        $this->send($tech, $this->body(['hallazgos' => str_repeat('x', 10001)]))->assertStatus(422)->assertJsonValidationErrors('hallazgos');
        $this->send($tech, $this->body(), ['fotos' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]])
            ->assertStatus(422)->assertJsonValidationErrors('fotos.0');

        $this->assertSame(0, ServiceReport::count());
    }

    public function test_text_is_sanitized(): void
    {
        $res = $this->send($this->staff(), $this->body([
            'trabajos_realizados' => "<script>alert(1)</script>Cambio de <b>bomba</b>\x07",
            'equipo' => ['marca' => "  Cleaver   <i>Brooks</i> "],
        ]))->assertCreated();

        $res->assertJsonPath('data.activity.content', 'alert(1)Cambio de bomba');
        $this->assertSame('Cleaver Brooks', ServiceReport::find($res->json('data.id'))->equipment['marca']);
    }

    // ── Servicio ──────────────────────────────────────────────────────────────

    public function test_servicio_id_derives_customer_from_service(): void
    {
        $service = $this->technicalService();
        $other = $this->customer('Otro');

        $res = $this->send($this->staff(), $this->body([
            'servicio_id' => (string) $service->id,
            'cliente_id' => (string) $other->id,
            'cliente' => ['id' => (string) $other->id, 'nombre' => 'Otro'],
        ]))->assertCreated();

        $res->assertJsonPath('data.technical_service.id', $service->id)
            ->assertJsonPath('data.customer.id', $service->customer_id)
            ->assertJsonPath('data.customer.company', 'Servicio SA')
            ->assertJsonPath('warnings.0', 'El cliente se tomó del servicio vinculado (no coincidía con el elegido en la app).');

        // Servicio cancelado (o inexistente): mismas reglas que el panel.
        $cancelled = $this->technicalService(['status' => 'cancelled']);
        $this->send($this->staff(), $this->body(['servicio_id' => (string) $cancelled->id]))
            ->assertStatus(422)->assertJsonValidationErrors('servicio_id');
    }

    // ── Fotos ─────────────────────────────────────────────────────────────────

    public function test_photos_that_cannot_be_processed_are_reported_as_warnings(): void
    {
        $real = app(ServiceReportService::class);
        $calls = 0;
        $this->partialMock(ServiceReportService::class, function ($mock) use ($real, &$calls) {
            $mock->shouldReceive('saveImages')->andReturnUsing(function ($report, $files) use ($real, &$calls) {
                return ++$calls === 1 ? 1 : $real->saveImages($report, $files);
            });
        });

        $res = $this->send($this->staff(), $this->body(), $this->fullFiles())->assertCreated();

        $res->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.caption', 'Después')
            ->assertJsonPath('warnings.0', 'No se pudo procesar la foto 1 (formato no compatible o archivo dañado); el reporte se guardó sin ella.');
    }

    // ── PDF ───────────────────────────────────────────────────────────────────

    public function test_pdf_of_created_report_renders_with_signature(): void
    {
        $res = $this->send($this->staff(), $this->body(['tipo_servicio' => 'Arranque']), $this->fullFiles())->assertCreated();
        $report = ServiceReport::findOrFail($res->json('data.id'));
        $pdfUrl = $res->json('data.pdf_url');

        $this->app['auth']->forgetGuards();
        $pdf = $this->get($pdfUrl);
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $content = $pdf->getContent();
        $this->assertStringStartsWith('%PDF', $content);

        // La firma realmente se dibuja: el mismo PDF sin firma tiene menos imágenes.
        $withSignature = $this->countPdfImages($content);
        $report->update(['signature_data' => null]);
        $without = $this->countPdfImages($this->get($pdfUrl)->getContent());
        // PNG con transparencia = imagen + máscara (SMask): 2 objetos más que sin firma.
        $this->assertSame($without + 2, $withSignature);
    }

    private function countPdfImages(string $pdf): int
    {
        return preg_match_all('#/Subtype\s*/Image#', $pdf);
    }

    // ── Trazo SVG ─────────────────────────────────────────────────────────────

    public function test_path_parser_handles_absolute_relative_and_implicit_commands(): void
    {
        $svc = app(MobileServiceReportService::class);

        $this->assertSame([[[10.0, 20.0], [30.0, 40.0]], [[5.0, 5.0], [15.0, 5.0], [15.0, 0.0], [5.0, 5.0]]],
            $svc->parsePath('M10 20 L30 40 m-25 -35 h10 v-5 z'));
        $this->assertSame([[[1.0, 2.0], [3.0, 4.0], [5.0, 6.0]]], $svc->parsePath('M1,2 3,4 5,6'));
        $this->assertSame([], $svc->parsePath('garbage'));
    }
}
