<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MonitoringEntry;
use App\Models\MonitoringProgram;
use App\Models\MonitoringProgramItem;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registro diario del cliente: lecturas del agua y dosificación de químicos.
 * Solo se pueden editar / eliminar los registros capturados por el propio cliente.
 */
class MonitoringEntryController extends Controller
{
    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    private function messages(): array
    {
        return [
            'entry_date.required'        => 'Indica la fecha del registro.',
            'entry_date.date'            => 'La fecha no es válida.',
            'entry_date.after_or_equal'  => 'La fecha no puede ser anterior al inicio del programa.',
            'entry_date.before_or_equal' => 'La fecha debe estar dentro del programa y no puede ser futura.',
            'item_id.required'           => 'Selecciona el parámetro o químico.',
            'item_id.exists'             => 'El elemento seleccionado no pertenece a este programa.',
            'equipment_id.exists'        => 'Selecciona uno de tus equipos activos.',
            'value.required'             => 'Captura el valor medido o la cantidad aplicada.',
            'value.numeric'              => 'El valor debe ser un número (puedes usar punto o coma decimal).',
            'value.between'              => 'El valor está fuera del rango permitido.',
            'value.min'                  => 'La cantidad dosificada no puede ser negativa.',
            'note.max'                   => 'La nota no puede superar 255 caracteres.',
        ];
    }

    /** Normaliza la coma decimal y los espacios del valor capturado. */
    private function normalize(Request $request): void
    {
        if (is_string($request->input('value'))) {
            $request->merge(['value' => str_replace(',', '.', str_replace(' ', '', trim($request->input('value'))))]);
        }
    }

    /** Última fecha válida de captura: fin del programa o hoy, lo que ocurra primero. */
    private function maxDate(MonitoringProgram $program): Carbon
    {
        $today = now()->startOfDay();
        $end = $program->endDate()->startOfDay();

        return $end->lessThan($today) ? $end : $today;
    }

    public function store(Request $request, string $program): RedirectResponse
    {
        $prog = MonitoringProgram::where('customer_id', $this->customerId())->findOrFail($program);
        $this->normalize($request);

        $rules = [
            'entry_date'   => [
                'required', 'date',
                'after_or_equal:' . $prog->start_date->toDateString(),
                'before_or_equal:' . $this->maxDate($prog)->toDateString(),
            ],
            'item_id'      => ['required', 'integer', Rule::exists('monitoring_program_items', 'id')->where('program_id', $prog->id)],
            'equipment_id' => ['nullable', 'integer', Rule::exists('monitoring_equipment', 'id')
                ->where('customer_id', $this->customerId())->where('is_active', 1)],
            'value'        => ['required', 'numeric', 'between:-99999999,99999999'],
            'note'         => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validateWithBag('entry', $rules, $this->messages());

        $item = MonitoringProgramItem::where('program_id', $prog->id)->findOrFail($data['item_id']);

        // El tipo de registro se deriva del elemento; nunca se confía en la entrada.
        $type = $item->isChemical() ? MonitoringEntry::TYPE_DOSE : MonitoringEntry::TYPE_READING;

        if ($type === MonitoringEntry::TYPE_DOSE && (float) $data['value'] < 0) {
            return back()->withInput()->withErrors(['value' => $this->messages()['value.min']], 'entry');
        }

        $entry = new MonitoringEntry([
            'program_id'              => $prog->id,
            'item_id'                 => $item->id,
            'equipment_id'            => $data['equipment_id'] ?? null,
            'entry_type'              => $type,
            'entry_date'              => $data['entry_date'],
            'value'                   => (float) $data['value'],
            'note'                    => filled($data['note'] ?? null) ? trim($data['note']) : null,
            'recorded_by_customer_id' => $this->customerId(),
        ]);
        $entry->setRelation('item', $item);
        $entry->save();

        $msg = $type === MonitoringEntry::TYPE_READING && $entry->in_range === false
            ? 'Lectura guardada. Quedó fuera del rango esperado; tu equipo de Equiterm podrá revisarla.'
            : 'Registro guardado.';

        return redirect()
            ->route('customer.monitoring.show', [
                'program' => $prog->id,
                'tab'     => 'registro',
                'week'    => $prog->weekOf($entry->entry_date),
            ])
            ->with('success', $msg);
    }

    /** Registro propio dentro de un programa propio; cualquier otro caso responde 404. */
    private function ownedEntry(string $id): MonitoringEntry
    {
        return MonitoringEntry::with(['item', 'program'])
            ->whereHas('program', fn ($q) => $q->where('customer_id', $this->customerId()))
            ->findOrFail($id);
    }

    private function isOwnRecord(MonitoringEntry $entry): bool
    {
        return (int) $entry->recorded_by_customer_id === $this->customerId();
    }

    public function update(Request $request, string $entry): RedirectResponse
    {
        $model = $this->ownedEntry($entry);

        if (!$this->isOwnRecord($model)) {
            return back()->withErrors(['entry_locked' => 'Solo puedes modificar los registros que capturaste tú. Los del equipo de Equiterm no se editan desde el portal.']);
        }

        $prog = $model->program;
        $this->normalize($request);

        $rules = [
            'entry_date'   => [
                'required', 'date',
                'after_or_equal:' . $prog->start_date->toDateString(),
                'before_or_equal:' . $this->maxDate($prog)->toDateString(),
            ],
            'equipment_id' => ['nullable', 'integer', Rule::exists('monitoring_equipment', 'id')->where('customer_id', $this->customerId())],
            'value'        => ['required', 'numeric', 'between:-99999999,99999999'],
            'note'         => ['nullable', 'string', 'max:255'],
        ];

        $data = $request->validateWithBag('entry_edit', $rules, $this->messages());

        if ($model->entry_type === MonitoringEntry::TYPE_DOSE && (float) $data['value'] < 0) {
            return back()->withInput()->withErrors(['value' => $this->messages()['value.min']], 'entry_edit');
        }

        $model->fill([
            'equipment_id' => $data['equipment_id'] ?? null,
            'entry_date'   => $data['entry_date'],
            'value'        => (float) $data['value'],
            'note'         => filled($data['note'] ?? null) ? trim($data['note']) : null,
        ])->save(); // in_range se recalcula en el evento saving del modelo.

        return back()->with('success', 'Registro actualizado.');
    }

    public function destroy(string $entry): RedirectResponse
    {
        $model = $this->ownedEntry($entry);

        if (!$this->isOwnRecord($model)) {
            return back()->withErrors(['entry_locked' => 'Solo puedes eliminar los registros que capturaste tú.']);
        }

        $model->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
