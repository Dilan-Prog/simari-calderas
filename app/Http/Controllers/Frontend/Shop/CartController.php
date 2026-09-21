<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Actions\AdvanceStoreOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
use App\Models\StoreOrder;
use App\Services\CartRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Carrito ligado a la sesión (invitado o cliente logueado). Sin
     * middleware auth en ninguna ruta de este controller: agregar/editar
     * el carrito debe funcionar sin haber iniciado sesión.
     */
    private function currentCart(): Cart
    {
        return Cart::firstOrCreate(
            ['session_id' => session()->getId()],
            ['customer_id' => Auth::guard('customer')->id()]
        );
    }

    /**
     * Cualquier cambio al carrito (agregar, quitar, cambiar cantidad)
     * invalida el progreso de checkout ya guardado en sesión -- sin esto,
     * el cliente podía vaciar/rellenar su carrito con productos distintos y
     * el acordeón seguía saltando directo a "Método de pago" con la
     * dirección vieja, reutilizando además el pedido/cobro de Mercado Pago
     * ya creado para el carrito anterior (monto equivocado). Cualquier
     * cambio al carrito es, por definición, un pedido distinto al que ya
     * se había empezado a pagar.
     */
    private function invalidateCheckoutSession(): void
    {
        session()->forget(['checkout.shipping', 'checkout.mp_order_id']);
    }

    /**
     * "Tarjeta" (el método pre-seleccionado por defecto) arranca solo en
     * cuanto se abre la sección de Pago (ver maybeAutoStartMercadoPago() en
     * checkout-accordion.js) -- eso ya crea un StoreOrder real de verdad y
     * vacía el carrito, aunque el cliente nunca haya dado clic en pagar.
     * Sin esto, agregar un producto más mientras se ve el paso de pago se
     * sentía como "se borró mi carrito": en realidad ya estaba convertido
     * en un pedido en automático. Se restauran los artículos de ese pedido
     * -- SOLO si nunca se le aprobó ningún cobro -- de vuelta al carrito
     * actual antes de agregar lo nuevo, y se cancela el pedido huérfano en
     * vez de dejarlo "pendiente_pago" para siempre sin que nadie vaya a
     * completarlo.
     */
    private function restoreDraftOrderIfAny(Cart $cart): void
    {
        $draftOrderId = session('checkout.mp_order_id');

        if (! $draftOrderId) {
            return;
        }

        $draftOrder = StoreOrder::where('id', $draftOrderId)
            ->where('status', 'pendiente_pago')
            ->first();

        if (! $draftOrder || $draftOrder->payments()->where('status', 'approved')->exists()) {
            return;
        }

        $draftOrder->loadMissing('items');

        foreach ($draftOrder->items as $item) {
            $existing = CartItem::where('cart_id', $cart->id)->where('product_id', $item->product_id)->first();

            CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $item->product_id],
                ['quantity' => ($existing->quantity ?? 0) + $item->quantity, 'unit_price_snapshot' => $item->unit_price]
            );
        }

        $cart->update(['converted_to_store_order_id' => null]);

        (new AdvanceStoreOrderStatus())(
            $draftOrder,
            'cancelado',
            'Cancelado en automático: el cliente agregó otro producto al carrito antes de pagar -- sus artículos se restauraron al carrito actual.'
        );

        // Slots de Mercado Pago de un pedido que nunca se cobró -- no tiene
        // caso dejarlos huérfanos apuntando a un pedido ya cancelado.
        $draftOrder->payments()->delete();
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'visitor_uuid' => ['nullable', 'uuid'],
        ]);

        $product = Products::where('id', $data['product_id'])->where('is_active', true)->first();

        if (! $product) {
            return response()->json(['message' => 'Producto no disponible.'], 422);
        }

        if (! $product->is_purchasable) {
            return response()->json(['message' => 'Este producto no está disponible actualmente.'], 422);
        }

        $cart = $this->currentCart();
        $this->restoreDraftOrderIfAny($cart);

        $existing = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        $newQuantity = ($existing->quantity ?? 0) + $data['quantity'];

        // Nunca confiar en un precio mandado por el cliente: siempre se
        // recalcula desde el accessor base_price del producto (precio de
        // venta sin IVA, ya convertido a MXN si el producto es USD). El IVA
        // se agrega aparte en el carrito/checkout (Cart::taxTotal()), no se
        // hornea en el snapshot de cada línea.
        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['quantity' => $newQuantity, 'unit_price_snapshot' => $product->base_price]
        );

        $cart->update(['last_activity_at' => now()]);
        $this->invalidateCheckoutSession();

        // Primer punto de captura de atribución publicitaria (no el
        // checkout): así los carritos que nunca llegan a pagar -- la
        // definición misma de "abandonado" -- también quedan con
        // visitor_uuid. Nunca sobreescribe uno ya guardado; la FK a
        // ad_visits es nullOnDelete, así que un visitor_uuid huérfano
        // (aún sin fila en ad_visits) no rompe nada.
        if (!empty($data['visitor_uuid']) && $cart->visitor_uuid === null) {
            try {
                $cart->update(['visitor_uuid' => $data['visitor_uuid']]);
            } catch (\Throwable $e) {
                // silencioso a propósito
            }
        }

        $cart->load('items');

        return response()->json([
            'cartCount' => (int) $cart->items->sum('quantity'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->currentCart();

        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $data['product_id'])->first();

        if (! $item) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'El producto no está en el carrito.'], 404);
            }

            return back()->with('error', 'El producto no está en el carrito.');
        }

        $item->update(['quantity' => $data['quantity']]);

        $cart->update(['last_activity_at' => now()]);
        // Solo el pedido/cobro de Mercado Pago reutilizable -- ese sí queda
        // con el monto viejo y no se puede reusar. La dirección de envío
        // NO se toca aquí: esta misma función es el stepper +/- de
        // cantidad DENTRO del checkout ya iniciado (ver checkout-accordion.js);
        // invalidar checkout.shipping también obligaría a rellenar la
        // dirección de nuevo solo por ajustar una cantidad, una molestia
        // real que el cliente no pidió.
        session()->forget('checkout.mp_order_id');

        $cart->load('items');

        // La página de checkout (index.blade.php) es server-rendered sin
        // Alpine y envía este PATCH como <form> normal — para esa necesita
        // un redirect, no JSON. Las llamadas AJAX (fetch con Accept:
        // application/json) siguen recibiendo el JSON de siempre.
        if ($request->wantsJson()) {
            return response()->json([
                'cartCount' => (int) $cart->items->sum('quantity'),
                'subtotal'  => $cart->subtotal(),
            ]);
        }

        return back();
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $cart = $this->currentCart();

        CartItem::where('cart_id', $cart->id)->where('product_id', $data['product_id'])->delete();

        $cart->load('items');

        // Carrito vaciado a mano ya no cuenta como "abandonado".
        $cart->update(['last_activity_at' => $cart->items->isEmpty() ? null : now()]);
        $this->invalidateCheckoutSession();

        if (! $request->wantsJson()) {
            return back();
        }

        return response()->json([
            'cartCount' => (int) $cart->items->sum('quantity'),
            'subtotal'  => $cart->subtotal(),
        ]);
    }

    /**
     * Link de recuperación real de un carrito abandonado (botón del correo
     * de seguimiento): fusiona los productos del carrito viejo en el
     * carrito actual de quien haga clic -- funciona sin cuenta ni sesión
     * previa, cualquier visitante que abra el link "hereda" esos productos.
     * Token inválido/ya recuperado cae silenciosamente al carrito vacío
     * normal, sin 404 -- un link viejo no debe verse roto para el cliente.
     */
    public function recover(string $token, CartRecoveryService $recovery)
    {
        $target = $this->currentCart();
        $source = $recovery->recoverByToken($token, $target);

        if ($source) {
            return redirect()->route('checkout.index')
                ->with('success', '¡Recuperamos tu carrito! Revisa que todo esté correcto antes de continuar.');
        }

        return redirect()->route('checkout.index');
    }

    public function mini()
    {
        $cart = $this->currentCart();
        $cart->load('items.product');

        return response()->json([
            'cartCount'     => (int) $cart->items->sum('quantity'),
            'subtotal'      => $cart->subtotal(),
            'shippingTotal' => $cart->shippingTotal(),
        ]);
    }
}
