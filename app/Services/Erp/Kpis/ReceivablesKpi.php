<?php

namespace App\Services\Erp\Kpis;

use App\Services\FinanceService;

class ReceivablesKpi
{
    public static function kpi(): ?array
    {
        $s = app(FinanceService::class)->summary('receivable');

        return [
            'value' => '$' . number_format($s['open'], 0),
            'label' => $s['overdue_count'] > 0 ? "por cobrar · {$s['overdue_count']} vencidas" : 'por cobrar',
            'tone'  => $s['overdue_count'] > 0 ? 'bad' : 'ok',
        ];
    }
}
