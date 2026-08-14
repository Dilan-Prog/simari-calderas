<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado de ejecución transitorio de UNA rama de un step 'parallel' dentro
 * de un WorkflowEnrollment. Filas de esta tabla son efímeras: se crean
 * cuando el motor entra a un step 'parallel' y se borran (todas juntas)
 * cuando el join correspondiente se completa (ver
 * WorkflowEngineService::checkJoinCompletion()).
 */
class WorkflowEnrollmentBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'parallel_step_id',
        'branch_key',
        'current_step_id',
        'status',
        'resume_at',
    ];

    protected $casts = [
        'resume_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(WorkflowEnrollment::class, 'enrollment_id');
    }

    public function parallelStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'parallel_step_id');
    }

    /**
     * IMPORTANTE: llamar como ->currentStep()->first() para obtener una
     * consulta SIEMPRE fresca, nunca como el accessor de propiedad mágico
     * $branch->currentStep -- misma razón documentada en
     * WorkflowEngineService::processStep() sobre WorkflowEnrollment::currentStep().
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }
}
