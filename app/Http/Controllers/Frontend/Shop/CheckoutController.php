<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    // Catálogos reales del SAT (mismos códigos/etiquetas del mockup de
    // referencia) — estáticos, sin dependencia externa. Si el SAT publica
    // nuevos códigos, se actualizan aquí a mano.
    public const USO_CFDI_OPTIONS = [
        ['value' => 'G01', 'label' => 'G01 - Adquisición de mercancías'],
        ['value' => 'G02', 'label' => 'G02 - Devoluciones, descuentos o bonificaciones'],
        ['value' => 'G03', 'label' => 'G03 - Gastos en general'],
        ['value' => 'I01', 'label' => 'I01 - Construcciones'],
        ['value' => 'I02', 'label' => 'I02 - Mobiliario y equipo de oficina por inversiones'],
        ['value' => 'I03', 'label' => 'I03 - Equipo de transporte'],
        ['value' => 'I04', 'label' => 'I04 - Equipo de cómputo y accesorios'],
        ['value' => 'I05', 'label' => 'I05 - Dados, troqueles, moldes, matrices y herramental'],
        ['value' => 'I06', 'label' => 'I06 - Comunicaciones telefónicas'],
        ['value' => 'I07', 'label' => 'I07 - Comunicaciones satelitales'],
        ['value' => 'I08', 'label' => 'I08 - Otra maquinaria y equipo'],
        ['value' => 'D01', 'label' => 'D01 - Honorarios médicos, dentales y gastos hospitalarios'],
        ['value' => 'D02', 'label' => 'D02 - Gastos médicos por incapacidad o discapacidad'],
        ['value' => 'D03', 'label' => 'D03 - Gastos funerales'],
        ['value' => 'D04', 'label' => 'D04 - Donativos'],
        ['value' => 'D05', 'label' => 'D05 - Intereses hipotecarios (casa habitación)'],
        ['value' => 'D06', 'label' => 'D06 - Aportaciones voluntarias al SAR'],
        ['value' => 'D07', 'label' => 'D07 - Primas por seguros de gastos médicos'],
        ['value' => 'D08', 'label' => 'D08 - Gastos de transportación escolar obligatoria'],
        ['value' => 'D09', 'label' => 'D09 - Depósitos en cuentas para el ahorro, pensiones'],
        ['value' => 'D10', 'label' => 'D10 - Pagos por servicios educativos (colegiaturas)'],
        ['value' => 'S01', 'label' => 'S01 - Sin efectos fiscales'],
        ['value' => 'CP01', 'label' => 'CP01 - Pagos'],
        ['value' => 'CN01', 'label' => 'CN01 - Nómina'],
    ];

    public const REGIMEN_FISCAL_OPTIONS = [
        ['value' => '601', 'label' => '601 - General de Ley Personas Morales'],
        ['value' => '603', 'label' => '603 - Personas Morales con Fines no Lucrativos'],
        ['value' => '605', 'label' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios'],
        ['value' => '606', 'label' => '606 - Arrendamiento'],
        ['value' => '607', 'label' => '607 - Régimen de Enajenación o Adquisición de Bienes'],
        ['value' => '608', 'label' => '608 - Demás ingresos'],
        ['value' => '610', 'label' => '610 - Residentes en el Extranjero sin Establecimiento Permanente en México'],
        ['value' => '611', 'label' => '611 - Ingresos por Dividendos (socios y accionistas)'],
        ['value' => '612', 'label' => '612 - Personas Físicas con Actividades Empresariales y Profesionales'],
        ['value' => '614', 'label' => '614 - Ingresos por intereses'],
        ['value' => '615', 'label' => '615 - Régimen de los ingresos por obtención de premios'],
        ['value' => '616', 'label' => '616 - Sin obligaciones fiscales'],
        ['value' => '620', 'label' => '620 - Sociedades Cooperativas de Producción'],
        ['value' => '621', 'label' => '621 - Incorporación Fiscal'],
        ['value' => '622', 'label' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'],
        ['value' => '623', 'label' => '623 - Opcional para Grupos de Sociedades'],
        ['value' => '624', 'label' => '624 - Coordinados'],
        ['value' => '625', 'label' => '625 - Actividades Empresariales con ingresos por Plataformas Tecnológicas'],
        ['value' => '626', 'label' => '626 - Régimen Simplificado de Confianza (RESICO)'],
    ];

    // Mismo criterio de resolución de carrito que CartController::currentCart().
    private function currentCart(): Cart
    {
        return Cart::firstOrCreate(
            ['session_id' => session()->getId()],
            ['customer_id' => Auth::guard('customer')->id()]
        );
    }

    public function index()
    {
        $cart = $this->currentCart()->load('items.product.images');

        $subtotal = $cart->subtotal();
        $taxTotal = $cart->taxTotal();
        $shippingTotal = $cart->shippingTotal();
        $freeShippingProgress = $cart->freeShippingProgress();

        return view('frontend.shop.checkout.index', compact('cart', 'subtotal', 'taxTotal', 'shippingTotal', 'freeShippingProgress'));
    }

    public function shipping()
    {
        $cart = $this->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
        }

        $addresses = collect();
        if (Auth::guard('customer')->check()) {
            $addresses = CustomerAddress::where('customer_id', Auth::guard('customer')->id())->get();
        }

        $termsUrl = route('terms-of-service');

        // Precarga: dirección default del cliente si existe, si no la primera.
        $prefill = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        $usoCfdiOptions = self::USO_CFDI_OPTIONS;
        $regimenFiscalOptions = self::REGIMEN_FISCAL_OPTIONS;

        return view('frontend.shop.checkout.shipping', compact('addresses', 'termsUrl', 'prefill', 'usoCfdiOptions', 'regimenFiscalOptions'));
    }

    public function storeShipping(Request $request)
    {
        $cart = $this->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
        }

        $data = $request->validate([
            'contact_name'            => ['required', 'string', 'max:150'],
            'contact_email'           => ['required', 'email', 'max:150'],
            'contact_phone'           => ['required', 'string', 'max:30'],
            'shipping_address_line1'  => ['required', 'string', 'max:255'],
            'shipping_address_line2'  => ['nullable', 'string', 'max:255'],
            'shipping_city'           => ['required', 'string', 'max:100'],
            'shipping_state'          => ['required', 'string', 'max:100'],
            'shipping_postal_code'    => ['required', 'string', 'max:20'],
            'terms_accepted'          => ['required', 'accepted'],
            'requires_invoice'        => ['nullable', 'boolean'],
            'rfc'                     => ['required_if:requires_invoice,1', 'nullable', 'string', 'max:13'],
            'uso_cfdi'                => ['required_if:requires_invoice,1', 'nullable', 'string', Rule::in(array_column(self::USO_CFDI_OPTIONS, 'value'))],
            'razon_social'            => ['required_if:requires_invoice,1', 'nullable', 'string', 'max:150'],
            'regimen_fiscal'          => ['required_if:requires_invoice,1', 'nullable', 'string', Rule::in(array_column(self::REGIMEN_FISCAL_OPTIONS, 'value'))],
            'cp_fiscal'               => ['required_if:requires_invoice,1', 'nullable', 'string', 'max:10'],
            'tax_certificate'         => ['required_if:requires_invoice,1', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $data['requires_invoice'] = $request->boolean('requires_invoice');

        if ($data['requires_invoice'] && $request->hasFile('tax_certificate')) {
            // Contiene RFC/datos fiscales — disco local (privado, no
            // servible por web), mismo criterio que los respaldos de
            // DevOps (storage/app/devops-backups).
            $data['tax_certificate_path'] = $request->file('tax_certificate')->store('tax-certificates', 'local');
        }
        unset($data['tax_certificate']);

        if (! $data['requires_invoice']) {
            $data['rfc'] = $data['uso_cfdi'] = $data['razon_social'] = $data['regimen_fiscal'] = $data['cp_fiscal'] = null;
        }

        // checkout_started_at solo se fija la primera vez: si el cliente
        // reintenta este paso varias veces, el reloj de "carrito abandonado"
        // debe leer desde el primer intento, no reiniciarse en cada reintento.
        $cart->update([
            'contact_name'        => $data['contact_name'],
            'contact_email'       => $data['contact_email'],
            'contact_phone'       => $data['contact_phone'],
            'checkout_started_at' => $cart->checkout_started_at ?? now(),
            'last_activity_at'    => now(),
        ]);

        session()->put('checkout.shipping', $data);

        return redirect()->route('checkout.payment');
    }

    /**
     * Captura progresiva de contacto: se llama por fetch() al perder el foco
     * el correo/teléfono del paso de envío, ANTES de que el cliente envíe el
     * formulario completo — así un carrito de invitado sigue siendo
     * recuperable aunque abandone a media captura y nunca llegue a
     * storeShipping(). Silenciosa a propósito (JSON simple, sin redirect):
     * el checkout nunca debe interrumpirse por esto.
     */
    public function captureContact(Request $request)
    {
        $data = $request->validate([
            'contact_name'  => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (empty($data['contact_email']) && empty($data['contact_phone'])) {
            return response()->json(['saved' => false], 422);
        }

        $cart = $this->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            return response()->json(['saved' => false], 422);
        }

        $cart->update([
            'contact_name'        => $data['contact_name'] ?: $cart->contact_name,
            'contact_email'       => $data['contact_email'] ?: $cart->contact_email,
            'contact_phone'       => $data['contact_phone'] ?: $cart->contact_phone,
            'checkout_started_at' => $cart->checkout_started_at ?? now(),
            'last_activity_at'    => now(),
        ]);

        return response()->json(['saved' => true]);
    }

    public function payment()
    {
        if (! session()->has('checkout.shipping')) {
            return redirect()->route('checkout.shipping');
        }

        $cart = $this->currentCart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        $subtotal = $cart->subtotal();
        $taxTotal = $cart->taxTotal();
        $shippingTotal = $cart->shippingTotal();
        $summary = [
            'subtotal'      => $subtotal,
            'taxTotal'      => $taxTotal,
            'shippingTotal' => $shippingTotal,
            'total'         => round($subtotal + $taxTotal + $shippingTotal, 2),
        ];
        $freeShippingProgress = $cart->freeShippingProgress();

        return view('frontend.shop.checkout.payment', compact('paymentMethods', 'summary', 'freeShippingProgress'));
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
        ]);

        if (! session()->has('checkout.shipping')) {
            return redirect()->route('checkout.shipping');
        }

        $cart = $this->currentCart()->load('items.product');

        if ($cart->items->isEmpty()) {
            return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
        }

        $shipping = session('checkout.shipping');

        // Recalcula todo server-side a partir de los items actuales del
        // carrito: nunca confiar en totales viejos de sesión ni en el
        // cliente. unit_price_snapshot (fijado en CartController::add())
        // guarda el precio SIN IVA, así que el IVA sí se suma aparte aquí
        // (Cart::taxTotal(), misma tasa plana sobre el subtotal ya agregado
        // que usa el checkout en index()/payment() — una sola fuente de
        // verdad para el cálculo, no una copia local del mismo cómputo).
        $subtotal = $cart->subtotal();
        $taxTotal = $cart->taxTotal();
        $shippingTotal = $cart->shippingTotal();
        $total = round($subtotal + $taxTotal + $shippingTotal, 2);

        $storeOrder = DB::transaction(function () use ($cart, $shipping, $data, $subtotal, $shippingTotal, $total, $taxTotal) {
            $storeOrder = StoreOrder::create([
                'order_number'            => StoreOrder::generateOrderNumber(),
                'customer_id'             => Auth::guard('customer')->id(),
                'contact_name'            => $shipping['contact_name'],
                'contact_email'           => $shipping['contact_email'],
                'contact_phone'           => $shipping['contact_phone'],
                'shipping_address_line1'  => $shipping['shipping_address_line1'],
                'shipping_address_line2'  => $shipping['shipping_address_line2'] ?? null,
                'shipping_city'           => $shipping['shipping_city'],
                'shipping_state'          => $shipping['shipping_state'],
                'shipping_postal_code'    => $shipping['shipping_postal_code'],
                'shipping_country'        => 'MX',
                'payment_method_id'       => $data['payment_method_id'],
                'subtotal'                => $subtotal,
                'shipping_total'          => $shippingTotal,
                'discount_total'          => 0,
                'tax_total'               => $taxTotal,
                'total'                   => $total,
                'currency'                => 'MXN',
                'status'                  => 'pendiente_pago',
                'terms_accepted_at'       => now(),
                'notes'                   => null,
                'requires_invoice'        => $shipping['requires_invoice'] ?? false,
                'rfc'                     => $shipping['rfc'] ?? null,
                'uso_cfdi'                => $shipping['uso_cfdi'] ?? null,
                'razon_social'            => $shipping['razon_social'] ?? null,
                'regimen_fiscal'          => $shipping['regimen_fiscal'] ?? null,
                'cp_fiscal'               => $shipping['cp_fiscal'] ?? null,
                'tax_certificate_path'    => $shipping['tax_certificate_path'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                StoreOrderItem::create([
                    'store_order_id' => $storeOrder->id,
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product->name ?? '',
                    'product_sku'    => $item->product->sku ?? null,
                    'quantity'       => $item->quantity,
                    'unit_price'     => $item->unit_price_snapshot,
                    'line_total'     => $item->lineTotal(),
                ]);
            }

            $cart->items()->delete();

            // Marca el carrito como convertido; last_activity_at se limpia
            // para que salga de la query de "carritos abandonados" del
            // módulo de automatización. checkout_started_at y contact_* NO
            // se nulean: quedan como registro histórico del checkout.
            $cart->update([
                'converted_to_store_order_id' => $storeOrder->id,
                'last_activity_at'            => null,
            ]);

            return $storeOrder;
        });

        session()->forget('checkout.shipping');

        // No hay ruta GET dedicada para la confirmación (el bloque de rutas
        // del checkout son solo las 9 especificadas): se renderiza la vista
        // directamente desde este POST, mostrando el folio recién creado.
        return view('frontend.shop.checkout.confirmation', ['storeOrder' => $storeOrder]);
    }
}
