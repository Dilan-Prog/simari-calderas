<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowStepRequest extends FormRequest
{
    /**
     * La autorización real la realiza el middleware `permission:` en la ruta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Todas las reglas llevan 'sometimes' porque un update puede mandar solo
     * un subconjunto de campos (ej. solo position_x/position_y al arrastrar,
     * o solo action_config al guardar el inspector).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'step_type' => 'nullable|sometimes|in:action,condition,wait,end,switch,parallel,join,loop',
            'parent_step_id' => 'nullable|sometimes|integer|exists:workflow_steps,id',
            'action_type' => 'nullable|sometimes|string|max:150',
            'action_config' => 'nullable|sometimes|array',
            'action_config.max_iterations' => 'required_if:step_type,loop|integer|min:1|max:50',
            'branch_condition' => 'nullable|sometimes|array',
            'branch_key' => 'nullable|sometimes|string|max:20|regex:/^[a-z0-9_]+$/i',
            'position_x' => 'nullable|sometimes|integer',
            'position_y' => 'nullable|sometimes|integer',
        ];
    }
}
