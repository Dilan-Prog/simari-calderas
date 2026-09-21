<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\MercadoPagoPayment;
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

    // Mismo catálogo estático ya usado en el selector "Estado" (checkbox de
    // dirección de envío y modal de la libreta de direcciones).
    public const ESTADOS_MEXICO = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];

    /**
     * Checkout de una sola página, tipo acordeón (Carrito / Dirección de
     * envío / Método de pago apilados en un solo <div>, sin navegar entre
     * URLs distintas) -- ensambla aquí TODO lo que antes vivía repartido
     * entre index()/shipping()/payment() para que la vista pueda renderizar
     * las 3 secciones de una sola vez. El estado de avance real (qué tanto
     * ya se llenó) sigue viviendo donde ya vivía: el carrito en BD y los
     * datos de envío en session('checkout.shipping') -- storeShipping()/
     * confirm() no cambiaron su forma de guardar nada, solo ganaron una
     * rama JSON para no forzar una navegación de página completa.
     */
    public function index()
    {
        // session('checkout.mp_order_id') (ver confirm()) es a propósito
        // permanente -- existe para reutilizar el pedido ya creado tanto si
        // el cliente cambia de radio Tarjeta<->Mercado Pago sin recargar esta
        // página, como si un cobro de Checkout Pro se rechaza y necesita
        // reintentar el MISMO pedido (ver openCheckoutProPopup() en
        // checkout-payment.js). Limpiarla aquí en cada GET rompía ese
        // segundo caso -- la única defensa real contra reutilizar un pedido
        // viejo de una sesión de checkout ya abandonada es la ventana de 30
        // minutos en confirm(), no esto.
        $cart = $this->currentCart()->load('items.product.images');

        $subtotal = $cart->subtotal();
        $taxTotal = $cart->taxTotal();
        $shippingTotal = $cart->shippingTotal();
        $freeShippingProgress = $cart->freeShippingProgress();
        $summary = [
            'subtotal'      => $subtotal,
            'taxTotal'      => $taxTotal,
            'shippingTotal' => $shippingTotal,
            'total'         => round($subtotal + $taxTotal + $shippingTotal, 2),
        ];

        $customer = Auth::guard('customer')->user();
        $addresses = collect();
        if ($customer) {
            $addresses = CustomerAddress::where('customer_id', $customer->id)->get();
        }
        // Precarga: dirección default del cliente si existe, si no la primera.
        $prefill = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();

        // La opción "Mercado Pago" (Checkout Pro) se deshabilita de antemano
        // cuando el carrito ya mezcla productos con/sin MSI -- ese caso
        // siempre termina en 2 "cobros" (ver createMercadoPagoPaymentSlots())
        // y checkoutPro() ya rechaza pedidos con más de 1 cobro. Evita un
        // clic muerto: sin esto, el radio se vería seleccionable y solo al
        // confirmar aparecería el 422.
        $cartWouldSplitMsi = $this->cartHasMixedMsiProducts($cart);

        $termsUrl = route('terms-of-service');
        $usoCfdiOptions = self::USO_CFDI_OPTIONS;
        $regimenFiscalOptions = self::REGIMEN_FISCAL_OPTIONS;
        $estadosMexico = self::ESTADOS_MEXICO;
        $deliveryEstimateLabel = config('shop.delivery_estimate_label');

        // Si el cliente ya llenó envío en una visita previa de esta misma
        // sesión (recargó la página, volvió de un paso de MP, etc.), la
        // sección de envío arranca marcada "completada" en vez de forzarlo a
        // recapturar todo -- el JS del acordeón usa esto solo para decidir
        // el estado inicial, storeShipping() sigue siendo la única fuente
        // real de verdad de si los datos ya están guardados.
        $shippingCompleted = session()->has('checkout.shipping');

        return view('frontend.shop.checkout.index', compact(
            'cart', 'subtotal', 'taxTotal', 'shippingTotal', 'freeShippingProgress', 'summary',
            'customer', 'addresses', 'prefill', 'termsUrl', 'usoCfdiOptions', 'regimenFiscalOptions', 'estadosMexico',
            'paymentMethods', 'shippingCompleted', 'cartWouldSplitMsi', 'deliveryEstimateLabel'
        ));
    }

    // Mismo agrupamiento por accepts_msi que createMercadoPagoPaymentSlots(),
    // pero de solo lectura sobre las líneas del carrito -- antes de que
    // exista ningún StoreOrder/MercadoPagoPayment todavía (se usa para
    // decidir, en index(), si la opción "Mercado Pago" debe verse deshabilitada).
    private function cartHasMixedMsiProducts(Cart $cart): bool
    {
        $groups = $cart->items->groupBy(fn ($item) => ($item->product?->accepts_msi ?? false) ? 1 : 2);

        return $groups->get(1, collect())->isNotEmpty() && $groups->get(2, collect())->isNotEmpty();
    }

    public function storeShipping(Request $request)
    {
        $cart = $this->currentCart()->load('items');

        if ($cart->items->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Tu carrito está vacío.'], 422);
            }

            return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
        }

        $data = $request->validate([
            'contact_name'            => ['required', 'string', 'max:150'],
            'contact_email'           => ['required', 'email', 'max:150'],
            'contact_phone'           => ['required', 'string', 'max:30'],
            'shipping_address_line1'  => ['required', 'string', 'max:255'],
            'shipping_address_line2'  => ['required', 'string', 'max:255'], // Colonia
            'shipping_reference'      => ['nullable', 'string', 'max:255'], // Referencias de entrega (opcional)
            'shipping_city'           => ['required', 'string', 'max:100'],
            'shipping_state'          => ['required', 'string', 'max:100'],
            'shipping_postal_code'    => ['required', 'string', 'max:20'],
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

        // Libreta de direcciones (solo clientes con cuenta, y solo cuando
        // todavía no tienen ninguna guardada -- ver checkbox "Guardar esta
        // dirección" en la vista): editar/agregar direcciones ya guardadas
        // se hace aparte, vía el modal de shipping.blade.php +
        // CustomerAddressController, nunca a través de este formulario.
        if (Auth::guard('customer')->check() && $request->boolean('save_address')) {
            $customer = Auth::guard('customer')->user();
            $customer->customer_addresses()->create([
                'recipient_name' => $data['contact_name'],
                'phone'          => $data['contact_phone'],
                'postal_code'    => $data['shipping_postal_code'],
                'state'          => $data['shipping_state'],
                'city'           => $data['shipping_city'],
                'address_line1'  => $data['shipping_address_line1'],
                'address_line2'  => $data['shipping_address_line2'] ?? null,
                'reference'      => $data['shipping_reference'] ?? null,
                'country'        => 'MX',
                'is_default'     => $customer->customer_addresses()->doesntExist(),
            ]);
        }

        // Acordeón de una sola página: la sección de Envío se envía por
        // fetch() con Accept: application/json (ver checkout-accordion.js) y
        // nunca navega -- solo necesita saber que se guardó bien para
        // colapsar esta sección y abrir la de Método de pago. El redirect de
        // abajo es respaldo para JS deshabilitado/fetch fallido.
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('checkout.index');
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

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            // Cuando el método elegido es Mercado Pago, este campo decide
            // cuál de los 2 sub-flujos corre (ver más abajo) -- para
            // cualquier otro método de pago se ignora por completo. Un solo
            // PaymentMethod admin sigue siendo la fuente de verdad de "MP
            // está activo"; esto es una bifurcación de UI/JS del checkout,
            // no un tipo de método nuevo en la tabla payment_methods.
            'mp_payment_flow' => ['nullable', 'string', 'in:card,checkout_pro'],
        ]);

        $paymentMethod = PaymentMethod::find($data['payment_method_id']);
        $isMercadoPago = $paymentMethod && in_array($paymentMethod->type, ['pasarela', 'digital'], true)
            && ($paymentMethod->details['processor'] ?? null) === 'mercadopago';

        // Tarjeta (auto-arranca al abrir la sección de Pago, ver
        // maybeAutoStartMercadoPago() en checkout-accordion.js) ya
        // consumió session('checkout.shipping') y creó el pedido la primera
        // vez que este endpoint corrió. Si el cliente después cambia al
        // radio "Mercado Pago" (o viceversa) dentro del MISMO intento de
        // checkout, este 2º POST llegaría aquí sin esa sesión y fallaría en
        // falso con "Primero completa los datos de envío" -- se reutiliza
        // el pedido ya creado en vez de exigir volver a llenar Envío.
        $storeOrder = null;
        if ($isMercadoPago && session('checkout.mp_order_id')) {
            $existing = StoreOrder::find(session('checkout.mp_order_id'));
            // Segunda capa de seguridad además de limpiar la sesión en
            // index() (ver ahí el porqué): el cambio de radio Tarjeta<->
            // Mercado Pago que motiva esta reutilización ocurre en segundos,
            // nunca en horas -- una ventana generosa sigue permitiendo ese
            // caso real sin arriesgar reutilizar un pedido de un intento de
            // checkout completamente distinto y ya viejo.
            if ($existing
                && $existing->status === 'pendiente_pago'
                && $existing->created_at->gt(now()->subMinutes(30))
                && $existing->payments()->where('status', 'approved')->doesntExist()) {
                $storeOrder = $existing;
            }
        }

        if (! $storeOrder) {
            if (! session()->has('checkout.shipping')) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Primero completa los datos de envío.'], 422);
                }

                return redirect()->route('checkout.index');
            }

            $cart = $this->currentCart()->load('items.product');

            if ($cart->items->isEmpty()) {
                return redirect()->route('checkout.index')->with('error', 'Tu carrito está vacío.');
            }

            $shipping = session('checkout.shipping');

            $storeOrder = $this->createOrderFromCart($cart, $shipping, $data['payment_method_id']);

            session()->forget('checkout.shipping');
        }

        // Mercado Pago (Checkout API/CardForm o Checkout Pro) se ramifica
        // del resto de métodos de pago justo aquí: el pedido ya existe con
        // status 'pendiente_pago' igual que cualquier otro método, pero en
        // vez de mostrar la confirmación directa se arma el cobro (o los 2
        // cobros, si el carrito mezcla líneas con/sin MSI) y se manda al
        // cliente al flujo elegido. Para cualquier otro método de pago,
        // cero cambios: sigue renderizando confirmation.blade.php
        // exactamente como antes.
        if ($isMercadoPago) {
            // createMercadoPagoPaymentSlots() ya es idempotente en la
            // práctica aquí: solo se llama la primera vez que se crea el
            // pedido (arriba) -- si se reutilizó uno existente, sus slots
            // ya están creados y no se duplican.
            if ($storeOrder->wasRecentlyCreated) {
                $this->createMercadoPagoPaymentSlots($storeOrder);
            }
            session(['checkout.mp_order_id' => $storeOrder->id]);

            $mpFlow = $data['mp_payment_flow'] ?? 'card';

            // Flujo "Mercado Pago" (Wallet/Efectivo/Transferencia/Mercado
            // Crédito): 100% delegado a Checkout Pro -- ya NO se ofrece como
            // link secundario bajo la tarjeta, es su propio radio. Solo
            // aplica con un único cobro (checkoutPro() ya rechaza >1 con
            // 422; se repite el chequeo aquí para responder sin necesidad de
            // que el cliente golpee ese endpoint aparte).
            if ($mpFlow === 'checkout_pro') {
                if ($storeOrder->payments()->count() > 1) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'message' => 'Esta opción no está disponible cuando tu pedido combina productos con y sin meses sin intereses. Elige "Tarjeta de crédito o débito".',
                        ], 422);
                    }

                    return redirect()->route('checkout.index')->with('error', 'Esta opción no está disponible cuando tu pedido combina productos con y sin meses sin intereses.');
                }

                $onlyPayment = $storeOrder->payments()->first();
                // Ruta POST-only (checkoutPro() crea la Order real de MP y
                // regresa su checkout_url vía JSON) -- el cliente hace un
                // 2º fetch a esta URL (ver checkout-payment.js) y navega al
                // resultado. Sin JS no hay forma de completar este flujo en
                // un solo salto; el respaldo cae a la misma página de
                // Tarjeta que ya usa el flujo "card" (JS deshabilitado es un
                // caso ya degradado en el resto de este checkout).
                $checkoutProUrl = route('checkout.payment.mercadopago.checkout-pro', [$storeOrder->order_number, $onlyPayment->id]);

                if ($request->wantsJson()) {
                    return response()->json(['flow' => 'checkout_pro', 'checkoutProUrl' => $checkoutProUrl]);
                }

                return redirect()->route('checkout.payment.mercadopago', $storeOrder->order_number);
            }

            // Flujo "Tarjeta": Checkout API vía CardForm/Secure Fields,
            // montado directo en payment.blade.php (sin navegar a una URL
            // aparte) -- checkout-payment.js llama a este mismo endpoint con
            // Accept: application/json y monta el formulario con esta
            // respuesta. El redirect de abajo queda como respaldo (JS
            // deshabilitado, fetch fallido antes de llegar aquí, etc.) --
            // checkout.payment.mercadopago sigue existiendo y sirviendo esta
            // misma orden.
            $cardPublicKey = \App\Services\MercadoPago\MercadoPagoPaymentService::publicKeyFor('api');

            // Sin esto, un rol "api" (Checkout API/Tarjeta) sin Public Key
            // configurada en /admin/integraciones (o su .env de respaldo)
            // hacía que new MercadoPago(null) fallara en silencio del lado
            // del cliente: los 3 campos de tarjeta se quedaban como <div>
            // vacíos para siempre, sin ningún mensaje -- parecía que el
            // checkout entero estaba roto en vez de solo faltar una
            // credencial por configurar.
            if ($request->wantsJson() && blank($cardPublicKey)) {
                return response()->json([
                    'message' => 'El pago con tarjeta no está disponible en este momento (falta configurar Mercado Pago). Intenta con la otra opción de pago o contáctanos.',
                ], 503);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'publicKey' => $cardPublicKey,
                    'retryOnly' => null,
                    // CardForm necesita un email de payer para tokenizar
                    // (cardholderEmail, campo oculto no sensible) -- el
                    // mismo correo de contacto ya capturado en Envío.
                    'payerEmail' => $storeOrder->contact_email,
                    'payments' => $storeOrder->payments()->orderBy('charge_group')->get()->map(fn ($p) => [
                        'id'          => $p->id,
                        'chargeGroup' => $p->charge_group,
                        'includesMsi' => (bool) $p->includes_msi,
                        'amount'      => (float) $p->amount,
                        'containerId' => 'cardform-' . $p->charge_group,
                        'chargeUrl'   => route('checkout.payment.mercadopago.charge', [$storeOrder->order_number, $p->id]),
                    ])->values(),
                ]);
            }

            return redirect()->route('checkout.payment.mercadopago', $storeOrder->order_number);
        }

        // No hay ruta GET dedicada para la confirmación (el bloque de rutas
        // del checkout son solo las 9 especificadas): se renderiza la vista
        // directamente desde este POST, mostrando el folio recién creado.
        return view('frontend.shop.checkout.confirmation', ['storeOrder' => $storeOrder]);
    }

    /**
     * Crea el StoreOrder + StoreOrderItems a partir del carrito actual y lo
     * deja listo para pago (carrito vaciado y marcado como convertido).
     * Misma lógica exacta que confirm() tenía inline antes de este refactor
     * -- extraída para que la rama de Mercado Pago pueda reutilizarla sin
     * duplicar el bloque de creación de la orden.
     */
    private function createOrderFromCart(Cart $cart, array $shipping, int $paymentMethodId): StoreOrder
    {
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

        return DB::transaction(function () use ($cart, $shipping, $paymentMethodId, $subtotal, $shippingTotal, $total, $taxTotal) {
            $storeOrder = StoreOrder::create([
                'order_number'            => StoreOrder::generateOrderNumber(),
                'customer_id'             => Auth::guard('customer')->id(),
                'contact_name'            => $shipping['contact_name'],
                'contact_email'           => $shipping['contact_email'],
                'contact_phone'           => $shipping['contact_phone'],
                'shipping_address_line1'  => $shipping['shipping_address_line1'],
                'shipping_address_line2'  => $shipping['shipping_address_line2'] ?? null,
                'shipping_reference'      => $shipping['shipping_reference'] ?? null,
                'shipping_city'           => $shipping['shipping_city'],
                'shipping_state'          => $shipping['shipping_state'],
                'shipping_postal_code'    => $shipping['shipping_postal_code'],
                'shipping_country'        => 'MX',
                'payment_method_id'       => $paymentMethodId,
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
    }

    /**
     * Arma los "slots" de cobro de Mercado Pago para un pedido recién creado:
     * agrupa sus líneas por si el producto acepta MSI o no y crea 1 fila en
     * mercado_pago_payments por grupo no vacío (1 o 2 filas). El monto de
     * cada grupo es su subtotal (line_total de sus líneas) + su parte
     * proporcional de envío/IVA del pedido -- ambos montos deben sumar
     * exacto $order->total, así que se redondea uno por proporción directa
     * y el otro se calcula como el residuo exacto (el grupo con mayor peso
     * decimal en el reparto se queda con el centavo de redondeo sobrante).
     */
    private function createMercadoPagoPaymentSlots(StoreOrder $order): void
    {
        $order->loadMissing('items.product');

        $groups = $order->items->groupBy(function (StoreOrderItem $item) {
            return ($item->product?->accepts_msi ?? false) ? 1 : 2;
        });

        $msiItems = $groups->get(1, collect());
        $regularItems = $groups->get(2, collect());

        if ($msiItems->isEmpty() && $regularItems->isEmpty()) {
            return;
        }

        // Un solo grupo con contenido: se lleva el pedido completo, sin
        // necesidad de repartir nada -- charge_group siempre 1 en este caso.
        if ($msiItems->isEmpty() || $regularItems->isEmpty()) {
            $onlyGroup = $msiItems->isNotEmpty() ? $msiItems : $regularItems;

            MercadoPagoPayment::create([
                'store_order_id'       => $order->id,
                'charge_group'         => 1,
                'includes_msi'         => $msiItems->isNotEmpty(),
                'store_order_item_ids' => $onlyGroup->pluck('id')->values()->all(),
                'amount'               => round((float) $order->total, 2),
                'currency'             => $order->currency,
                'status'               => 'pending',
            ]);

            return;
        }

        // Ambos grupos tienen líneas: reparte shipping_total + tax_total a
        // prorrata del peso (subtotal del grupo / subtotal de ambos grupos),
        // trabajando en centavos enteros para evitar arrastres de flotantes.
        $msiSubtotalCents = (int) round($msiItems->sum(fn (StoreOrderItem $item) => (float) $item->line_total) * 100);
        $regularSubtotalCents = (int) round($regularItems->sum(fn (StoreOrderItem $item) => (float) $item->line_total) * 100);
        $sumSubtotalCents = $msiSubtotalCents + $regularSubtotalCents;
        $totalCents = (int) round(((float) $order->total) * 100);

        // round() normal (mitad hacia arriba) sobre el monto proporcional del
        // grupo MSI ya le da el centavo de residuo al grupo con mayor peso
        // decimal; el grupo regular se calcula como el complemento exacto,
        // así ambos montos siempre suman exactamente $order->total.
        $msiAmountCents = $sumSubtotalCents > 0
            ? (int) round($totalCents * ($msiSubtotalCents / $sumSubtotalCents))
            : intdiv($totalCents, 2);
        $regularAmountCents = $totalCents - $msiAmountCents;

        MercadoPagoPayment::create([
            'store_order_id'       => $order->id,
            'charge_group'         => 1,
            'includes_msi'         => true,
            'store_order_item_ids' => $msiItems->pluck('id')->values()->all(),
            'amount'               => round($msiAmountCents / 100, 2),
            'currency'             => $order->currency,
            'status'               => 'pending',
        ]);

        MercadoPagoPayment::create([
            'store_order_id'       => $order->id,
            'charge_group'         => 2,
            'includes_msi'         => false,
            'store_order_item_ids' => $regularItems->pluck('id')->values()->all(),
            'amount'               => round($regularAmountCents / 100, 2),
            'currency'             => $order->currency,
            'status'               => 'pending',
        ]);
    }
}
