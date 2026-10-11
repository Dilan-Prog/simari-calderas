<?php

namespace App\Services\Erp\Kpis;

use App\Models\Payment;

class PaymentsKpi
{
    public static function kpi(): ?array
    {
        $n = Payment::whereIn('status', ['pendiente', 'aplicado'])->count();

        return [
            'value' => $n,
            'label' => 'por conciliar',
            'tone'  => $n > 0 ? 'warn' : 'ok',
        ];
    }
}
