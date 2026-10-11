<?php

namespace App\Services\Erp\Kpis;

use App\Services\FinanceService;

class PayablesKpi
{
    public static function kpi(): ?array
    {
        $s = app(FinanceService::class)->summary('payable');

        return [
            'value' => '$' . number_format($s['open'], 0),
            'label' => $s['overdue_count'] > 0 ? "por pagar · {$s['overdue_count']} vencidas" : 'por pagar',
            'tone'  => $s['overdue_count'] > 0 ? 'bad' : 'neutral',
        ];
    }
}
