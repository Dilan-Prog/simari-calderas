<?php

namespace App\Console\Commands;

use App\Services\FinanceService;
use Illuminate\Console\Command;

class FinanceMarkOverdue extends Command
{
    protected $signature = 'finance:mark-overdue';

    protected $description = 'Marca como vencidas las cuentas por cobrar y por pagar abiertas con fecha de vencimiento pasada';

    public function handle(FinanceService $finance): int
    {
        $n = $finance->markOverdue();
        $this->info("Documentos marcados como vencidos: {$n}");

        return self::SUCCESS;
    }
}
