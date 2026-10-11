<?php

namespace App\Services\Erp\Kpis;

use App\Models\StockCount;

class StockCountsKpi
{
    public static function kpi(): ?array
    {
        $n = StockCount::whereIn('status', ['abierto', 'en_conteo'])->count();

        return ['value' => $n, 'label' => 'conteos abiertos', 'tone' => $n > 0 ? 'warn' : 'ok'];
    }
}
