<?php

namespace Tests\Feature\ServiceMigration;

use App\Models\Products;
use App\Models\Setting;
use App\Support\ServiceProductDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceProductDetectorTest extends TestCase
{
    use RefreshDatabase, ServiceMigrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettingCache();
    }

    public function test_nombres_que_empiezan_con_palabra_de_servicio_son_positivos(): void
    {
        foreach ([
            'Mantenimiento Preventivo Mayor de caldera',
            'Servicio de Calibración de válvulas',
            'servicios de ingeniería',
            'Reparación de quemador',
            'INSTALACION de bomba',          // sin acento y en mayúsculas
            'Calibracion de manómetro',
            'Diagnóstico de equipo',
        ] as $name) {
            $this->assertTrue(ServiceProductDetector::nameMatches($name), "Debía detectar: {$name}");
        }
    }

    public function test_nombres_de_productos_reales_son_negativos(): void
    {
        foreach ([
            'Caldera de vapor 50 BHP',
            'Kit de mantenimiento para bomba',       // contiene la palabra pero no empieza con ella
            'Válvula de servicio 2"',
            'Serviciosa 3000',                       // empieza con letras de la palabra pero no es la palabra
            '',
        ] as $name) {
            $this->assertFalse(ServiceProductDetector::nameMatches($name), "No debía detectar: {$name}");
        }
    }

    public function test_categoria_servicios_y_descendientes_cuentan_como_servicio(): void
    {
        $servicios = $this->makeCategory('Servicios');
        $hija = $this->makeCategory('Mantenimientos especiales', $servicios->id);
        $otra = $this->makeCategory('Calderas');

        $this->assertEqualsCanonicalizing([$servicios->id, $hija->id], ServiceProductDetector::serviceCategoryIds());

        $p1 = $this->makeProduct('Visita técnica', $servicios);
        $p2 = $this->makeProduct('Hora de ingeniería', $hija);
        $p3 = $this->makeProduct('Caldera 10 BHP', $otra);

        $this->assertTrue(ServiceProductDetector::isServiceProduct($p1));
        $this->assertTrue(ServiceProductDetector::isServiceProduct($p2));
        $this->assertFalse(ServiceProductDetector::isServiceProduct($p3));
    }

    public function test_patrones_configurables_por_setting(): void
    {
        $this->assertFalse(ServiceProductDetector::nameMatches('Asesoría en eficiencia'));

        Setting::updateOrCreate(['key' => 'services.detector_patterns'], ['type' => 'json', 'value' => json_encode(['Asesoría'])]);
        $this->resetSettingCache();

        $this->assertSame(['Asesoría'], ServiceProductDetector::patterns());
        $this->assertTrue(ServiceProductDetector::nameMatches('Asesoría en eficiencia'));
        $this->assertFalse(ServiceProductDetector::nameMatches('Mantenimiento general'));
    }

    public function test_setting_vacio_o_invalido_cae_a_los_valores_por_omision(): void
    {
        Setting::updateOrCreate(['key' => 'services.detector_patterns'], ['type' => 'json', 'value' => json_encode([])]);
        $this->resetSettingCache();

        $this->assertSame(ServiceProductDetector::DEFAULT_PATTERNS, ServiceProductDetector::patterns());
    }

    public function test_linea_con_service_page_id_no_es_producto_servicio(): void
    {
        $user = $this->adminUser();
        $quote = $this->makeQuote();
        $page = $this->makeServicePage();

        $viaPagina = $this->addLine($quote, 'Mantenimiento preventivo', null, ['service_page_id' => $page->id]);
        $viaProducto = $this->addLine($quote, 'Mantenimiento preventivo mayor');
        $normal = $this->addLine($quote, 'Empaque de caldera');

        $this->assertFalse(ServiceProductDetector::isServiceLine($viaPagina));
        $this->assertTrue(ServiceProductDetector::isServiceLine($viaProducto));
        $this->assertFalse(ServiceProductDetector::isServiceLine($normal));
    }

    public function test_alcances_sql_coinciden_con_la_deteccion(): void
    {
        $servicios = $this->makeCategory('Servicios');
        $this->makeProduct('Visita técnica', $servicios);
        $this->makeProduct('Mantenimiento mayor');
        $this->makeProduct('Caldera 10 BHP');

        $soloServicios = Products::query();
        ServiceProductDetector::scopeServiceProducts($soloServicios);
        $this->assertEqualsCanonicalizing(['Visita técnica', 'Mantenimiento mayor'], $soloServicios->pluck('name')->all());

        $sinServicios = Products::query();
        ServiceProductDetector::scopeExcludeServiceProducts($sinServicios);
        $this->assertSame(['Caldera 10 BHP'], $sinServicios->pluck('name')->all());
    }
}
