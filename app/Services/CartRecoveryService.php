<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;

/**
 * Recuperación real de carritos abandonados: fusiona los productos de un
 * carrito viejo (abandonado) dentro del carrito ACTUAL de la sesión, en vez
 * de solo mandar al cliente a una URL genérica que no reconoce su carrito.
 *
 * Reutiliza el mismo criterio de precio que CartController::add() (nunca
 * confiar en un unit_price_snapshot viejo -- siempre recalcula desde
 * Products::base_price actual) para no arrastrar un precio desactualizado
 * si el producto cambió de precio desde que se abandonó el carrito.
 */
class CartRecoveryService
{
    /**
     * Fusiona los items de $source dentro de $target (suma cantidades si el
     * producto ya está en $target) y marca $source como recuperado
     * (dismissed_at) para que la automatización de recordatorios deje de
     * insistir con un carrito que ya se recuperó. No hace nada si $source
     * ya fue convertido a pedido o si es el mismo carrito que $target.
     */
    public function mergeInto(Cart $source, Cart $target): bool
    {
        if ($source->id === $target->id) {
            return false;
        }

        if ($source->converted_to_store_order_id !== null) {
            return false;
        }

        $source->loadMissing('items.product');

        if ($source->items->isEmpty()) {
            return false;
        }

        foreach ($source->items as $item) {
            $product = $item->product;

            if (!$product || !$product->is_active) {
                continue;
            }

            $existing = CartItem::where('cart_id', $target->id)->where('product_id', $product->id)->first();
            $newQuantity = ($existing->quantity ?? 0) + $item->quantity;

            CartItem::updateOrCreate(
                ['cart_id' => $target->id, 'product_id' => $product->id],
                ['quantity' => $newQuantity, 'unit_price_snapshot' => $product->base_price]
            );
        }

        $target->update(['last_activity_at' => now()]);

        $source->update(['dismissed_at' => now()]);

        return true;
    }

    /**
     * Busca el carrito abandonado detrás de un token de recuperación (link
     * del correo) y lo fusiona en $target. Devuelve el carrito de origen si
     * la fusión ocurrió, o null si el token no es válido / ya no aplica
     * (convertido, sin productos, o es el mismo carrito actual).
     */
    public function recoverByToken(string $token, Cart $target): ?Cart
    {
        $source = Cart::where('recovery_token', $token)->first();

        if (!$source) {
            return null;
        }

        return $this->mergeInto($source, $target) ? $source : null;
    }

    /**
     * Busca el carrito abandonado MÁS RECIENTE de este cliente (distinto al
     * carrito actual de la sesión, con productos, sin convertir) y lo
     * fusiona en $target. Pensado para llamarse justo después de que un
     * cliente inicia sesión, para que sus productos abandonados en otra
     * sesión/dispositivo reaparezcan solos sin que tenga que hacer nada.
     */
    public function recoverForCustomer(Customer $customer, Cart $target): ?Cart
    {
        $source = Cart::where('customer_id', $customer->id)
            ->where('id', '!=', $target->id)
            ->whereNull('converted_to_store_order_id')
            ->whereHas('items')
            ->latest('last_activity_at')
            ->first();

        if (!$source) {
            return null;
        }

        return $this->mergeInto($source, $target) ? $source : null;
    }
}
