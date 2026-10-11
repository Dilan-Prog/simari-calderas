<?php

namespace App\Services\Erp\Kpis;

use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Schema;

/** KPI del Launchpad: órdenes vencidas / en proceso. */
class ProductionOrdersKpi
{
    public static function kpi(): ?array
    {
        if (!Schema::hasTable('production_orders')) {
            return null;
        }

        $overdue = ProductionOrder::overdue()->count();
        $running = ProductionOrder::where('status', 'en_proceso')->count();

        return [
            'value' => $overdue . ' / ' . $running,
            'label' => 'órdenes vencidas / en proceso',
            'tone'  => $overdue > 0 ? 'bad' : ($running > 0 ? 'warn' : 'ok'),
        ];
    }
}
