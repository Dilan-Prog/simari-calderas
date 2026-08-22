<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\MarketingEmailMailable;
use App\Models\EmailTemplate;
use App\Models\Quote;
use App\Models\Products;
use App\Models\ServicePage;
use App\Services\EmailTemplateService;
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
        $this->resolveVisitorUuidForQuote($quote);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', "Cotización {$quote->quote_number} creada exitosamente.");
    }

    /**
     * Resuelve y guarda visitor_uuid/visitor_uuid_confidence de una
     * cotización recién creada/editada, para atribución de conversiones de
     * Google Ads:
     *  - Cliente identificado con visitor_uuid propio -> copia exacta.
     *  - Invitado (guest_email/guest_phone) -> busca en carts un
     *    contact_email/contact_phone que coincida (normalizado) y que ya
     *    tenga visitor_uuid, tomando el más reciente si hay varios.
     *  - Sin match en ninguno de los dos casos: deja ambos campos en null,
     *    no es un error.
     */
    private function resolveVisitorUuidForQuote(Quote $quote): void
    {
        if ($quote->customer_id) {
            $customer = \App\Models\Customer::find($quote->customer_id);

            if ($customer && $customer->visitor_uuid !== null) {
                $quote->update([
                    'visitor_uuid'            => $customer->visitor_uuid,
                    'visitor_uuid_confidence' => 'exact',
                ]);
            }

            return;
        }

        $cart = null;

        if ($quote->guest_email) {
            $email = strtolower(trim($quote->guest_email));

            $cart = \App\Models\Cart::whereNotNull('visitor_uuid')
                ->whereRaw('LOWER(TRIM(contact_email)) = ?', [$email])
                ->latest('id')
                ->first();
        }

        if (!$cart && $quote->guest_phone) {
            $phone = preg_replace('/\D/', '', $quote->guest_phone);

            if ($phone !== '') {
                $cart = \App\Models\Cart::whereNotNull('visitor_uuid')
                    ->whereNotNull('contact_phone')
                    ->latest('id')
                    ->get()
                    ->first(fn ($c) => preg_replace('/\D/', '', $c->contact_phone) === $phone);
            }
        }

        if ($cart) {
            $quote->update([
                'visitor_uuid'            => $cart->visitor_uuid,
                'visitor_uuid_confidence' => 'matched_by_contact',
            ]);
        }
    }

    public function show(Quote $quote)
    {
        $quote->load('items', 'createdBy', 'customer');

        $customers = $quote->customer_id ? collect() : \App\Models\Customer::select('id', 'first_name', 'last_name', 'company')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $activities = $this->quoteActivities($quote);

        return view('admin.quotes.show', compact('quote', 'customers', 'activities'));
    }

    /**
     * Línea de tiempo de envíos de esta cotización, combinando dos fuentes
     * que no se tocan entre sí en el resto del código:
     *  - Envíos MANUALES (botón "Enviar por correo" o el selector "Cambiar
     *    Estado" -> Enviada): ambos terminan en el mismo $quote->update(...),
     *    que LogsActivity ya registra genéricamente en SystemLog como un
     *    'updated' -- se filtra por la presencia de 'sent_at' en new_value
     *    (la señal confiable de "aquí hubo un (re)envío", a diferencia de
     *    'status' que puede no cambiar si ya estaba en 'sent').
     *  - Recordatorios AUTOMÁTICOS (workflow tipo 'quote'): WorkflowEnrollment
     *    de esta cotización -> WorkflowEnrollmentLog con action_taken
     *    send_email/send_whatsapp_message.
     * No unifica en una sola tabla a propósito -- son dos mecanismos ya
     * existentes en el sistema, esto solo los combina para mostrarlos.
     */
    private function quoteActivities(Quote $quote): \Illuminate\Support\Collection
    {
        $manual = \App\Models\SystemLog::where('entity_type', 'quote')
            ->where('entity_id', $quote->id)
            ->where('action', 'updated')
            ->with('performedBy')
            ->get()
            ->filter(fn ($log) => array_key_exists('sent_at', $log->new_value ?? []))
            ->map(fn ($log) => [
                'type'      => 'manual',
                'label'     => 'Cotización enviada manualmente',
                'detail'    => $log->performedBy ? "por {$log->performedBy->full_name}" : null,
                'at'        => $log->performed_at,
                'preview'    => 'email',
                'preview_id' => null,
            ]);

        $enrollmentIds = \App\Models\WorkflowEnrollment::where('enrollable_type', Quote::class)
            ->where('enrollable_id', $quote->id)
            ->pluck('workflow_id', 'id');

        $automated = \App\Models\WorkflowEnrollmentLog::whereIn('enrollment_id', $enrollmentIds->keys())
            ->whereIn('action_taken', ['send_email', 'send_whatsapp_message'])
            ->with('enrollment.workflow')
            ->get()
            ->map(function ($log) {
                $channel = $log->action_taken === 'send_email' ? 'correo' : 'WhatsApp';
                $workflowName = $log->enrollment?->workflow?->name ?? 'Automatización';
                $resultLabel = match ($log->result) {
                    'success' => 'enviado',
                    'skipped' => 'omitido',
                    'failed'  => 'falló',
                    default   => $log->result,
                };

                return [
                    'type'       => 'automated',
                    'label'      => "Recordatorio automático por {$channel} — {$resultLabel}",
                    'detail'     => "{$workflowName}" . ($log->message ? " · {$log->message}" : ''),
                    'at'         => $log->logged_at,
                    'preview'    => $log->result === 'success' ? ($log->action_taken === 'send_email' ? 'email' : 'whatsapp') : null,
                    'preview_id' => $log->id,
                ];
            });

        return $manual->concat($automated)->sortByDesc('at')->values();
    }

    /**
     * Vista previa del correo ENVIADO MANUALMENTE (botón "Enviar por correo"
     * / "Marcar como Enviada"). QuoteMail no varía entre envíos -- siempre
     * renderiza con los datos ACTUALES de la cotización, así que no hace
     * falta un id de log específico, solo re-renderiza el mismo Mailable
     * que ya se usó para enviar.
     */
    public function previewManualEmail(Quote $quote)
    {
        $quote->load('items', 'createdBy', 'customer');

        return $this->renderQuoteManualEmail($quote)['html'];
    }

    /**
     * Renderiza el correo de "Enviar cotización" a partir de la EmailTemplate
     * del sistema (system_key='quote_manual_send', ver migración
     * 2026_08_20_010001) -- el usuario la puede editar libremente desde
     * Email Marketing, pero no eliminar (EmailTemplateController::destroy()).
     */
    private function renderQuoteManualEmail(Quote $quote): array
    {
        $template = EmailTemplate::where('system_key', 'quote_manual_send')->firstOrFail();

        return app(EmailTemplateService::class)->render(
            $template,
            $quote->customer,
            null,
            $quote,
            $quote->customer ? null : $quote->guest_name,
            $quote->customer ? null : $quote->guest_email,
        );
    }

    /**
     * Vista previa del correo de un recordatorio AUTOMÁTICO específico,
     * reconstruido con el mismo mecanismo que SendMarketingEmailJob (mismo
     * template + mismos datos de destinatario), a partir del step que
     * generó el WorkflowEnrollmentLog indicado.
     */
    public function previewAutomatedEmail(Quote $quote, int $log)
    {
        $enrollmentLog = \App\Models\WorkflowEnrollmentLog::with('step', 'enrollment.enrollable')->findOrFail($log);
        $step = $enrollmentLog->step;
        $templateId = $step?->action_config['template_id'] ?? null;
        $template = $templateId ? \App\Models\EmailTemplate::find($templateId) : null;

        if (!$template) {
            abort(404, 'No se encontró la plantilla usada en este envío.');
        }

        $enrollable = $enrollmentLog->enrollment?->enrollable;
        $customer = $quote->customer;
        $isGuest = !$customer;

        $rendered = app(\App\Services\EmailTemplateService::class)->render(
            $template,
            $customer,
            null,
            $enrollable instanceof Quote ? $enrollable : $quote,
            $isGuest ? $quote->guest_name : null,
            $isGuest ? $quote->guest_email : null,
        );

        return $rendered['html'];
    }

    /**
     * Vista previa del texto de WhatsApp de un recordatorio automático
     * específico, con los mismos tokens ya resueltos (mismo resolver que
     * usa el motor al enviarlo de verdad).
     */
    public function previewAutomatedWhatsapp(Quote $quote, int $log)
    {
        $enrollmentLog = \App\Models\WorkflowEnrollmentLog::with('step', 'enrollment')->findOrFail($log);
        $step = $enrollmentLog->step;
        $text = $step?->action_config['text'] ?? null;

        $resolved = $enrollmentLog->enrollment
            ? app(\App\Services\WorkflowTokenResolver::class)->resolveTokens($text, $enrollmentLog->enrollment)
            : $text;

        return response()->json(['text' => $resolved]);
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
        $this->resolveVisitorUuidForQuote($quote->fresh());

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
        $quote->load('items', 'createdBy', 'customer');

        $recipient = $quote->customer?->email ?: $quote->guest_email;

        $rendered = $this->renderQuoteManualEmail($quote);

        $pdf = Pdf::loadView('admin.quotes.pdf', ['quote' => $quote])->setPaper('a4', 'portrait');

        Mail::to($recipient)->send(new MarketingEmailMailable($rendered, [
            'content'  => $pdf->output(),
            'filename' => "{$quote->quote_number}.pdf",
            'mime'     => 'application/pdf',
        ]));

        // sent_at SIEMPRE se actualiza a "ahora" (no solo la primera vez):
        // un reenvío manual reinicia el reloj de 24h del recordatorio
        // automático de seguimiento (Workflow tipo 'quote', trigger stale),
        // igual que si la cotización se acabara de enviar por primera vez.
        $quote->update(['status' => 'sent', 'sent_at' => now()]);

        app(\App\Services\WorkflowEngineService::class)->resetCompletedFollowUps($quote, 'quote');

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', 'Cotización enviada por correo exitosamente.');
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate([
            'status' => 'required|in:sent,accepted,rejected,expired',
        ]);

        // Guard de idempotencia: si ya estaba aceptada, un reenvío del mismo
        // cambio de estado no debe generar un segundo Pedido. Todo en una
        // transacción: si processAcceptance() falla, el cambio de estado se
        // revierte también, para no dejar la cotización marcada "accepted"
        // sin su Pedido y sin poder reintentar (el guard de arriba bloquearía
        // un segundo intento si el status ya hubiera quedado guardado).
        $wasAccepted = $quote->status === 'accepted';

        DB::transaction(function () use ($quote, $request, $wasAccepted) {
            $attributes = ['status' => $request->status];

            // Igual que sendEmail(): marcar manualmente "Enviada" reinicia el
            // reloj de 24h del seguimiento automático, como si se acabara de
            // enviar. No aplica al resto de estados.
            if ($request->status === 'sent') {
                $attributes['sent_at'] = now();
            }

            $quote->update($attributes);

            if ($request->status === 'sent') {
                app(\App\Services\WorkflowEngineService::class)->resetCompletedFollowUps($quote, 'quote');
            }

            if ($request->status === 'accepted' && !$wasAccepted) {
                $this->quoteService->processAcceptance($quote);

                $adVisit = $quote->visitor_uuid !== null ? $quote->adVisit : null;

                // Solo si de verdad hay gclid/wbraid que reportar -- una
                // visita solo con UTM (sin gclid/wbraid) no aporta nada útil
                // para subir a Google Ads, y crearía ruido en la tabla.
                if ($adVisit && ($adVisit->gclid || $adVisit->wbraid)) {
                    try {
                        \App\Models\GoogleConversion::create([
                            'gclid'            => $adVisit->gclid,
                            'wbraid'           => $adVisit->wbraid,
                            'conversion_name'  => 'Cotizado',
                            'conversion_value' => $quote->total,
                            'currency_code'    => $quote->currency ?? 'MXN',
                            'conversion_time'  => now(),
                            // Prefijo para no chocar con el order_id de
                            // StoreOrder en la unique constraint.
                            'order_id'         => 'QUOTE-' . $quote->quote_number,
                            'status'           => 'stored',
                        ]);
                    } catch (\Throwable $e) {
                        // silencioso a propósito -- no debe tronar el flujo
                        // real de aceptar la cotización (p. ej. reintento
                        // que choca con el unique de order_id).
                    }
                }
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
