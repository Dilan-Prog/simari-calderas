<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro diario del análisis químico del agua de un cliente (uno por cliente y día). */
class WaterAnalysisReport extends Model
{
    protected $fillable = ['customer_id', 'report_date', 'notes', 'recorded_by_member_id'];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function results()
    {
        return $this->hasMany(WaterAnalysisResult::class, 'report_id');
    }
}
