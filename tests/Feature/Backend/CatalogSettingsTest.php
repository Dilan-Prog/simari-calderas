<?php

namespace Tests\Feature\Backend;

use App\Http\Controllers\Backend\SettingController;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Catalog\CatalogSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Sección "Catálogo y etiquetas" de Configuración del Sitio (WP-5): carga de
 * la pantalla, guardado tipado, validación con errores visibles y que las
 * claves ecommerce.* sigan funcionando igual.
 *
 * Auth: mismo patrón que WebhookControllerTest (rol 'Administrador').
 */
class CatalogSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/admin/configuracion-sitio';

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearSettingCache();
    }

    private function clearSettingCache(): void
    {
        $prop = new ReflectionProperty(Setting::class, 'cache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role'    => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        return User::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'position'   => 'Administrador',
            'phone'      => '5555555555',
            'email'      => 'admin-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    /** Valores válidos distintos de los defaults para todas las claves catalog.*. */
    private function validValues(): array
    {
        return [
            'catalog.last_units_threshold'      => '5',
            'catalog.best_seller_top_percent'   => '12.5',
            'catalog.best_seller_days'          => '60',
            'catalog.best_seller_min_units'     => '4',
            'catalog.best_seller_min_products'  => '2',
            'catalog.new_days'                  => '30',
            'catalog.discount_min_percent'      => '5',
            'catalog.badge_color_best_seller'   => '#aa11bb',
            'catalog.badge_color_discount'      => '#112233',
            'catalog.badge_color_last_units'    => '#FFEE00',
            'catalog.badge_color_new'           => '#00ff00',
            'catalog.badge_enabled_best_seller' => '0',
            'catalog.badge_enabled_discount'    => '1',
            'catalog.badge_enabled_last_units'  => '0',
            'catalog.badge_enabled_new'         => '1',
            'catalog.fast_shipping_label'       => 'Entrega rápida',
            'catalog.filter_max_visible'        => '8',
            'catalog.cache_ttl_minutes'         => '30',
        ];
    }

    private function save(User $admin, array $values)
    {
        return $this->actingAs($admin)->from(self::URL)->put(self::URL, ['values' => $values]);
    }

    // --- pantalla ------------------------------------------------------

    public function test_settings_screen_shows_catalog_group_with_all_keys(): void
    {
        Setting::create(['key' => 'footer.email', 'value' => 'a@b.mx', 'type' => 'string', 'group_name' => 'footer']);

        $response = $this->actingAs($this->adminUser())->get(self::URL);

        $response->assertOk();
        $response->assertSee('Catálogo y etiquetas');
        foreach (array_keys(SettingController::CATALOG_FIELDS) as $key) {
            $response->assertSee('name="values[' . $key . ']"', false);
        }
        $response->assertSee('Últimas piezas: stock máximo');
        $response->assertSee('Más vendido: % superior de la categoría');
        $response->assertSee('Minutos de caché del listado');
        // Inputs numéricos con rangos y color con vista previa.
        $response->assertSee('type="number"', false);
        $response->assertSee('data-color-preview', false);
        // Los otros grupos siguen apareciendo con su render genérico.
        $response->assertSee('Footer');
        $response->assertSee('name="values[footer.email]"', false);
    }

    public function test_every_catalog_field_in_spec_is_seeded_and_matches_defaults(): void
    {
        $this->assertEqualsCanonicalizing(
            array_map(fn ($k) => 'catalog.' . $k, array_keys(CatalogSettings::DEFAULTS)),
            array_keys(SettingController::CATALOG_FIELDS)
        );
        foreach (array_keys(SettingController::CATALOG_FIELDS) as $key) {
            $this->assertDatabaseHas('settings', ['key' => $key, 'group_name' => 'catalog']);
        }
    }

    // --- guardado ------------------------------------------------------

    public function test_valid_values_are_saved_with_correct_types(): void
    {
        $admin = $this->adminUser();

        $response = $this->save($admin, $this->validValues());

        $response->assertRedirect(self::URL);
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $this->clearSettingCache();

        $this->assertSame(5, Setting::get('catalog.last_units_threshold'));
        $this->assertSame(12.5, Setting::get('catalog.best_seller_top_percent'));
        $this->assertSame(60, Setting::get('catalog.best_seller_days'));
        $this->assertSame(4, Setting::get('catalog.best_seller_min_units'));
        $this->assertSame(2, Setting::get('catalog.best_seller_min_products'));
        $this->assertSame(30, Setting::get('catalog.new_days'));
        $this->assertSame(5, Setting::get('catalog.discount_min_percent'));
        // Colores normalizados a mayúsculas.
        $this->assertSame('#AA11BB', Setting::get('catalog.badge_color_best_seller'));
        $this->assertSame('#112233', Setting::get('catalog.badge_color_discount'));
        $this->assertSame('#FFEE00', Setting::get('catalog.badge_color_last_units'));
        $this->assertSame('#00FF00', Setting::get('catalog.badge_color_new'));
        $this->assertFalse(Setting::get('catalog.badge_enabled_best_seller'));
        $this->assertTrue(Setting::get('catalog.badge_enabled_discount'));
        $this->assertFalse(Setting::get('catalog.badge_enabled_last_units'));
        $this->assertTrue(Setting::get('catalog.badge_enabled_new'));
        $this->assertSame('Entrega rápida', Setting::get('catalog.fast_shipping_label'));
        $this->assertSame(8, Setting::get('catalog.filter_max_visible'));
        $this->assertSame(30, Setting::get('catalog.cache_ttl_minutes'));

        // El wrapper tipado del catálogo ve lo mismo.
        $this->assertSame(5, CatalogSettings::get('last_units_threshold'));
        $this->assertFalse(CatalogSettings::get('badge_enabled_best_seller'));
        $this->assertSame(1, Setting::where('key', 'catalog.cache_ttl_minutes')->count());
    }

    public function test_boundary_values_are_accepted(): void
    {
        $values = array_merge($this->validValues(), [
            'catalog.last_units_threshold'     => '100',
            'catalog.best_seller_top_percent'  => '100',
            'catalog.best_seller_days'         => '365',
            'catalog.best_seller_min_units'    => '10000',
            'catalog.best_seller_min_products' => '1000',
            'catalog.new_days'                 => '0',
            'catalog.discount_min_percent'     => '0',
            'catalog.filter_max_visible'       => '3',
            'catalog.cache_ttl_minutes'        => '1440',
            'catalog.fast_shipping_label'      => 'abc',
        ]);

        $this->save($this->adminUser(), $values)->assertSessionHasNoErrors();

        $this->clearSettingCache();
        $this->assertSame(100, Setting::get('catalog.last_units_threshold'));
        $this->assertSame(100.0, Setting::get('catalog.best_seller_top_percent'));
        $this->assertSame(0, Setting::get('catalog.new_days'));
        $this->assertSame(0, Setting::get('catalog.discount_min_percent'));
        $this->assertSame(1440, Setting::get('catalog.cache_ttl_minutes'));
    }

    // --- validación ----------------------------------------------------

    public static function invalidProvider(): array
    {
        return [
            'umbral de últimas piezas 0'      => ['catalog.last_units_threshold', '0'],
            'umbral de últimas piezas 101'    => ['catalog.last_units_threshold', '101'],
            'días más vendido 0'              => ['catalog.best_seller_days', '0'],
            'días más vendido 366'            => ['catalog.best_seller_days', '366'],
            'unidades mínimas 0'              => ['catalog.best_seller_min_units', '0'],
            'unidades mínimas 10001'          => ['catalog.best_seller_min_units', '10001'],
            'productos mínimos 0'             => ['catalog.best_seller_min_products', '0'],
            'productos mínimos 1001'          => ['catalog.best_seller_min_products', '1001'],
            'nuevo días negativo'             => ['catalog.new_days', '-1'],
            'nuevo días 366'                  => ['catalog.new_days', '366'],
            'descuento mínimo 101'            => ['catalog.discount_min_percent', '101'],
            'descuento mínimo negativo'       => ['catalog.discount_min_percent', '-5'],
            'filtros visibles 2'              => ['catalog.filter_max_visible', '2'],
            'filtros visibles 21'             => ['catalog.filter_max_visible', '21'],
            'caché 0'                         => ['catalog.cache_ttl_minutes', '0'],
            'caché 1441'                      => ['catalog.cache_ttl_minutes', '1441'],
            'entero decimal'                  => ['catalog.best_seller_days', '9.5'],
            'entero texto'                    => ['catalog.best_seller_days', 'abc'],
            'entero vacío'                    => ['catalog.best_seller_days', ''],
            'porcentaje 0'                    => ['catalog.best_seller_top_percent', '0'],
            'porcentaje 0.0'                  => ['catalog.best_seller_top_percent', '0.0'],
            'porcentaje 100.5'                => ['catalog.best_seller_top_percent', '100.5'],
            'porcentaje 101'                  => ['catalog.best_seller_top_percent', '101'],
            'porcentaje negativo'             => ['catalog.best_seller_top_percent', '-3'],
            'porcentaje texto'                => ['catalog.best_seller_top_percent', 'diez'],
            'porcentaje notación científica'  => ['catalog.best_seller_top_percent', '1e1'],
            'color sin #'                     => ['catalog.badge_color_new', '2E7D32'],
            'color corto'                     => ['catalog.badge_color_new', '#FFF'],
            'color no hex'                    => ['catalog.badge_color_discount', '#GGGGGG'],
            'color largo'                     => ['catalog.badge_color_last_units', '#FFC10700'],
            'color vacío'                     => ['catalog.badge_color_best_seller', ''],
            'booleano inválido'               => ['catalog.badge_enabled_new', 'yes'],
            'label demasiado corto'           => ['catalog.fast_shipping_label', 'ab'],
            'label demasiado largo'           => ['catalog.fast_shipping_label', str_repeat('x', 41)],
            'label vacío'                     => ['catalog.fast_shipping_label', ''],
            'label con salto de línea'        => ['catalog.fast_shipping_label', "Envío\nrápido"],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid_value_is_rejected_with_visible_error_and_nothing_is_saved(string $key, string $bad): void
    {
        $admin = $this->adminUser();
        $before = Setting::where('group_name', 'catalog')->orderBy('key')->pluck('value', 'key')->all();

        // Un campo válido distinto viaja en la misma petición: tampoco debe guardarse.
        $values = array_merge($this->validValues(), [$key => $bad]);

        $response = $this->save($admin, $values);

        $response->assertRedirect(self::URL);
        $response->assertSessionHasErrors($key);
        $message = session('errors')->first($key);
        $this->assertStringContainsString('«' . SettingController::CATALOG_FIELDS[$key]['label'] . '»', $message);

        $after = Setting::where('group_name', 'catalog')->orderBy('key')->pluck('value', 'key')->all();
        $this->assertSame($before, $after);
    }

    public function test_error_is_shown_next_to_field_and_typed_values_are_kept(): void
    {
        $admin = $this->adminUser();
        $values = array_merge($this->validValues(), [
            'catalog.badge_color_new'  => '#ZZZ',
            'catalog.best_seller_days' => '999',
        ]);

        $this->actingAs($admin)->from(self::URL)->put(self::URL, ['values' => $values]);
        $page = $this->actingAs($admin)->get(self::URL);

        $page->assertOk();
        // Conserva lo escrito (inválido y válido).
        $page->assertSee('value="#ZZZ"', false);
        $page->assertSee('value="999"', false);
        $page->assertSee('value="12.5"', false);
        $page->assertSee('value="Entrega rápida"', false);
        // Error junto a cada campo inválido, enlazado por aria-describedby.
        $page->assertSee('id="setting_catalog_badge_color_new_error"', false);
        $page->assertSee('id="setting_catalog_best_seller_days_error"', false);
        $page->assertSee('debe ser un color hexadecimal con el formato #RRGGBB');
        $page->assertSee('debe ser un número entero entre 1 y 365');
        $page->assertSee('aria-invalid="true"', false);
        $page->assertDontSee('id="setting_catalog_new_days_error"', false);
    }

    public function test_json_request_gets_422_with_errors(): void
    {
        $response = $this->actingAs($this->adminUser())->putJson(self::URL, [
            'values' => ['catalog.last_units_threshold' => '0'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['catalog.last_units_threshold']);
    }

    public function test_partial_submission_validates_only_submitted_keys(): void
    {
        $this->save($this->adminUser(), ['catalog.last_units_threshold' => '7'])
            ->assertSessionHasNoErrors();

        $this->clearSettingCache();
        $this->assertSame(7, Setting::get('catalog.last_units_threshold'));
        $this->assertSame(90, Setting::get('catalog.best_seller_days'));
    }

    public function test_unknown_catalog_keys_do_not_create_rows(): void
    {
        $countBefore = Setting::count();

        $this->save($this->adminUser(), [
            'catalog.inventada'            => 'x',
            'otra.clave_inexistente'       => 'y',
            'catalog.last_units_threshold' => '4',
        ])->assertSessionHasNoErrors();

        $this->assertSame($countBefore, Setting::count());
        $this->assertDatabaseMissing('settings', ['key' => 'catalog.inventada']);
        $this->assertDatabaseMissing('settings', ['key' => 'otra.clave_inexistente']);
    }

    public function test_saving_catalog_setting_fires_model_saved_event(): void
    {
        $fired = [];
        Setting::saved(function (Setting $s) use (&$fired) {
            $fired[] = $s->key;
        });

        $this->save($this->adminUser(), ['catalog.cache_ttl_minutes' => '15']);

        $this->assertContains('catalog.cache_ttl_minutes', $fired);
    }

    // --- ecommerce.* sigue igual --------------------------------------

    public function test_ecommerce_keys_keep_their_behaviour(): void
    {
        $admin = $this->adminUser();
        Setting::firstOrCreate(['key' => 'ecommerce.iva_rate'], ['value' => '16', 'type' => 'decimal', 'group_name' => 'ecommerce']);
        Setting::firstOrCreate(['key' => 'ecommerce.cash_discount_percent'], ['value' => '0', 'type' => 'decimal', 'group_name' => 'ecommerce']);
        Setting::where('key', 'ecommerce.iva_rate')->update(['value' => '16']);
        Setting::where('key', 'ecommerce.cash_discount_percent')->update(['value' => '0']);

        // Válido: se guarda.
        $this->save($admin, ['ecommerce.iva_rate' => '8'])->assertSessionHasNoErrors();
        $this->clearSettingCache();
        $this->assertSame(8.0, Setting::get('ecommerce.iva_rate'));

        // Inválido: se descarta como siempre (sin error ni cambio) y no
        // bloquea el guardado de claves catalog.* válidas de la misma petición.
        $this->save($admin, [
            'ecommerce.iva_rate'              => 'abc',
            'ecommerce.cash_discount_percent' => '150',
            'catalog.last_units_threshold'    => '9',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->clearSettingCache();
        $this->assertSame(8.0, Setting::get('ecommerce.iva_rate'));
        $this->assertSame(0.0, Setting::get('ecommerce.cash_discount_percent'));
        $this->assertSame(9, Setting::get('catalog.last_units_threshold'));
    }

    // --- contraste -----------------------------------------------------

    public function test_contrast_helpers_are_correct(): void
    {
        $this->assertEqualsWithDelta(21.0, SettingController::contrastRatio('#000000', '#FFFFFF'), 0.001);
        $this->assertEqualsWithDelta(1.0, SettingController::contrastRatio('#FF6213', '#FF6213'), 0.001);

        // Ámbar claro -> texto oscuro; verde/rojo oscuros -> texto blanco.
        $amber = SettingController::readableTextColor('#FFC107');
        $this->assertSame('#111111', $amber['color']);
        $this->assertGreaterThanOrEqual(4.5, $amber['ratio']);

        $green = SettingController::readableTextColor('#2E7D32');
        $this->assertSame('#FFFFFF', $green['color']);
        $this->assertEqualsWithDelta(5.1, $green['ratio'], 0.1);

        $red = SettingController::readableTextColor('#C62828');
        $this->assertSame('#FFFFFF', $red['color']);
        $this->assertEqualsWithDelta(5.6, $red['ratio'], 0.1);

        // Naranja de marca: blanco solo da ~3:1, el texto oscuro mucho más.
        $orange = SettingController::readableTextColor('#FF6213');
        $this->assertSame('#111111', $orange['color']);

        // Con texto #111111/#FFFFFF solo una franja de grises medios (~#777)
        // se queda justo por debajo de 4.5:1: ahí la vista avisa.
        $this->assertGreaterThanOrEqual(4.5, SettingController::readableTextColor('#808080')['ratio']);
        $this->assertGreaterThanOrEqual(4.5, SettingController::readableTextColor('#767676')['ratio']);
        $this->assertLessThan(4.5, SettingController::readableTextColor('#777777')['ratio']);
    }

    public function test_page_shows_preview_with_computed_text_color(): void
    {
        $page = $this->actingAs($this->adminUser())->get(self::URL);

        $page->assertSee('background:#FFC107;color:#111111', false);
        $page->assertSee('background:#C62828;color:#FFFFFF', false);
        $page->assertSee('Contraste del texto automático');
        $page->assertDontSee('catalog-settings-hint is-warning', false);
    }

    public function test_page_warns_when_automatic_text_contrast_is_below_minimum(): void
    {
        $admin = $this->adminUser();
        $this->save($admin, ['catalog.badge_color_new' => '#777777'])->assertSessionHasNoErrors();

        $page = $this->actingAs($admin)->get(self::URL);

        $page->assertSee('Atención: el contraste del texto de la etiqueta es de 4.48:1 y no alcanza el mínimo de 4.5:1');
    }
}
