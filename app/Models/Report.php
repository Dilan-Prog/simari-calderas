<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'data_source',
        'chart_type',
        'config',
        'created_by',
        'is_prebuilt',
    ];

    protected $casts = [
        'config'       => 'array',
        'is_prebuilt'  => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dashboards()
    {
        return $this->belongsToMany(Dashboard::class, 'dashboard_reports')
            ->withPivot('position');
    }
}
