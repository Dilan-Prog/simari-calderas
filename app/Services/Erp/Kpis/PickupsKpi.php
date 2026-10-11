<?php

namespace App\Services\Erp\Kpis;

use App\Models\Pickup;
use Illuminate\Support\Facades\Schema;

class PickupsKpi
{
    public static function kpi(): ?array
    {
        if (! Schema::hasTable('pickups')) {
            return null;
        }

        $incidents = Pickup::where('status', 'incidencia')->count();
        if ($incidents > 0) {
            return ['value' => $incidents, 'label' => 'con incidencia', 'tone' => 'bad'];
        }

        $today = Pickup::whereDate('scheduled_date', today())
            ->whereIn('status', ['programada', 'en_ruta', 'recolectada'])->count();

        return ['value' => $today, 'label' => 'recolecciones hoy', 'tone' => $today ? 'warn' : 'neutral'];
    }
}
