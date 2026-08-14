<?php

namespace App\Console\Commands;

use App\Services\WorkflowTriggerService;
use Illuminate\Console\Command;

/**
 * Comando de scheduler: dispara el trigger "stage_stagnant" (Negocio
 * estancado en su etapa actual N días) para los Workflows de type='deal'.
 * Delega toda la lógica en WorkflowTriggerService::handleModelStageStagnant(),
 * que a su vez reusa Deal::scopeStalled() tal cual.
 *
 * withoutOverlapping() se declara en Kernel::schedule() (no aquí), igual que
 * los demás comandos de tick del proyecto.
 */
class TickStagnantDeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:tick-stagnant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inscribe en workflows activos los Negocios estancados en su etapa actual más allá del umbral configurado (trigger "stage_stagnant")';

    public function handle(WorkflowTriggerService $triggerService): int
    {
        $triggerService->handleModelStageStagnant('deal');

        return self::SUCCESS;
    }
}
