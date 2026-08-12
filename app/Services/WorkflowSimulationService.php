<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowStep;

/**
 * Modo de PRUEBA para workflows: recorre el árbol de steps de un workflow
 * con datos de ejemplo simulados, SIN ejecutar ninguna acción real.
 *
 * A diferencia de WorkflowEngineService, este servicio nunca llama a
 * WorkflowActionExecutor, nunca crea un WorkflowEnrollment real y nunca
 * toca un Deal/Customer real. Solo produce un log de lo que habría pasado.
 */
class WorkflowSimulationService
{
    /**
     * Recorre el workflow con $sampleData y retorna un log de pasos simulados.
     *
     * @param Workflow $workflow
     * @param array $sampleData
     * @return array
     */
    public function run(Workflow $workflow, array $sampleData): array
    {
        $log = [];
        $step = WorkflowStep::where('workflow_id', $workflow->id)
            ->whereNull('parent_step_id')->orderBy('order')->first();

        $guard = 0;
        while ($step && $guard < 200) { // límite duro anti-ciclo infinito en modo prueba
            $guard++;

            if ($step->step_type === 'condition') {
                $result = WorkflowConditionEvaluator::evaluate($sampleData, $step->branch_condition ?? []);
                $branchKey = $result ? 'yes' : 'no';
                $log[] = ['step_id' => $step->id, 'step_type' => 'condition', 'result' => $branchKey, 'message' => "Condición evaluó: " . ($result ? 'Sí' : 'No')];
                $step = WorkflowStep::where('parent_step_id', $step->id)->where('branch_key', $branchKey)->orderBy('order')->first();
                continue;
            }

            if ($step->step_type === 'wait') {
                $log[] = ['step_id' => $step->id, 'step_type' => 'wait', 'result' => 'simulated', 'message' => 'Espera simulada (no se pausa realmente en modo prueba)'];
            } else { // action
                $log[] = ['step_id' => $step->id, 'step_type' => 'action', 'action_type' => $step->action_type, 'result' => 'simulated', 'message' => "Acción simulada: {$step->action_type}", 'config' => $step->action_config];
            }

            // Siguiente hermano dentro de la misma rama (misma lógica que
            // nextSiblingOf() del motor real, duplicada intencionalmente aquí
            // porque este servicio no ejecuta dentro de un WorkflowEnrollment real).
            $query = WorkflowStep::where('workflow_id', $step->workflow_id)
                ->where('parent_step_id', $step->parent_step_id)
                ->where('order', '>', $step->order)->orderBy('order');
            $step->branch_key === null ? $query->whereNull('branch_key') : $query->where('branch_key', $step->branch_key);
            $step = $query->first();
        }

        return $log;
    }
}
