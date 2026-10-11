<?php

namespace App\Services\Erp\Kpis;

use App\Models\Bom;
use App\Models\BomLine;
use App\Models\Products;
use Illuminate\Support\Facades\Schema;

/** KPI del Launchpad: recetas vigentes / productos usados como subensamble sin receta. */
class BomsKpi
{
    public static function kpi(): ?array
    {
        if (!Schema::hasTable('boms')) {
            return null;
        }

        $active = Bom::where('is_active', true)->count();

        // Productos fabricados (con alguna receta) que no tienen ninguna vigente.
        $withoutActive = Products::whereIn('id', Bom::select('product_id'))
            ->whereNotIn('id', Bom::where('is_active', true)->select('product_id'))
            ->count();

        return [
            'value' => $active . ' / ' . $withoutActive,
            'label' => 'recetas vigentes / fabricados sin vigente',
            'tone'  => $withoutActive > 0 ? 'warn' : ($active > 0 ? 'ok' : 'neutral'),
        ];
    }
}
