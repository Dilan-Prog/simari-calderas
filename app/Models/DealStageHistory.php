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
