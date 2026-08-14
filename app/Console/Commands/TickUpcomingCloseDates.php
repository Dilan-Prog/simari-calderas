<?php

namespace App\Console\Commands;

use App\Services\WorkflowTriggerService;
use Illuminate\Console\Command;

/**
 * Comando de scheduler: dispara el trigger "upcoming" (fecha próxima a
 * cumplirse en los siguientes N días) sobre expected_close_date de los
 * Workflows de type='deal'. Delega toda la lógica en
 * WorkflowTriggerService::handleModelUpcoming().
 *
 * withoutOverlapping() se declara en Kernel::schedule() (no aquí), igual que
 * los demás comandos de tick del proyecto.
 */
class TickUpcomingCloseDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deals:tick-upcoming-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inscribe en workflows activos los Negocios cuya fecha de cierre esperada (expected_close_date) cae dentro de la ventana configurada (trigger "upcoming")';

    public function handle(WorkflowTriggerService $triggerService): int
    {
        $triggerService->handleModelUpcoming('deal', 'expected_close_date');

        return self::SUCCESS;
    }
}
