<?php

namespace App\Observers;

use App\Services\AutomatableModuleRegistry;
use App\Services\WorkflowTriggerService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer genérico (Fase 16) que reemplaza a los observers bespoke
 * (DealObserver, WhatsappConversationObserver) para cualquier modelo
 * registrado en config/automatable_modules.php.
 *
 * created()/updated() resuelven el `type` de Workflow correspondiente al
 * modelo vía AutomatableModuleRegistry::typeForModel() y delegan en
 * WorkflowTriggerService::handleModelEvent() (Fase 17). Si el modelo que
 * disparó el evento no está en el registro (no debería pasar, ya que este
 * observer solo se registra sobre modelos del registro en
 * AppServiceProvider::boot(), pero se valida por seguridad), no hace nada:
 * silencioso, sin overhead extra.
 *
 * IMPORTANTE: igual que los observers que reemplaza, debe invocarse con la
 * MISMA instancia que Eloquent acaba de guardar (no fresh()/refresh()) para
 * que wasChanged() dentro de WorkflowTriggerService refleje los cambios
 * reales de ese guardado.
 */
class AutomatableModelObserver
{
    public function __construct(protected AutomatableModuleRegistry $registry)
    {
    }

    public function created(Model $model): void
    {
        $this->dispatch($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->dispatch($model, 'updated');
    }

    /**
     * Fase 23: delega igual que created()/updated(). Laravel invoca este
     * método automáticamente en cualquier borrado (soft o hard) de un
     * modelo con este Observer registrado -- no hace falta tocar el bucle
     * de registro en AppServiceProvider::boot(). En modelos con
     * SoftDeletes (ej. Deal), el evento 'deleted' de Eloquent dispara igual
     * con un borrado suave -- comportamiento esperado (ver
     * WorkflowTriggerService::triggerMatches()).
     */
    public function deleted(Model $model): void
    {
        $this->dispatch($model, 'deleted');
    }

    protected function dispatch(Model $model, string $eventType): void
    {
        $type = $this->registry->typeForModel($model);

        if ($type === null) {
            return;
        }

        app(WorkflowTriggerService::class)->handleModelEvent($model, $eventType, $type);
    }
}
