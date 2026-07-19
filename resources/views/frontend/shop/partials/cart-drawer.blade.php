<div class="cart-drawer" x-data x-show="$store.shop.cartOpen" x-cloak>
    <div class="cart-drawer__backdrop" @click="$store.shop.toggleCart()"></div>
    <div class="cart-drawer__panel">
        <div class="cart-drawer__header">
            <div>Tu solicitud de cotización</div>
            <button type="button" @click="$store.shop.toggleCart()">×</button>
        </div>
        <div class="cart-drawer__body">
            <p class="cart-drawer__empty">Aún no has agregado productos. Explora el catálogo y da clic en "Agregar al carrito".</p>
        </div>
        <div class="cart-drawer__footer">
            <div class="cart-drawer__subtotal">
                <span>Subtotal estimado</span><span>$0.00 MXN</span>
            </div>
            <button type="button" class="cart-drawer__continue" disabled title="Próximamente">Continuar solicitud</button>
        </div>
    </div>
</div>
