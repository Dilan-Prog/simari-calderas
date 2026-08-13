<?php

namespace App\Console\Commands;

use App\Services\AutomatableModuleRegistry;
use App\Services\WorkflowTriggerService;
use Illuminate\Console\Command;

/**
 * Comando de scheduler genérico (Fase 16) que reemplaza a
 * whatsapp-conversations:tick: itera las entradas de
 * config/automatable_modules.php con supports_stale=true y, para cada una,
 * delega en WorkflowTriggerService::handleModelStale() (Fase 17) el trigger
 * de "obsoleto" (sin actividad en N horas) correspondiente.
 *
 * withoutOverlapping() se declara en Kernel::schedule() desde el día uno
 * (lección de Fase 10, punto 1) -- no aquí, es responsabilidad del
 * scheduler, igual que los demás comandos de tick del proyecto.
 */
class TickAutomatableModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automatable-modules:tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inscribe en workflows activos los registros de cualquier módulo automatizable (config/automatable_modules.php) sin actividad más allá del umbral configurado (trigger "stale")';

    public function handle(AutomatableModuleRegistry $registry, WorkflowTriggerService $triggerService): int
    {
        foreach ($registry->all() as $type => $entry) {
            if (empty($entry['supports_stale']) || empty($entry['stale_field'])) {
                continue;
            }

            $triggerService->handleModelStale($type, $entry['stale_field'], 24);
        }

        return self::SUCCESS;
    }
}
