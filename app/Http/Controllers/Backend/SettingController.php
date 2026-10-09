<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * Reglas de las claves del grupo "catalog" (criterios y colores de las
     * etiquetas de producto + ajustes del listado). Es la fuente única: la
     * vista la usa para los atributos min/max/step de los inputs y las
     * etiquetas, y update() la usa para validar con mensajes visibles.
     *
     * type: int | decimal | color | bool | string
     */
    public const CATALOG_FIELDS = [
        'catalog.last_units_threshold' => [
            'type' => 'int', 'min' => 1, 'max' => 100, 'step' => 1,
            'label' => 'Últimas piezas: stock máximo',
        ],
        'catalog.best_seller_top_percent' => [
            'type' => 'decimal', 'min' => 0.01, 'max' => 100, 'step' => 0.01,
            'label' => 'Más vendido: % superior de la categoría',
        ],
        'catalog.best_seller_days' => [
            'type' => 'int', 'min' => 1, 'max' => 365, 'step' => 1,
            'label' => 'Más vendido: ventana en días',
        ],
        'catalog.best_seller_min_units' => [
            'type' => 'int', 'min' => 1, 'max' => 10000, 'step' => 1,
            'label' => 'Más vendido: unidades mínimas',
        ],
        'catalog.best_seller_min_products' => [
            'type' => 'int', 'min' => 1, 'max' => 1000, 'step' => 1,
            'label' => 'Más vendido: mínimo de productos con ventas',
        ],
        'catalog.new_days' => [
            'type' => 'int', 'min' => 0, 'max' => 365, 'step' => 1,
            'label' => "Nuevo: días desde la creación (0 = solo la marca 'Nuevo' manual)",
        ],
        'catalog.discount_min_percent' => [
            'type' => 'int', 'min' => 0, 'max' => 100, 'step' => 1,
            'label' => 'Descuento: porcentaje mínimo para mostrar -X%',
        ],
        'catalog.badge_color_best_seller' => [
            'type' => 'color', 'label' => 'Color de la etiqueta «Más vendido»',
        ],
        'catalog.badge_color_discount' => [
            'type' => 'color', 'label' => 'Color de la etiqueta de descuento (-X%)',
        ],
        'catalog.badge_color_last_units' => [
            'type' => 'color', 'label' => 'Color de la etiqueta «Últimas piezas»',
        ],
        'catalog.badge_color_new' => [
            'type' => 'color', 'label' => 'Color de la etiqueta «Nuevo»',
        ],
        'catalog.badge_enabled_best_seller' => [
            'type' => 'bool', 'label' => 'Mostrar la etiqueta «Más vendido»',
        ],
        'catalog.badge_enabled_discount' => [
            'type' => 'bool', 'label' => 'Mostrar la etiqueta de descuento (-X%)',
        ],
        'catalog.badge_enabled_last_units' => [
            'type' => 'bool', 'label' => 'Mostrar la etiqueta «Últimas piezas»',
        ],
        'catalog.badge_enabled_new' => [
            'type' => 'bool', 'label' => 'Mostrar la etiqueta «Nuevo»',
        ],
        'catalog.fast_shipping_label' => [
            'type' => 'string', 'min' => 3, 'max' => 40,
            'label' => 'Texto del filtro de envío rápido',
        ],
        'catalog.filter_max_visible' => [
            'type' => 'int', 'min' => 3, 'max' => 20, 'step' => 1,
            'label' => 'Opciones visibles por filtro',
        ],
        'catalog.cache_ttl_minutes' => [
            'type' => 'int', 'min' => 1, 'max' => 1440, 'step' => 1,
            'label' => 'Minutos de caché del listado',
        ],
    ];

    public function index()
    {
        // El grupo 'integraciones' (SMTP, credenciales) tiene su propio
        // módulo con manejo de encriptación — editarlo aquí lo corrompería.
        $groups = Setting::where(function ($q) {
                $q->where('group_name', '!=', 'integraciones')->orWhereNull('group_name');
            })
            ->orderBy('group_name')->orderBy('key')->get()->groupBy('group_name');

        $catalogFields = self::CATALOG_FIELDS;

        return view('admin.settings.index', compact('groups', 'catalogFields'));
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->input('values', []);

        $existingKeys = Setting::whereIn('key', array_keys($values))->pluck('key')->all();

        // Las claves catalog.* se validan TODAS antes de guardar nada: si una
        // es inválida se devuelve el formulario con el error junto al campo y
        // los valores escritos, y no se guarda ninguna (nada de descartes
        // silenciosos como en las claves más antiguas de abajo).
        $catalogErrors = [];
        $catalogClean = [];
        foreach ($values as $key => $value) {
            if (!in_array($key, $existingKeys, true) || !isset(self::CATALOG_FIELDS[$key])) {
                continue;
            }

            [$clean, $error] = self::validateCatalogValue(self::CATALOG_FIELDS[$key], $value);
            if ($error !== null) {
                $catalogErrors[$key] = [$error];
            } else {
                $catalogClean[$key] = $clean;
            }
        }

        if ($catalogErrors !== []) {
            throw ValidationException::withMessages($catalogErrors);
        }

        foreach ($values as $key => $value) {
            if (!in_array($key, $existingKeys, true)) {
                continue;
            }

            if (array_key_exists($key, $catalogClean)) {
                Setting::set($key, $catalogClean[$key], auth()->id());
                continue;
            }

            // La tasa de IVA alimenta el cálculo de precio de todo el
            // catálogo — un valor no numérico rompería esa cuenta en todo
            // el sitio, así que se descarta en vez de guardarse.
            if ($key === 'ecommerce.iva_rate' && !is_numeric($value)) {
                continue;
            }

            // A diferencia de IVA (0 es válido — "sin impuesto"), este valor
            // se usa como multiplicador/divisor de conversión — un 0 o un
            // valor no numérico rompería silenciosamente toda conversión USD.
            if ($key === 'ecommerce.usd_to_mxn_rate' && (!is_numeric($value) || (float) $value <= 0)) {
                continue;
            }

            // Multiplicador de precio de contado en product-detail -- un
            // valor fuera de 0-100 o no numérico rompería/mentiría en el
            // badge "Ahorras X%".
            if ($key === 'ecommerce.cash_discount_percent' && (!is_numeric($value) || (float) $value < 0 || (float) $value > 100)) {
                continue;
            }

            // Tarifa eléctrica que alimenta el costo operativo estimado de la
            // calculadora de bombas de calor -- debe ser positiva (0 o
            // negativo no tiene sentido físico y rompería el cálculo).
            if ($key === 'pool_calculator.tarifa_kwh' && (!is_numeric($value) || (float) $value <= 0)) {
                continue;
            }

            // COP nominal de ficha técnica -- fuera de 1-10 es un valor de
            // catálogo imposible para una bomba de calor real, se descarta
            // para no distorsionar el consumo estimado en toda la calculadora.
            if ($key === 'pool_calculator.cop_nominal' && (!is_numeric($value) || (float) $value < 1 || (float) $value > 10)) {
                continue;
            }

            // Horas de operación al día -- fuera de 1-24 no es un valor
            // físicamente posible y rompería el cálculo de consumo diario.
            if ($key === 'pool_calculator.horas_operacion_dia' && (!is_numeric($value) || (float) $value < 1 || (float) $value > 24)) {
                continue;
            }

            Setting::set($key, $value, auth()->id());
        }

        return back()->with('success', 'Configuración actualizada.');
    }

    /**
     * Valida un valor según la especificación de CATALOG_FIELDS.
     *
     * @return array{0: mixed, 1: ?string} [valor normalizado, mensaje de error|null]
     */
    private static function validateCatalogValue(array $spec, mixed $value): array
    {
        $label = $spec['label'];
        $raw = is_string($value) ? trim($value) : $value;
        $invalid = fn (string $why) => [null, "«{$label}» {$why}"];

        if (!is_scalar($raw) || $raw === '') {
            return $invalid('es obligatorio.');
        }
        $raw = (string) $raw;

        switch ($spec['type']) {
            case 'int':
                $msg = "debe ser un número entero entre {$spec['min']} y {$spec['max']}.";
                if (!preg_match('/^\d{1,6}$/', $raw)) {
                    return $invalid($msg);
                }
                $n = (int) $raw;
                if ($n < $spec['min'] || $n > $spec['max']) {
                    return $invalid($msg);
                }

                return [(string) $n, null];

            case 'decimal':
                $msg = 'debe ser un número mayor que 0 y de máximo 100.';
                if (!preg_match('/^\d{1,3}(\.\d{1,4})?$/', $raw)) {
                    return $invalid($msg);
                }
                $n = (float) $raw;
                if ($n <= 0 || $n > 100) {
                    return $invalid($msg);
                }

                return [rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.'), null];

            case 'color':
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $raw)) {
                    return $invalid('debe ser un color hexadecimal con el formato #RRGGBB (por ejemplo #FF6213).');
                }

                return [strtoupper($raw), null];

            case 'bool':
                if (!in_array($raw, ['0', '1'], true)) {
                    return $invalid('solo admite los valores activado o desactivado.');
                }

                return [$raw, null];

            case 'string':
                $len = mb_strlen($raw);
                if ($len < $spec['min'] || $len > $spec['max'] || preg_match('/[\x00-\x1F\x7F]/u', $raw)) {
                    return $invalid("debe tener entre {$spec['min']} y {$spec['max']} caracteres y no puede incluir saltos de línea.");
                }

                return [$raw, null];
        }

        return [$raw, null];
    }

    /** Luminancia relativa WCAG de un color #RRGGBB. */
    public static function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /** Relación de contraste WCAG entre dos colores #RRGGBB (1..21). */
    public static function contrastRatio(string $hexA, string $hexB): float
    {
        $a = self::relativeLuminance($hexA);
        $b = self::relativeLuminance($hexB);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Color de texto (#FFFFFF o #111111) con mayor contraste sobre el fondo
     * dado, y la relación de contraste resultante.
     *
     * @return array{color: string, ratio: float}
     */
    public static function readableTextColor(string $bgHex): array
    {
        $white = self::contrastRatio($bgHex, '#FFFFFF');
        $dark = self::contrastRatio($bgHex, '#111111');

        return $white >= $dark
            ? ['color' => '#FFFFFF', 'ratio' => $white]
            : ['color' => '#111111', 'ratio' => $dark];
    }
}
