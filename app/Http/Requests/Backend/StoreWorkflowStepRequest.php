<?php

namespace App\Http\Requests\Backend;

use App\Models\WorkflowStep;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreWorkflowStepRequest extends FormRequest
{
    /**
     * La autorización real la realiza el middleware `permission:` en la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'step_type' => 'required|in:action,condition,wait,end,switch,parallel,join,loop',
            'parent_step_id' => 'nullable|integer|exists:workflow_steps,id',
            'action_type' => 'nullable|string|max:150',
            'action_config' => 'nullable|array',
            'branch_condition' => 'nullable|array',
            'branch_key' => 'nullable|string|max:20|regex:/^[a-z0-9_]+$/i',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // max_iterations de un paso 'loop' se valida aquí a mano, en vez
            // de con una regla 'action_config.max_iterations' en rules() --
            // esa dot-notation hacía que Validator::validated() reconstruyera
            // 'action_config' SOLO con las subclaves que tienen regla
            // explícita, descartando en silencio cualquier otra clave
            // (template_id, rules, branches, etc.) de action_config para
            // TODOS los step_type, no solo 'loop'. Bug real detectado en vivo:
            // guardar la acción "Enviar correo" persistía action_config=null
            // pese a que el payload enviado por el cliente era correcto.
            if ($this->input('step_type') === 'loop') {
                $maxIterations = data_get($this->input('action_config'), 'max_iterations');

                if (!is_numeric($maxIterations) || (int) $maxIterations < 1 || (int) $maxIterations > 50) {
                    $validator->errors()->add(
                        'action_config.max_iterations',
                        'action_config.max_iterations es requerido para un paso "loop" y debe ser un entero entre 1 y 50.'
                    );
                }
            }

            $parentStepId = $this->input('parent_step_id');

            if (empty($parentStepId)) {
                return;
            }

            $parentStep = WorkflowStep::find($parentStepId);

            if (! $parentStep) {
                return;
            }

            $branchKey = $this->input('branch_key');
            $stepType = $this->input('step_type');

            // Un 'join' hijo de un 'parallel' es el ÚNICO caso donde, siendo
            // hijo de un nodo "ramificador", branch_key debe estar PROHIBIDO
            // en vez de requerido (marca el punto de reunión, no una rama).
            $isJoinUnderParallel = $parentStep->step_type === 'parallel' && $stepType === 'join';

            $branchKeyRequired = ! $isJoinUnderParallel && in_array($parentStep->step_type, ['condition', 'switch', 'parallel'], true);

            if (! $branchKeyRequired && ! empty($branchKey)) {
                $validator->errors()->add(
                    'branch_key',
                    'branch_key solo tiene sentido cuando el paso padre es de tipo condition, switch o parallel (y el paso actual no es el join de ese parallel).'
                );
            }

            if ($branchKeyRequired && empty($branchKey)) {
                $validator->errors()->add(
                    'branch_key',
                    'branch_key es requerido cuando el paso padre es de tipo condition, switch o parallel.'
                );
            }
        });
    }
}
