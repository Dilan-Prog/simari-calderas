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
            'action_config.max_iterations' => 'required_if:step_type,loop|integer|min:1|max:50',
            'branch_condition' => 'nullable|array',
            'branch_key' => 'nullable|string|max:20|regex:/^[a-z0-9_]+$/i',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
