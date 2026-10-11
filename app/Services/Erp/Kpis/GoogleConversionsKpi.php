<?php

namespace App\Services\Erp\Kpis;

use App\Models\GoogleConversionUpload;

class GoogleConversionsKpi
{
    /** Conversiones fallidas (agotaron los 3 intentos). */
    public static function kpi(): ?array
    {
        $n = GoogleConversionUpload::where('status', 'fallida')->count();

        return [
            'value' => $n,
            'label' => $n === 1 ? 'conversión fallida' : 'conversiones fallidas',
            'tone'  => $n === 0 ? 'ok' : 'bad',
        ];
    }
}
