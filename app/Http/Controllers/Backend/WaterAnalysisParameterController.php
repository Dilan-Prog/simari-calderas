<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WaterAnalysisParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuración ERP > Parámetros de análisis de agua. El personal fija los valores
 * mínimo y máximo de cada parámetro; el portal del cliente los muestra y evalúa con ellos.
 */
class WaterAnalysisParameterController extends Controller
{
    public function index(): View
    {
        return view('admin.erp-settings.water-analysis', [
            'parameters' => WaterAnalysisParameter::ordered()->get(),
        ]);
    }

    public function update(Request $request, string $parameter): RedirectResponse
    {
        $param = WaterAnalysisParameter::findOrFail($parameter);

        // Se acepta la coma decimal y el campo vacío (= sin límite).
        foreach (['min_value', 'max_value'] as $f) {
            if (is_string($request->input($f))) {
                $request->merge([$f => str_replace(',', '.', trim($request->input($f)))]);
            }
        }

        $maxLimit = $param->code === 'ph' ? 14 : 9999999;

        $validator = validator($request->all(), [
            'name'      => ['required', 'string', 'max:120'],
            'unit'      => ['nullable', 'string', 'max:30'],
            'min_value' => ['nullable', 'numeric', 'min:0', "max:{$maxLimit}"],
            'max_value' => ['nullable', 'numeric', 'min:0', "max:{$maxLimit}"],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required'   => 'Indica el nombre del parámetro.',
            'name.max'        => 'El nombre no puede superar 120 caracteres.',
            'unit.max'        => 'La unidad no puede superar 30 caracteres.',
            'min_value.numeric' => 'El valor mínimo debe ser un número.',
            'min_value.min'   => 'El valor mínimo no puede ser negativo.',
            'min_value.max'   => $param->code === 'ph' ? 'El valor mínimo del pH no puede ser mayor a 14.' : 'El valor mínimo es demasiado grande.',
            'max_value.numeric' => 'El valor máximo debe ser un número.',
            'max_value.min'   => 'El valor máximo no puede ser negativo.',
            'max_value.max'   => $param->code === 'ph' ? 'El valor máximo del pH no puede ser mayor a 14.' : 'El valor máximo es demasiado grande.',
        ]);
        $validator->after(function ($v) use ($request) {
            $min = $request->input('min_value');
            $max = $request->input('max_value');
            if (! $v->errors()->hasAny(['min_value', 'max_value']) && filled($min) && filled($max) && (float) $max < (float) $min) {
                $v->errors()->add('max_value', 'El valor máximo no puede ser menor que el mínimo.');
            }
        });
        $data = $validator->validateWithBag("parameter{$param->id}");

        $param->update([
            'name'      => trim($data['name']),
            'unit'      => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'min_value' => $this->numberOrNull($data['min_value'] ?? null),
            'max_value' => $this->numberOrNull($data['max_value'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.erp-settings.water-analysis.index')
            ->with('success', "Parámetro \"{$param->name}\" actualizado.");
    }

    private function numberOrNull(mixed $v): ?float
    {
        return $v === null || $v === '' ? null : (float) $v;
    }
}
