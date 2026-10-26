<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterAnalysisResult extends Model
{
    protected $fillable = ['report_id', 'parameter_id', 'value'];

    protected $casts = [
        'value' => 'float',
    ];

    public function report()
    {
        return $this->belongsTo(WaterAnalysisReport::class, 'report_id');
    }

    public function parameter()
    {
        return $this->belongsTo(WaterAnalysisParameter::class, 'parameter_id');
    }
}
