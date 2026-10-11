<?php

namespace App\Services\Erp\Kpis;

use App\Models\TechnicalService;
use Illuminate\Support\Facades\Schema;

/** KPI del Launchpad: servicios de hoy / próximos 7 días. */
class ServiceCalendarKpi
{
    public static function kpi(): ?array
    {
        if (!Schema::hasTable('services')) {
            return null;
        }

        $active = fn () => TechnicalService::whereNotIn('status', ['cancelled', 'completed', 'draft']);
        $today = $active()->whereDate('service_date', now()->toDateString())->count();
        $week = $active()->whereBetween('service_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->count();

        return ['value' => $today . ' / ' . $week, 'label' => 'servicios hoy / próximos 7 días', 'tone' => $today > 0 ? 'warn' : 'neutral'];
    }
}
