<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MonitoringProgram;
use App\Models\MonitoringProgramItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Parámetros / químicos que el cliente agrega a su programa de monitoreo.
 * Los elementos definidos por Equiterm (added_by = staff) son de solo lectura.
 */
class MonitoringItemController extends Controller
{
    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    public function store(Request $request, string $program): RedirectResponse
    {
        $prog = MonitoringProgram::where('customer_id', $this->customerId())->findOrFail($program);

        // Acepta coma decimal en los límites.
        foreach (['limit_min', 'limit_max'] as $key) {
            if (is_string($request->input($key))) {
                $request->merge([$key => str_replace(',', '.', trim($request->input($key)))]);
            }
        }

        $isChemical = $request->input('kind') === MonitoringProgramItem::KIND_CHEMICAL;

        $rules = [
            'kind'       => ['required', Rule::in([MonitoringProgramItem::KIND_PARAMETER, MonitoringProgramItem::KIND_CHEMICAL])],
            'name'       => ['required', 'string', 'max:120'],
            'unit'       => ['nullable', 'string', 'max:20'],
            'limit_min'  => ['nullable', 'numeric', 'between:-9999999,9999999'],
            'limit_max'  => array_merge(
                ['nullable', 'numeric', 'between:-9999999,9999999'],
                filled($request->input('limit_min')) ? ['gte:limit_min'] : []
            ),
            'start_week' => ['required', 'integer', 'between:1,' . $prog->weeks],
            'end_week'   => ['required', 'integer', 'between:1,' . $prog->weeks, 'gte:start_week'],
        ];

        $messages = [
            'kind.required'       => 'Selecciona si es una lectura del agua o un químico.',
            'kind.in'             => 'Selecciona un tipo válido.',
            'name.required'       => 'Indica el nombre del parámetro o químico.',
            'name.max'            => 'El nombre no puede superar 120 caracteres.',
            'unit.max'            => 'La unidad no puede superar 20 caracteres.',
            'limit_min.numeric'   => 'El límite mínimo debe ser un número.',
            'limit_min.between'   => 'El límite mínimo está fuera del rango permitido.',
            'limit_max.numeric'   => 'El límite máximo debe ser un número.',
            'limit_max.between'   => 'El límite máximo está fuera del rango permitido.',
            'limit_max.gte'       => 'El límite máximo no puede ser menor al mínimo.',
            'start_week.required' => 'Indica la semana de inicio.',
            'start_week.integer'  => 'La semana de inicio debe ser un número entero.',
            'start_week.between'  => 'La semana de inicio debe estar entre 1 y ' . $prog->weeks . '.',
            'end_week.required'   => 'Indica la semana final.',
            'end_week.integer'    => 'La semana final debe ser un número entero.',
            'end_week.between'    => 'La semana final debe estar entre 1 y ' . $prog->weeks . '.',
            'end_week.gte'        => 'La semana final no puede ser anterior a la de inicio.',
        ];

        $data = $request->validateWithBag('item', $rules, $messages);

        $name = trim($data['name']);
        $duplicate = $prog->items()
            ->where('kind', $data['kind'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un elemento con ese nombre en el programa.',
            ])->errorBag('item');
        }

        $prog->items()->create([
            'kind'       => $data['kind'],
            'name'       => $name,
            'unit'       => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            // Los límites solo aplican a lecturas del agua.
            'limit_min'  => $isChemical || !isset($data['limit_min']) || $data['limit_min'] === '' ? null : $data['limit_min'],
            'limit_max'  => $isChemical || !isset($data['limit_max']) || $data['limit_max'] === '' ? null : $data['limit_max'],
            'start_week' => (int) $data['start_week'],
            'end_week'   => (int) $data['end_week'],
            'added_by'   => 'customer',
            'sort_order' => ((int) $prog->items()->max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('customer.monitoring.show', ['program' => $prog->id, 'tab' => 'registro'])
            ->with('success', $isChemical ? 'Químico agregado al programa.' : 'Parámetro agregado al programa.');
    }

    public function destroy(string $item): RedirectResponse
    {
        $model = MonitoringProgramItem::whereHas('program', fn ($q) => $q->where('customer_id', $this->customerId()))
            ->findOrFail($item);

        $back = redirect()->route('customer.monitoring.show', ['program' => $model->program_id, 'tab' => 'registro']);

        if ($model->added_by !== 'customer') {
            return $back->withErrors(['item_delete' => 'Los elementos definidos por Equiterm no se pueden eliminar.']);
        }

        if ($model->entries()->exists()) {
            return $back->withErrors(['item_delete' => 'No puedes eliminar "' . $model->name . '" porque ya tiene registros.']);
        }

        $model->delete();

        return $back->with('success', 'Elemento eliminado del programa.');
    }
}
