<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\QuoteMail;
use App\Models\Quote;
use App\Models\Products;
use App\Models\ServicePage;
use App\Services\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quoteService) {}

    public function index(Request $request)
    {
        $query = Quote::with('createdBy')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_company', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $quotes = $query->paginate(15)->withQueryString();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'quotes.index')
            ->value('columns');

        return view('admin.quotes.index', compact('quotes', 'visibleColumns'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name', 'email', 'phone', 'rfc', 'company', 'tipo_persona')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        // Tipo de cambio USD→MXN vigente al momento de crear el formulario —
        // solo un valor por defecto para el campo editable; se guarda lo que
        // el usuario deje en el campo al momento de enviar el form, no esto.
        $defaultExchangeRate = Products::exchangeRate();

        return view('admin.quotes.create', compact('customers', 'defaultExchangeRate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'guest_name'       => 'required|string|max:180',
            'guest_email'      => 'nullable|email|max:255',
            'guest_phone'      => 'nullable|string|max:30',
            'guest_company'    => 'nullable|string|max:255',
            'guest_rfc'        => 'nullable|string|max:20',
            'valid_until'      => 'nullable|date',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'discount_total'   => 'nullable|numeric|min:0',
            // FIX: ISR retention — only meaningful (and only enabled in the
            // UI) when the selected customer is persona moral, but always
            // validated as a plain optional percentage server-side.
            'isr_retention_rate' => 'nullable|numeric|min:0|max:100',
            'currency'         => 'required|in:MXN,USD',
            'exchange_rate'    => 'required|numeric|min:0.01',
            'notes'            => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items_json'       => 'required|json',
        ]);

        $items = json_decode($request->items_json, true);

        if (empty($items)) {
            return back()->withErrors(['items_json' => 'Debe agregar al menos un producto.'])->withInput();
        }

        $data = $request->only([
            'customer_id', 'guest_name', 'guest_email', 'guest_phone', 'guest_company',
            'guest_rfc', 'valid_until', 'tax_rate', 'discount_total', 'isr_retention_rate',
            'currency', 'exchange_rate', 'notes', 'terms_conditions',
        ]);
        $data['items'] = $items;

        $quote = $this->quoteService->store($data, auth()->id());

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', "Cotización {$quote->quote_number} creada exitosamente.");
    }

    public function show(Quote $quote)
    {
        $quote->load('items', 'createdBy', 'customer');

        $customers = $quote->customer_id ? collect() : \App\Models\Customer::select('id', 'first_name', 'last_name', 'company')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('admin.quotes.show', compact('quote', 'customers'));
    }

    public function edit(Quote $quote)
    {
        if (!$quote->isEditable(auth()->user())) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Esta cotización ya no se puede editar en su estatus actual — solo un administrador puede hacerlo.');
        }

        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name', 'email', 'phone', 'rfc', 'company', 'tipo_persona')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $quote->load('items');

        // Fallback para cotizaciones que aún no tienen exchange_rate guardado
        // (nullable, capturadas antes de esta fase) — el campo en la vista
        // sigue siendo editable, esto solo define qué se muestra por defecto.
        $defaultExchangeRate = Products::exchangeRate();

        return view('admin.quotes.edit', compact('quote', 'customers', 'defaultExchangeRate'));
    }

    public function update(Request $request, Quote $quote)
    {
        if (!$quote->isEditable(auth()->user())) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Esta cotización ya no se puede editar en su estatus actual — solo un administrador puede hacerlo.');
        }

        $request->validate([
            'customer_id'      => 'nullable|exists:customers,id',
            'guest_name'       => 'required|string|max:180',
            'guest_email'      => 'nullable|email|max:255',
            'guest_phone'      => 'nullable|string|max:30',
            'guest_company'    => 'nullable|string|max:255',
            'guest_rfc'        => 'nullable|string|max:20',
            'valid_until'      => 'nullable|date',
            'tax_rate'         => 'required|numeric|min:0|max:100',
            'discount_total'   => 'nullable|numeric|min:0',
            'isr_retention_rate' => 'nullable|numeric|min:0|max:100',
            'currency'         => 'required|in:MXN,USD',
            'exchange_rate'    => 'required|numeric|min:0.01',
            'notes'            => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items_json'       => 'required|json',
        ]);

        $items = json_decode($request->items_json, true);

        if (empty($items)) {
            return back()->withErrors(['items_json' => 'Debe agregar al menos un producto.'])->withInput();
        }

        $data = $request->only([
            'customer_id', 'guest_name', 'guest_email', 'guest_phone', 'guest_company',
            'guest_rfc', 'valid_until', 'tax_rate', 'discount_total', 'isr_retention_rate',
            'currency', 'exchange_rate', 'notes', 'terms_conditions',
        ]);
        $data['items'] = $items;

        $this->quoteService->update($quote, $data);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', "Cotización {$quote->quote_number} actualizada exitosamente.");
    }

    public function destroy(Quote $quote)
    {
        $quoteNumber = $quote->quote_number;
        $quote->update(['status' => 'rejected']);

        return redirect()->route('admin.quotes.index')
            ->with('success', "Cotización {$quoteNumber} cancelada.");
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');

        // Moneda del documento (Cotización) y tipo de cambio ACTUAL del
        // campo del formulario — no el global — para convertir cada línea
        // de producto USD antes de mostrarla. Si no vienen (compatibilidad
        // con llamadas antiguas), cae al tipo de cambio global vigente.
        $documentCurrency = $request->get('currency', 'MXN');
        $exchangeRate = $request->filled('exchange_rate')
            ? (float) $request->get('exchange_rate')
            : Products::exchangeRate();

        $products = Products::with(['category:id,name', 'brand:id,name'])
            ->where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%")
                      ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$q}%"))
                      ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
            })
            ->select('id', 'name', 'sku', 'price', 'price_includes_tax', 'stock', 'cover_image_url', 'brand_id', 'category_id', 'currency')
            ->take(12)
            ->get()
            ->map(function ($p) use ($documentCurrency, $exchangeRate) {
                // Precio base (sin IVA) en la moneda NATIVA del producto —
                // el % de IVA es agnóstico a la moneda, así que primero se
                // extrae el impuesto en la moneda propia del producto y solo
                // AL FINAL se convierte, si la moneda del documento difiere
                // de la del producto. No se reusa base_price (accessor):
                // ese siempre pasa por MXN con el tipo de cambio GLOBAL
                // horneado, lo que daba un resultado incorrecto tanto cuando
                // el documento ya era USD y el producto también (se
                // convertía de más) como cuando el documento era USD y el
                // producto MXN (no se convertía en absoluto).
                $rawPrice = (float) $p->price;
                $ivaRate = Products::ivaRate();
                $nativePrice = $p->price_includes_tax
                    ? round($rawPrice / (1 + $ivaRate / 100), 2)
                    : round($rawPrice, 2);

                if ($documentCurrency === $p->currency) {
                    $price = $nativePrice;
                } elseif ($documentCurrency === 'MXN' && $p->currency === 'USD') {
                    $price = round($nativePrice * $exchangeRate, 2);
                } else {
                    // $documentCurrency === 'USD' && $p->currency === 'MXN'
                    $price = round($nativePrice / $exchangeRate, 2);
                }

                return [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'sku'       => $p->sku,
                    // Siempre sin IVA — la cotización aplica su propio tax_rate
                    // encima, así que nunca se debe cobrar el impuesto dos veces.
                    'price'     => $price,
                    'stock'     => $p->stock,
                    'image_url' => $p->cover_image_url,
                    'category'  => $p->category?->name,
                    'brand'     => $p->brand?->name,
                ];
            });

        return response()->json($products);
    }

    /**
     * Buscador inline de Servicios (ServicePage) para agregar como línea de
     * cotización — análogo a searchProducts() pero sobre el catálogo de
     * servicios, que no maneja stock ni SKU.
     */
    public function searchServices(Request $request)
    {
        $q = $request->get('q', '');

        $services = ServicePage::where('is_active', 1)
            ->where('name', 'like', "%{$q}%")
            ->select('id', 'name', 'price', 'cover_image_url')
            ->take(12)
            ->get()
            ->map(fn ($s) => [
                'id'        => $s->id,
                'name'      => $s->name,
                'price'     => (float) ($s->price ?? 0),
                'image_url' => $s->cover_image_url,
            ]);

        return response()->json($services);
    }

    public function downloadPdf(Quote $quote)
    {
        $quote->load('items', 'createdBy');
        $pdf = Pdf::loadView('admin.quotes.pdf', compact('quote'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("{$quote->quote_number}.pdf");
    }

    public function previewPdf(Quote $quote)
    {
        $quote->load('items', 'createdBy');
        $pdf = Pdf::loadView('admin.quotes.pdf', compact('quote'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("{$quote->quote_number}.pdf");
    }

    public function sendEmail(Quote $quote)
    {
        $quote->load('items', 'createdBy');

        Mail::to($quote->guest_email)->send(new QuoteMail($quote));

        $quote->update(['status' => 'sent']);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', 'Cotización enviada por correo exitosamente.');
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected,expired',
        ]);

        // Guard de idempotencia: si ya estaba aceptada, un reenvío del mismo
        // cambio de estado no debe generar un segundo Pedido. Todo en una
        // transacción: si processAcceptance() falla, el cambio de estado se
        // revierte también, para no dejar la cotización marcada "accepted"
        // sin su Pedido y sin poder reintentar (el guard de arriba bloquearía
        // un segundo intento si el status ya hubiera quedado guardado).
        $wasAccepted = $quote->status === 'accepted';

        DB::transaction(function () use ($quote, $request, $wasAccepted) {
            $quote->update(['status' => $request->status]);

            if ($request->status === 'accepted' && !$wasAccepted) {
                $this->quoteService->processAcceptance($quote);
            }
        });

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', 'Estado de la cotización actualizado.');
    }

    // ── Vincular cliente (solo admin) ───────────────────────────────────────────
    // Para cotizaciones que quedaron sin cliente y ya no pueden editarse por su
    // estado (p. ej. aceptadas) — asigna únicamente el vínculo, sin tocar el
    // resto de la cotización.
    public function attachCustomer(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $quote->update(['customer_id' => $validated['customer_id']]);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', 'Cliente vinculado correctamente.');
    }
}
