<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Huella ligera de la actividad de la bandeja de leads. La pagina la consulta cada pocos segundos
 * (resources/js/admin/leads-live.js); cuando cambia, vuelve a pedir la vista y refresca los paneles.
 * Son unas pocas consultas de agregado (max/count), sin cargar leads ni mensajes.
 */
class LeadInboxLiveController extends Controller
{
    public function fingerprint(): JsonResponse
    {
        $parts = [
            'leads_activity' => $this->aggregate('leads', 'max(last_activity_at)'),
            'leads_updated'  => $this->aggregate('leads', 'max(updated_at)'),
            'leads_count'    => $this->aggregate('leads', 'count(*)'),
            'wa_messages'    => $this->aggregate('whatsapp_messages', 'max(id)'),
            'wa_status'      => $this->aggregate('whatsapp_messages', 'max(updated_at)'),
            'notes'          => $this->aggregate('lead_notes', 'max(id)'),
            'emails_in'      => $this->aggregate('lead_inbound_emails', 'max(id)'),
            'emails_out'     => $this->aggregate('lead_outbound_emails', 'max(id)'),
            'calls'          => $this->aggregate('lead_calls', 'max(id)'),
        ];

        return response()
            ->json(['fp' => md5(json_encode($parts)), 'at' => now()->toIso8601String()])
            ->header('Cache-Control', 'no-store');
    }

    /** Si una tabla aun no existe (migracion pendiente) simplemente no aporta a la huella. */
    private function aggregate(string $table, string $expression): mixed
    {
        try {
            return DB::table($table)->selectRaw($expression . ' as v')->value('v');
        } catch (\Throwable) {
            return null;
        }
    }
}
