<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\Validator;
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
            'branch_condition' => 'nullable|sometimes|array',
            'branch_key' => 'nullable|sometimes|string|max:20|regex:/^[a-z0-9_]+$/i',
            'position_x' => 'nullable|sometimes|integer',
            'position_y' => 'nullable|sometimes|integer',
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
            //
            // step_type puede no venir en este payload (update parcial que
            // solo manda action_config) -- en ese caso se usa el step_type
            // YA guardado del paso real, vía route model binding.
            $stepType = $this->input('step_type');
            if ($stepType === null) {
                $step = $this->route('step');
                $stepType = $step?->step_type;
            }

            if ($stepType === 'loop' && $this->has('action_config')) {
                $maxIterations = data_get($this->input('action_config'), 'max_iterations');

                if (!is_numeric($maxIterations) || (int) $maxIterations < 1 || (int) $maxIterations > 50) {
                    $validator->errors()->add(
                        'action_config.max_iterations',
                        'action_config.max_iterations es requerido para un paso "loop" y debe ser un entero entre 1 y 50.'
                    );
                }
            }
        });
    }
}
