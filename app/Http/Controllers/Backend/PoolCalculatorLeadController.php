<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PoolCalculatorLead;
use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Módulo admin mínimo de solo lectura sobre los leads generados por la
 * calculadora de bombas de calor para alberca (endpoint público, fuera de
 * este módulo) -- la única acción de escritura es vincular manualmente un
 * lead a la Cotización real que ventas terminó generando, para poder tirar
 * después del hilo visitor_uuid -> ad_visits.gclid y reportar la conversión.
 */
class PoolCalculatorLeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = PoolCalculatorLead::with(['adVisit', 'matchedQuote'])
            ->when($request->filled('ref'), fn ($q) => $q->where('ref', 'like', '%' . $request->input('ref') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));

        $leads = $query->latest('created_at')->paginate(30)->withQueryString();

        // Set acotado para el <select> de "Vincular a Cotización" -- alcance
        // intencionalmente simple (sin autocomplete AJAX): solo cotizaciones
        // aceptadas, las 50 más recientes.
        $quotesForMatch = Quote::with('customer')->where('status', 'accepted')->latest('id')->limit(50)->get();

        return view('admin.pool-calculator-leads.index', [
            'leads' => $leads,
            'quotesForMatch' => $quotesForMatch,
            'statuses' => PoolCalculatorLead::STATUSES,
            'filters' => [
                'ref' => (string) $request->input('ref', ''),
                'status' => (string) $request->input('status', ''),
                'from' => (string) $request->input('from', ''),
                'to' => (string) $request->input('to', ''),
            ],
        ]);
    }

    public function markMatched(Request $request, PoolCalculatorLead $lead): RedirectResponse
    {
        $request->validate([
            'quote_id' => ['required', 'exists:quotes,id'],
        ]);

        $lead->update([
            'status' => 'cotizado',
            'matched_quote_id' => $request->input('quote_id'),
        ]);

        return back()->with('success', 'Lead vinculado a la cotización correctamente.');
    }
}
