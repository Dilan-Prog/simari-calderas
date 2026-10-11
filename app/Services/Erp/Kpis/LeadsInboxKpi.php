<?php

namespace App\Services\Erp\Kpis;

use App\Models\Lead;

class LeadsInboxKpi
{
    /** Leads nuevos sin atender (sin responsable ni primer contacto). */
    public static function kpi(): ?array
    {
        $n = Lead::where('status', 'nuevo')->whereNull('first_contact_at')->count();

        return [
            'value' => $n,
            'label' => $n === 1 ? 'lead nuevo sin atender' : 'leads nuevos sin atender',
            'tone'  => $n === 0 ? 'ok' : ($n > 10 ? 'bad' : 'warn'),
        ];
    }
}
