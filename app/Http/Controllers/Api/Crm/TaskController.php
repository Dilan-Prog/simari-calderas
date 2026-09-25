<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Fase 3 (API N8N) — CRM / Tasks. Polimórfico, igual que
 * WorkflowActionExecutor::createTask() (app/Services/WorkflowActionExecutor.php)
 * -- ese método no es reutilizable tal cual desde un controlador HTTP porque
 * recibe un WorkflowEnrollment ya resuelto (toma taskable de
 * $enrollment->enrollable, con los tokens {{ }} ya interpolados), así que
 * aquí se replica el mismo criterio de construcción del Task en vez de
 * duplicar esa clase.
 *
 * Diferencia deliberada de seguridad frente a aceptar cualquier
 * `taskable_type` crudo: se exige que el FQCN mandado por el caller esté
 * registrado como `model` en config/automatable_modules.php (mismo catálogo
 * que ya usa Api\N8n\WorkflowTriggerController::enroll() para no aceptar
 * enrollable_type arbitrario) -- evita que un token con tasks:write pueda
 * instanciar/consultar un modelo Eloquent fuera de ese catálogo.
 */
class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taskable_type' => 'required|string',
            'taskable_id'   => 'required|integer',
        ]);

        $tasks = Task::where('taskable_type', $data['taskable_type'])
            ->where('taskable_id', $data['taskable_id'])
            ->latest()
            ->get();

        return response()->json(['data' => TaskResource::collection($tasks)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'taskable_type' => 'required|string',
            'taskable_id'   => 'required|integer',
            'assigned_to'   => 'nullable|exists:users,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'due_at'        => 'nullable|date',
            'status'        => 'nullable|string|max:50',
        ]);

        $allowedModels = collect(config('automatable_modules'))->pluck('model')->all();

        if (! in_array($data['taskable_type'], $allowedModels, true)) {
            throw ValidationException::withMessages([
                'taskable_type' => ["'{$data['taskable_type']}' no es un tipo de registro soportado. Ver config/automatable_modules.php."],
            ]);
        }

        $taskableClass = $data['taskable_type'];
        $taskable = $taskableClass::find($data['taskable_id']);

        if (! $taskable) {
            throw ValidationException::withMessages([
                'taskable_id' => ["No existe un registro de '{$data['taskable_type']}' con id {$data['taskable_id']}."],
            ]);
        }

        $task = new Task([
            'assigned_to' => $data['assigned_to'] ?? null,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at'      => $data['due_at'] ?? null,
            'status'      => $data['status'] ?? 'open',
        ]);

        $task->taskable()->associate($taskable);
        $task->save();

        return response()->json(['data' => new TaskResource($task)], 201);
    }
}
