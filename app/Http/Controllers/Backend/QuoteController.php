<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\QuoteMail;
use App\Models\Quote;
use App\Models\Products;
use App\Services\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.quotes.index', compact('quotes'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name', 'email', 'phone', 'rfc', 'company', 'tipo_persona')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('admin.quotes.create', compact('customers'));
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
            'notes', 'terms_conditions',
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
        if (!$quote->isEditable()) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Solo se pueden editar cotizaciones en estado Borrador o Enviada.');
        }

        $customers = \App\Models\Customer::select('id', 'first_name', 'last_name', 'email', 'phone', 'rfc', 'company', 'tipo_persona')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $quote->load('items');
        return view('admin.quotes.edit', compact('quote', 'customers'));
    }

    public function update(Request $request, Quote $quote)
    {
        if (!$quote->isEditable()) {
            return redirect()->route('admin.quotes.show', $quote)
                ->with('error', 'Solo se pueden editar cotizaciones en estado Borrador o Enviada.');
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
            'notes', 'terms_conditions',
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

        $products = Products::with(['category:id,name', 'brand:id,name'])
            ->where('is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%")
                      ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$q}%"))
                      ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
            })
            ->select('id', 'name', 'sku', 'price', 'stock', 'cover_image_url', 'brand_id', 'category_id')
            ->take(12)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'sku'       => $p->sku,
                'price'     => $p->price,
                'stock'     => $p->stock,
                'image_url' => $p->cover_image_url,
                'category'  => $p->category?->name,
                'brand'     => $p->brand?->name,
            ]);

        return response()->json($products);
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

        $quote->update(['status' => $request->status]);

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
