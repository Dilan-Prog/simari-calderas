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
            'step_type' => 'required|in:action,condition,wait',
            'parent_step_id' => 'nullable|integer|exists:workflow_steps,id',
            'action_type' => 'nullable|string|max:150',
            'action_config' => 'nullable|array',
            'branch_condition' => 'nullable|array',
            'branch_key' => 'nullable|string|in:yes,no',
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
            $parentIsCondition = $parentStep->step_type === 'condition';

            if (! $parentIsCondition && ! empty($branchKey)) {
                $validator->errors()->add(
                    'branch_key',
                    'branch_key solo tiene sentido cuando el paso padre es de tipo condition.'
                );
            }

            if ($parentIsCondition && empty($branchKey)) {
                $validator->errors()->add(
                    'branch_key',
                    'branch_key es requerido cuando el paso padre es de tipo condition.'
                );
            }
        });
    }
}
