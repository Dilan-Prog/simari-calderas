<?php

namespace App\Console\Commands;

use App\Services\ProductionService;
use Illuminate\Console\Command;

class ProductionAlertDelays extends Command
{
    protected $signature = 'production:alert-delays';

    protected $description = 'Crea una tarea por cada orden de producción vencida y no terminada (sin duplicar).';

    public function handle(ProductionService $service): int
    {
        $n = $service->alertDelays();
        $this->info("Tareas de retraso creadas: {$n}");

        return self::SUCCESS;
    }
}
