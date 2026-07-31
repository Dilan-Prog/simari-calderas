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

            Setting::set($key, $value, auth()->id());
        }

        return back()->with('success', 'Configuración actualizada.');
    }
}
