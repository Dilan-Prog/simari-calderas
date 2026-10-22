<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MonitoringEquipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Equipos del cliente (calderas, torres, chillers...) que se monitorean desde
 * el portal de servicios. Cada cliente solo ve y modifica los suyos.
 */
class EquipmentController extends Controller
{
    private function customerId(): int
    {
        return (int) auth('customer')->id();
    }

    /** Un equipo ajeno responde 404 (no revela que existe). */
    private function owned(int|string $id): MonitoringEquipment
    {
        return MonitoringEquipment::where('customer_id', $this->customerId())->findOrFail($id);
    }

    private function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:120'],
            'type'      => ['required', Rule::in(array_keys(MonitoringEquipment::TYPES))],
            'location'  => ['nullable', 'string', 'max:150'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required'  => 'Indica el nombre del equipo.',
            'name.max'       => 'El nombre no puede superar 120 caracteres.',
            'type.required'  => 'Selecciona el tipo de equipo.',
            'type.in'        => 'Selecciona un tipo de equipo válido.',
            'location.max'   => 'La ubicación no puede superar 150 caracteres.',
            'notes.max'      => 'Las notas no pueden superar 2000 caracteres.',
        ];
    }

    public function index(): View
    {
        $equipment = MonitoringEquipment::where('customer_id', $this->customerId())
            ->withCount('entries')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('customers.equipment.index', [
            'equipment' => $equipment,
            'types'     => MonitoringEquipment::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        MonitoringEquipment::create([
            'customer_id' => $this->customerId(),
            'name'        => trim($data['name']),
            'type'        => $data['type'],
            'location'    => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'notes'       => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'is_active'   => true,
            'created_via' => 'customer',
        ]);

        return redirect()->route('customer.equipment.index')->with('success', 'Equipo agregado.');
    }

    public function update(Request $request, string $equipment): RedirectResponse
    {
        $model = $this->owned($equipment);
        $data = $request->validate($this->rules(), $this->messages());

        $model->update([
            'name'      => trim($data['name']),
            'type'      => $data['type'],
            'location'  => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'notes'     => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('customer.equipment.index')->with('success', 'Equipo actualizado.');
    }

    public function destroy(string $equipment): RedirectResponse
    {
        $model = $this->owned($equipment);

        // Con registros históricos no se borra: se desactiva para conservar el historial.
        if ($model->entries()->exists()) {
            $model->update(['is_active' => false]);

            return redirect()->route('customer.equipment.index')
                ->with('success', 'El equipo tiene registros de monitoreo, así que se desactivó en lugar de eliminarse. Su historial se conserva.');
        }

        $model->delete();

        return redirect()->route('customer.equipment.index')->with('success', 'Equipo eliminado.');
    }
}
