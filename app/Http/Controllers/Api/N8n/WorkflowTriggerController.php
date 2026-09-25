<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowEnrollmentResource;
use App\Http\Resources\WorkflowResource;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Puente de entrada N8N -> motor de Workflows. El motor ya tenía todo lo
 * necesario del lado "hacia afuera" (acción call_webhook en
 * WorkflowActionExecutor), pero nada del lado "hacia adentro": ningún
 * endpoint permitía que un sistema externo inscribiera algo en un workflow.
 * enroll() reutiliza tal cual WorkflowEngineService::enroll() (el mismo
 * método que usa la acción interna 'enroll_in_workflow'), sin duplicar la
 * lógica de inscripción/reenrollment_allowed/procesamiento del primer step.
 */
class WorkflowTriggerController extends Controller
{
    public function index(): JsonResponse
    {
        $workflows = Workflow::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => WorkflowResource::collection($workflows)]);
    }

    public function enroll(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'enrollable_id' => ['required', 'integer'],
            'context'       => ['nullable', 'array'],
        ]);

        // El `type` de un Workflow es el mismo string libre que ya usa
        // config('automatable_modules') como llave (ver comentario en ese
        // archivo) -- se reutiliza tal cual como whitelist, en vez de
        // aceptar cualquier enrollable_type que mande el caller.
        $moduleKey = $workflow->type;
        $module = config("automatable_modules.{$moduleKey}");

        if (! $module) {
            throw ValidationException::withMessages([
                'workflow' => ["El workflow #{$workflow->id} tiene type '{$moduleKey}', que no está registrado en config/automatable_modules.php."],
            ]);
        }

        $target = $module['model']::find($data['enrollable_id']);

        if (! $target) {
            throw ValidationException::withMessages([
                'enrollable_id' => ["No existe un registro de '{$moduleKey}' con id {$data['enrollable_id']}."],
            ]);
        }

        $enrollment = app(WorkflowEngineService::class)->enroll(
            $workflow,
            $target,
            array_merge($data['context'] ?? [], ['source' => 'n8n'])
        );

        return response()->json(['data' => new WorkflowEnrollmentResource($enrollment)], 201);
    }

    public function showEnrollment(WorkflowEnrollment $workflowEnrollment): JsonResponse
    {
        return response()->json(['data' => new WorkflowEnrollmentResource($workflowEnrollment)]);
    }
}
