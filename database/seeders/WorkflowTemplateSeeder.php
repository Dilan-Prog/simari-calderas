<?php

namespace Database\Seeders;

use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

/**
 * Workflows de ejemplo ya armados con sus steps, usando los modelos reales
 * (no factories) -- pensados como plantillas de referencia rápidas para
 * probar el step-builder y la vista de debug de ejecuciones
 * (admin.workflows.show) sin tener que armar un workflow a mano primero.
 *
 * Registrado en DatabaseSeeder (se corre con el resto de seeders vía
 * `php artisan db:seed`), pero también se puede correr manualmente con:
 *   php artisan db:seed --class=WorkflowTemplateSeeder
 */
class WorkflowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->negocioEstancado();
        $this->clienteNuevoBienvenida();
    }

    /**
     * "Negocio estancado 7 días -> notificar"
     *
     * Workflow tipo 'deal'. El enrollment_trigger es una versión simplificada
     * (se dispara con cualquier actualización del deal); en un escenario real
     * la detección de "estancado N días sin movimiento" requeriría un job
     * programado que evalúe periódicamente los deals abiertos y los inscriba
     * -- eso está fuera de alcance de este seeder, aquí solo se arma la
     * estructura del workflow (steps) para poder probar el motor y la vista
     * de debug.
     *
     * Steps:
     *   1. wait   -> espera 7 días
     *   2. action -> notify_rep (notifica al responsable del deal)
     */
    protected function negocioEstancado(): void
    {
        $workflow = Workflow::create([
            'name'                 => 'Negocio estancado 7 días -> notificar',
            'type'                 => 'deal',
            'enrollment_trigger'   => [
                'event' => 'updated',
                'field' => 'pipeline_stage_id',
            ],
            'is_active'            => true,
            'reenrollment_allowed' => false,
            'created_by'           => null,
            'is_template'          => true,
        ]);

        $waitStep = WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => null,
            'branch_condition' => null,
            'order'            => 1,
            'step_type'        => 'wait',
            'action_type'      => null,
            'action_config'    => [
                'unit'   => 'days',
                'amount' => 7,
            ],
        ]);

        WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => null,
            'branch_condition' => null,
            'order'            => 2,
            'step_type'        => 'action',
            'action_type'      => 'notify_rep',
            'action_config'    => [
                'message' => 'Este negocio lleva 7 días sin movimiento de etapa. Dale seguimiento.',
            ],
        ]);

        $this->command?->info("Workflow \"{$workflow->name}\" creado (ID {$workflow->id}) con ".$workflow->allSteps()->count().' steps.');

        // Referencia al primer step por si se quiere inspeccionar desde tinker.
        unset($waitStep);
    }

    /**
     * "Cliente nuevo -> tarea de bienvenida"
     *
     * Workflow tipo 'customer'. Se dispara al crear el cliente y crea una
     * tarea de bienvenida asignada al equipo de ventas. Sirve como segundo
     * ejemplo de referencia con un action_type distinto (create_task) y sin
     * step de espera.
     */
    protected function clienteNuevoBienvenida(): void
    {
        $workflow = Workflow::create([
            'name'                 => 'Cliente nuevo -> tarea de bienvenida',
            'type'                 => 'customer',
            'enrollment_trigger'   => [
                'event' => 'created',
            ],
            'is_active'            => true,
            'reenrollment_allowed' => false,
            'created_by'           => null,
            'is_template'          => true,
        ]);

        WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => null,
            'branch_condition' => null,
            'order'            => 1,
            'step_type'        => 'action',
            'action_type'      => 'create_task',
            'action_config'    => [
                'title'       => 'Llamada de bienvenida',
                'description' => 'Contactar al cliente nuevo para presentarle a su ejecutivo de cuenta y resolver dudas iniciales.',
                'due_at'      => null,
                'assigned_to' => null,
            ],
        ]);

        $this->command?->info("Workflow \"{$workflow->name}\" creado (ID {$workflow->id}) con ".$workflow->allSteps()->count().' steps.');
    }
}
