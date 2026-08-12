<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowVariable;
use Illuminate\Http\Request;

/**
 * CRUD JSON de variables de un Workflow (modal-based, mismo patrón que
 * PaymentMethodController). La ruta anida dos segmentos de modelo
 * ({workflow}/variables/{variable}), por lo que update()/destroy()
 * declaran Workflow $workflow aunque no lo usen directamente: omitir un
 * parámetro de ruta en la firma rompe la resolución posicional de
 * Laravel con rutas de dos modelos.
 */
class WorkflowVariableController extends Controller
{
    private const TYPES = ['string', 'number', 'json'];

    public function index(Workflow $workflow)
    {
        $variables = WorkflowVariable::where('workflow_id', $workflow->id)->get();

        return response()->json($variables);
    }

    public function store(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|in:' . implode(',', self::TYPES),
            'value' => 'nullable|string',
        ]);

        $variable = new WorkflowVariable();
        $variable->workflow_id = $workflow->id;
        $variable->name = $data['name'];
        $variable->type = $data['type'];
        $variable->value = $data['value'] ?? null;
        $variable->save();

        return response()->json([
            'success' => true,
            'variable' => $variable,
        ]);
    }

    public function update(Request $request, Workflow $workflow, WorkflowVariable $variable)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|in:' . implode(',', self::TYPES),
            'value' => 'nullable|string',
        ]);

        if (array_key_exists('name', $data)) {
            $variable->name = $data['name'];
        }
        if (array_key_exists('type', $data)) {
            $variable->type = $data['type'];
        }
        if (array_key_exists('value', $data)) {
            $variable->value = $data['value'];
        }
        $variable->save();

        return response()->json([
            'success' => true,
            'variable' => $variable,
        ]);
    }

    public function destroy(Workflow $workflow, WorkflowVariable $variable)
    {
        $variable->delete();

        return response()->json(['success' => true]);
    }
}
