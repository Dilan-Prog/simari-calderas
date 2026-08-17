<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
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

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $product = Products::where('id', $data['product_id'])->where('is_active', true)->first();

        if (! $product) {
            return response()->json(['message' => 'Producto no disponible.'], 422);
        }

        $cart = $this->currentCart();

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
