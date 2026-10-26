<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Services\LeadDealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Acciones sobre un correo de la bandeja de leads: crear un negocio o una cotización a partir del correo recibido.
 * Un negocio / cotización exige que el lead ya sea cliente (no se crean clientes automáticamente).
 */
class LeadEmailActionsController extends Controller
{
    public function __construct(private LeadDealService $deals)
    {
    }

    private function inbox(Lead $lead): RedirectResponse
    {
        return redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id]);
    }

    /** Correo recibido indicado por email_id; null si no se indicó; false si no pertenece al lead. */
    private function inboundEmail(Request $request, Lead $lead): LeadInboundEmail|false|null
    {
        $id = $request->input('email_id');
        if ($id === null || $id === '') {
            return null;
        }
        if (! is_numeric($id)) {
            return false;
        }

        return LeadInboundEmail::where('lead_id', $lead->id)->whereKey((int) $id)->first() ?? false;
    }

    public function createDeal(Request $request, Lead $lead): RedirectResponse
    {
        $request->validate(['email_id' => 'nullable|integer']);

        $email = $this->inboundEmail($request, $lead);
        if ($email === false) {
            return $this->inbox($lead)->with('error', 'Ese correo no pertenece a este lead.');
        }

        if ($lead->deal_id && ($existing = Deal::find($lead->deal_id))) {
            return redirect()->route('admin.deals.show', $existing->id)
                ->with('success', 'Este lead ya tiene el negocio ' . $existing->folio . '.');
        }

        if (! $lead->customer_id) {
            return $this->inbox($lead)->with('error', 'Para crear un negocio primero agrega al lead como cliente: así el negocio nace con sus datos ya dados de alta.');
        }

        $data = [];
        if ($email) {
            $data['name'] = $this->dealName($email);
            $data['notes'] = $this->dealNotes($email);
        }

        try {
            $deal = $this->deals->createForLead($lead, $data, $request->user());
        } catch (ValidationException $e) {
            return $this->inbox($lead)->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('admin.deals.show', $deal->id)
            ->with('success', 'Negocio ' . $deal->folio . ' creado desde el correo.');
    }

    public function createQuote(Request $request, Lead $lead): RedirectResponse
    {
        $email = $this->inboundEmail($request, $lead);
        if ($email === false) {
            return $this->inbox($lead)->with('error', 'Ese correo no pertenece a este lead.');
        }

        if (! $lead->customer_id) {
            return $this->inbox($lead)->with('error', 'Para crear una cotización primero agrega al lead como cliente: así la cotización nace con sus datos ya dados de alta.');
        }

        $params = ['customer_id' => $lead->customer_id, 'from_lead' => $lead->id];
        if ($email) {
            $params['from_email'] = $email->id;
        }

        return redirect()->route('admin.quotes.create', $params);
    }

    /** Asunto del correo sin prefijos Re:/Fwd:, como nombre del negocio. */
    private function dealName(LeadInboundEmail $email): ?string
    {
        $subject = trim((string) preg_replace('/^(\s*(re|rv|fwd?|reenv)\s*:\s*)+/i', '', (string) $email->subject));

        return $subject !== '' ? Str::limit($subject, 150, '') : null;
    }

    private function dealNotes(LeadInboundEmail $email): string
    {
        $from = trim(($email->from_name ? $email->from_name . ' ' : '') . ($email->from_email ? '<' . $email->from_email . '>' : ''));
        $date = ($email->received_at ?? $email->created_at)?->format('d/m/Y H:i');
        $excerpt = trim((string) $email->excerpt);

        $lines = ['Creado desde un correo recibido.'];
        $lines[] = 'Asunto: ' . ($email->subject ?: '(sin asunto)');
        if ($from !== '') {
            $lines[] = 'De: ' . $from;
        }
        if ($date) {
            $lines[] = 'Fecha: ' . $date;
        }
        if ($excerpt !== '') {
            $lines[] = '';
            $lines[] = Str::limit($excerpt, 1200);
        }

        return implode("\n", $lines);
    }
}
