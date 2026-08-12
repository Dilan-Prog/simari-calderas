<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD de pasos de Workflow (admin). Sigue el mismo patrón modal/AJAX-JSON
 * que PipelineController: sin vistas propias, todas las acciones se llaman
 * vía AJAX desde el builder de pasos y responden JSON.
 */
class WorkflowStepController extends Controller
{
    public function store(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'step_type' => 'required|in:action,condition,wait',
            'parent_step_id' => 'nullable|exists:workflow_steps,id',
            'action_type' => 'nullable|string|max:150',
            'action_config' => 'nullable|array',
            'branch_condition' => 'nullable|array',
        ]);

        $step = DB::transaction(function () use ($workflow, $data) {
            $nextOrder = WorkflowStep::where('workflow_id', $workflow->id)
                ->where('parent_step_id', $data['parent_step_id'] ?? null)
                ->max('order');

            $nextOrder = $nextOrder === null ? 0 : $nextOrder + 1;

            return WorkflowStep::create([
                'workflow_id' => $workflow->id,
                'parent_step_id' => $data['parent_step_id'] ?? null,
                'step_type' => $data['step_type'],
                'action_type' => $data['action_type'] ?? null,
                'action_config' => $data['action_config'] ?? null,
                'branch_condition' => $data['branch_condition'] ?? null,
                'order' => $nextOrder,
            ]);
        });

        return response()->json([
            'success' => true,
            'step' => $step,
        ]);
    }

    public function update(Request $request, WorkflowStep $step)
    {
        $data = $request->validate([
            'action_type' => 'nullable|string|max:150',
            'action_config' => 'nullable|array',
            'branch_condition' => 'nullable|array',
        ]);

        $step->update([
            'action_type' => $data['action_type'] ?? null,
            'action_config' => $data['action_config'] ?? null,
            'branch_condition' => $data['branch_condition'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'step' => $step,
        ]);
    }

    public function destroy(WorkflowStep $step)
    {
        $step->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'step_ids' => 'required|array',
            'step_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($workflow, $data) {
            foreach ($data['step_ids'] as $index => $stepId) {
                WorkflowStep::where('id', $stepId)
                    ->where('workflow_id', $workflow->id)
                    ->update(['order' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }
}
