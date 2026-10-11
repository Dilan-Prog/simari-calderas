<?php

namespace App\Services\Erp\Kpis;

use App\Models\MaterialRequirement;
use Illuminate\Support\Facades\Schema;

/** KPI del Launchpad: requerimientos con faltante / requerimientos en compra. */
class MrpKpi
{
    public static function kpi(): ?array
    {
        if (!Schema::hasTable('material_requirements')) {
            return null;
        }

        $withShortfall = MaterialRequirement::whereNotIn('status', ['cancelado', 'cubierto'])
            ->whereHas('lines', fn ($q) => $q->where('shortfall', '>', 0))->count();
        $buying = MaterialRequirement::where('status', 'en_compra')->count();

        return [
            'value' => $withShortfall . ' / ' . $buying,
            'label' => 'requerimientos con faltante / en compra',
            'tone'  => $withShortfall > 0 ? 'warn' : ($buying > 0 ? 'neutral' : 'ok'),
        ];
    }
}
