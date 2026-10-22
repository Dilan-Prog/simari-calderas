<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Quote;
use App\Services\LeadDealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Negocio del lead desde la bandeja: se crea solo si el lead ya es cliente (con los datos del cliente) y se le
 * ligan cotizaciones del mismo cliente; el monto del negocio sale de la cotizacion mas reciente.
 */
class LeadDealController extends Controller
{
    public function __construct(private LeadDealService $deals)
    {
    }

    private function back(Lead $lead): RedirectResponse
    {
        return redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id]);
    }

    public function store(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'nullable|string|max:255',
            'amount'              => 'nullable|numeric|min:0',
            'currency'            => 'nullable|in:MXN,USD',
            'expected_close_date' => 'nullable|date',
            'owner_id'            => 'nullable|exists:users,id',
            'notes'               => 'nullable|string|max:2000',
        ]);

        try {
            $deal = $this->deals->createForLead($lead, $data, $request->user());
        } catch (ValidationException $e) {
            return $this->back($lead)->with('error', collect($e->errors())->flatten()->first());
        }

        return $this->back($lead)->with('success', 'Negocio ' . $deal->folio . ' creado con los datos del cliente.');
    }

    public function linkQuote(Request $request, Lead $lead): RedirectResponse
    {
        $request->validate(['quote_id' => 'nullable|integer', 'quote_number' => 'nullable|string|max:40']);

        $deal = $lead->deal;
        if (! $deal) {
            return $this->back($lead)->with('error', 'Este lead todavía no tiene negocio.');
        }

        $quote = $request->filled('quote_id')
            ? Quote::find($request->integer('quote_id'))
            : Quote::where('quote_number', strtoupper(trim((string) $request->input('quote_number'))))->first();
        if (! $quote) {
            return $this->back($lead)->with('error', 'No se encontró esa cotización.');
        }
        if ((int) $quote->customer_id !== (int) $lead->customer_id) {
            return $this->back($lead)->with('error', 'La cotización ' . $quote->quote_number . ' pertenece a otro cliente; solo se pueden ligar las del cliente de este lead.');
        }

        $this->deals->attachQuote($deal, $quote);

        return $this->back($lead)->with('success', 'Cotización ' . $quote->quote_number . ' ligada al negocio ' . $deal->folio . '.');
    }

    public function unlinkQuote(Lead $lead, Quote $quote): RedirectResponse
    {
        if ($deal = $lead->deal) {
            $this->deals->detachQuote($deal, $quote);
        }

        return $this->back($lead)->with('success', 'Cotización ' . $quote->quote_number . ' quitada del negocio.');
    }
}
