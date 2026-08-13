<?php

namespace App\Console\Commands;

use App\Services\WorkflowTriggerService;
use Illuminate\Console\Command;

/**
 * Mismo patrón que TickWaitingWorkflows (workflows:tick): comando periódico
 * que evalúa el trigger "sin responder más de N horas" de los workflows
 * activos tipo 'whatsapp_conversation' e inscribe las conversaciones
 * estancadas que correspondan. withoutOverlapping() en Kernel::schedule()
 * desde el día uno (lección de Fase 10, punto 1).
 */
class TickStaleWhatsappConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp-conversations:tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inscribe en workflows activos las conversaciones de WhatsApp sin responder más del umbral configurado (trigger "stale")';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowTriggerService $triggerService): int
    {
        $triggerService->handleConversationStale();

        return self::SUCCESS;
    }
}
