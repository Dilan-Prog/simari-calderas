<?php

namespace App\Services\Erp\Kpis;

use App\Models\WarehouseTransfer;

class WarehouseTransfersKpi
{
    public static function kpi(): ?array
    {
        $n = WarehouseTransfer::where('status', 'en_transito')->count();

        return ['value' => $n, 'label' => 'en tránsito', 'tone' => $n > 0 ? 'warn' : 'ok'];
    }
}
