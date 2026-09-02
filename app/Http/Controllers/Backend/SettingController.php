<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index()
    {
        // El grupo 'integraciones' (SMTP, credenciales) tiene su propio
        // módulo con manejo de encriptación — editarlo aquí lo corrompería.
        $groups = Setting::where(function ($q) {
                $q->where('group_name', '!=', 'integraciones')->orWhereNull('group_name');
            })
            ->orderBy('group_name')->orderBy('key')->get()->groupBy('group_name');

        return view('admin.settings.index', compact('groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->input('values', []);

        $existingKeys = Setting::whereIn('key', array_keys($values))->pluck('key')->all();

        foreach ($values as $key => $value) {
            if (!in_array($key, $existingKeys, true)) {
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
}
