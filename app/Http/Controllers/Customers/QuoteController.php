<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\TechnicalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * "Cotizaciones" del portal de servicios: historial de cotizaciones del propio
 * cliente. Solo lectura. Nunca se muestran borradores, ni las notas internas
 * (`notes`), ni datos internos (created_by_user_id, costos).
 *
 * El parametro de ruta {quote} llega sin tipo (no hay binding implicito): se
 * resuelve aqui siempre acotado al cliente autenticado y a los estados
 * visibles, asi que la cotizacion de otro cliente o un borrador responde 404.
 */
class QuoteController extends Controller
{
    /** Estados que el cliente puede ver (nunca 'draft'). */
    public const VISIBLE_STATUSES = ['sent', 'accepted', 'rejected', 'expired'];

    private const PER_PAGE = 15;

    public function index(Request $request)
    {
        $customerId = auth('customer')->id();
        $today      = now()->startOfDay();

        $request->validate([
            'status' => ['nullable', 'in:sent,accepted,rejected,expired'],
            'year'   => ['nullable', 'integer', 'between:2000,2100'],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        $status = (string) $request->query('status', '');
        $year   = $request->filled('year') ? (int) $request->query('year') : null;
        $search = trim((string) $request->query('q', ''));

        $base = Quote::query()
            ->where('customer_id', $customerId)
            ->whereIn('status', self::VISIBLE_STATUSES);

        // Tarjetas de resumen: sobre todas sus cotizaciones visibles (sin filtros).
        $stats = (clone $base)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'accepted') as accepted")
            ->selectRaw("SUM(status = 'sent' AND (valid_until IS NULL OR valid_until >= ?)) as waiting", [$today->toDateString()])
            ->selectRaw("SUM(status = 'rejected' OR status = 'expired' OR (status = 'sent' AND valid_until < ?)) as closed", [$today->toDateString()])
            ->first();

        // Anios disponibles para el filtro (solo de sus cotizaciones).
        $years = (clone $base)
            ->selectRaw('YEAR(COALESCE(sent_at, created_at)) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();

        $quotes = (clone $base)
            ->select([
                'id', 'quote_number', 'customer_id', 'status', 'currency', 'total',
                'valid_until', 'sent_at', 'accepted_at', 'rejected_at', 'created_at',
            ])
            ->withCount('items')
            ->when($status === 'sent', fn ($q) => $q
                ->where('status', 'sent')
                ->where(fn ($w) => $w->whereNull('valid_until')->orWhere('valid_until', '>=', $today->toDateString())))
            ->when($status === 'expired', fn ($q) => $q
                ->where(fn ($w) => $w->where('status', 'expired')
                    ->orWhere(fn ($s) => $s->where('status', 'sent')->where('valid_until', '<', $today->toDateString()))))
            ->when(in_array($status, ['accepted', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->when($year, fn ($q) => $q->whereRaw('YEAR(COALESCE(sent_at, created_at)) = ?', [$year]))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_') . '%';
                $q->where(function ($w) use ($like) {
                    $w->where('quote_number', 'like', $like)
                      ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like));
                });
            })
            ->orderByRaw('COALESCE(sent_at, created_at) desc')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('customers.quotes.index', [
            'quotes' => $quotes,
            'stats'  => [
                'total'    => (int) ($stats->total ?? 0),
                'accepted' => (int) ($stats->accepted ?? 0),
                'waiting'  => (int) ($stats->waiting ?? 0),
                'closed'   => (int) ($stats->closed ?? 0),
            ],
            'years'  => $years,
            'year'   => $year,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(string|int $quote)
    {
        $quote = $this->resolve($quote);

        $quote->load([
            'items.product:id,name,cover_image_url,cover_image_alt',
            'salesOrders:id,order_number,quote_id,customer_id,status',
        ]);

        $services = TechnicalService::query()
            ->where('customer_id', $quote->customer_id)
            ->where('from_quote_id', $quote->id)
            ->select(['id', 'service_number', 'customer_id', 'service_type_id', 'service_date', 'status', 'from_quote_id'])
            ->with('serviceType:id,name')
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        return view('customers.quotes.show', [
            'quote'    => $quote,
            'services' => $services,
        ]);
    }

    public function pdf(Request $request, string|int $quote)
    {
        $quote = $this->resolve($quote);

        $quote->load('items');

        // Las notas internas nunca llegan al cliente (solo en memoria, no se guarda).
        $quote->notes = null;

        $pdf = Pdf::loadView('admin.quotes.pdf', ['quote' => $quote])
            ->setPaper('a4', 'portrait');

        $filename = "{$quote->quote_number}.pdf";

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /** Cotizacion visible del cliente autenticado, o 404. */
    private function resolve(string|int $quote): Quote
    {
        return Quote::query()
            ->where('customer_id', auth('customer')->id())
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->findOrFail($quote);
    }
}
