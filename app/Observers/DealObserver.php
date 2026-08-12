<?php

namespace App\Observers;

/**
 * Observer para el modelo App\Models\Deal (aún no existe; se creará en Fase 1
 * del módulo CRM). Se usa el FQCN completo aquí a modo de referencia.
 */
class DealObserver
{
    /**
     * @param \App\Models\Deal $deal
     */
    public function created($deal): void
    {
        app(\App\Services\WorkflowTriggerService::class)->handleDealEvent($deal, 'created');
    }

    /**
     * @param \App\Models\Deal $deal
     */
    public function updated($deal): void
    {
        app(\App\Services\WorkflowTriggerService::class)->handleDealEvent($deal, 'updated');
    }
}
