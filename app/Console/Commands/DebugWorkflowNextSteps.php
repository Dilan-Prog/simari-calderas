<?php

namespace App\Console\Commands;

use App\Services\WorkflowEngineService;
use Illuminate\Console\Command;

/**
 * Comando de depuración/test-only (Fase 10, punto 2 del plan de QA de
 * Automatizaciones): expone en JSON la secuencia completa de "siguiente
 * step" que produce WorkflowEngineService::nextSiblingOf() para un workflow
 * dado, sin ejecutar nada del motor (no crea WorkflowEnrollment, no llama
 * WorkflowActionExecutor).
 *
 * Existe para poder verificar por fuera (en tests/Feature/Workflows/
 * CanvasEngineParityTest.php, y manualmente vía `php artisan
 * workflows:debug-next-steps {id}` durante desarrollo) que la topología que
 * el motor PHP realmente recorre coincide con la que el canvas de React
 * dibuja (resources/js/admin/canvas/graph/toFlow.js). No se usa en el flujo
 * de negocio real.
 */
class DebugWorkflowNextSteps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflows:debug-next-steps {workflow : ID del workflow}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug/test-only: imprime en JSON la secuencia de "siguiente step" que produce nextSiblingOf() para cada step del workflow';

    public function handle(WorkflowEngineService $engine): int
    {
        $workflowId = (int) $this->argument('workflow');

        $sequence = $engine->nextStepSequence($workflowId);

        $this->line(json_encode($sequence));

        return self::SUCCESS;
    }
}
