<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealStageHistory extends Model
{
    protected $table = 'deal_stage_history';

    protected $fillable = [
        'deal_id',
        'from_stage_id',
        'to_stage_id',
        'moved_by',
        'moved_at',
        'duration_in_previous_stage_seconds',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Fase 7: Cotizado / Venta ganada disparan la conversión a Google (nunca rompe el movimiento de etapa).
        static::created(function (DealStageHistory $history) {
            rescue(function () use ($history) {
                $deal = Deal::withTrashed()->find($history->deal_id);
                if ($deal && $history->to_stage_id) {
                    app(\App\Services\GoogleConversionService::class)->handleStageMove(
                        $deal,
                        $history->from_stage_id ? PipelineStage::find($history->from_stage_id) : null,
                        PipelineStage::find($history->to_stage_id)
                    );
                }
            }, null, true);
        });
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function fromStage()
    {
        return $this->belongsTo(PipelineStage::class, 'from_stage_id');
    }

    public function toStage()
    {
        return $this->belongsTo(PipelineStage::class, 'to_stage_id');
    }

    public function movedByUser()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
