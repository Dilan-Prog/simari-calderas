@php
    // $hasDiscount/$discountPct ya vienen calculados por show.blade.php
    // (@include comparte su scope) -- gallery.blade.php también los usa
    // (badge de descuento sobre la imagen), por eso viven ahí y no aquí.
    // Fijo a MXN: base_price/compare_base_price ya vienen convertidos.
    $currency = 'MXN';
    // $resolvedName ya viene calculado por show.blade.php (@include comparte
    // su scope) -- no se recalcula aquí.
    $quoteMessage = "Hola, me interesa cotizar este producto: {$resolvedName} (SKU: {$product->sku}) - " . route('product.show', $product->slug);
    $quoteWhatsappUrl = 'https://wa.me/' . \App\Models\Setting::get('footer.phone_link', '5214494577320') . '?text=' . urlencode($quoteMessage);
@endphp
<div class="product-price-box">
    <div class="product-price-box__eyebrow">{{ $product->availability_label }} &nbsp;|&nbsp; SKU {{ $product->sku }}</div>
    <h1 class="product-price-box__title">{{ $resolvedName }}</h1>

    @if ($hasDiscount)
        <div class="product-price-box__discount-row">
            <span class="product-price-box__discount-badge">{{ $discountPct }}% OFF</span>
            <span class="product-price-box__original">${{ number_format($product->compare_base_price, 2) }}</span>
        </div>
    @endif
    <div class="product-price-box__price">${{ number_format($product->base_price, 2) }} {{ $currency }}</div>
    <div class="product-price-box__iva">Precio + IVA</div>
    @if (!$product->shipping_cost || $product->shipping_cost <= 0)
        <div class="product-price-box__shipping">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Envío a toda la República Mexicana
        </div>
    @elseif (!$product->free_shipping_threshold)
        <div class="product-price-box__shipping-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Recíbelo por ${{ number_format($product->shipping_cost, 2) }} de envío
        </div>
    @else
        <div class="product-price-box__shipping-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Envío gratis en compras desde ${{ number_format($product->free_shipping_threshold, 2) }} MXN. Antes de eso, el envío tiene un costo de ${{ number_format($product->shipping_cost, 2) }}.
        </div>
    @endif

    <div class="product-qty-row">
        <div class="product-qty-stepper">
            <button type="button" @click="dec()" :disabled="qty <= 1" aria-label="Disminuir cantidad">−</button>
            <span class="product-qty-stepper__value" x-text="qty"></span>
            <button type="button" @click="inc()" :disabled="qty >= max" aria-label="Aumentar cantidad">+</button>
        </div>
        @if ($product->availability !== 'on_order')
            <span class="product-qty-available">{{ $product->stock }} disponibles</span>
        @endif
    </div>

    <div class="product-price-box__actions">
        <button type="button" class="product-price-box__add-btn" data-product-id="{{ $product->id }}"
            data-sku="{{ $product->sku }}" data-name="{{ $resolvedName }}" data-price="{{ $product->base_price }}"
            @if(!$product->is_purchasable) disabled @endif>
            {{ $product->is_purchasable ? 'Agregar al carrito' : 'No disponible' }}
        </button>
        <a href="{{ $quoteWhatsappUrl }}" target="_blank" rel="noopener nofollow" class="product-price-box__quote-btn" data-ad-track="quote_start" data-product-id="{{ $product->id }}">Solicitar cotización</a>
    </div>

    @if ($product->short_description)
        <div class="product-price-box__highlights">
            <div class="product-price-box__highlights-title">Lo que debes saber de este producto</div>
            <p>{{ $product->resolveVariables($product->short_description) }}</p>
        </div>
    @endif

    @php
        $quickFacts = collect([
            ['label' => 'Marca', 'value' => $product->brand?->name],
            ['label' => 'Modelo', 'value' => $product->model],
            ['label' => 'SKU', 'value' => $product->sku],
            ['label' => 'Categoría', 'value' => $product->category?->name],
            ['label' => 'Disponibilidad', 'value' => $product->availability_label],
            ['label' => 'Unidad de medida', 'value' => $product->stock_unit],
        ])->filter(fn ($fact) => filled($fact['value']));
    @endphp
    @if ($quickFacts->isNotEmpty())
        <dl class="product-price-box__facts">
            @foreach ($quickFacts as $fact)
                <div class="product-price-box__facts-row">
                    <dt>{{ $fact['label'] }}</dt>
                    <dd>{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @php
        // Descuento de contado sobre base_price (sin IVA) -- misma
        // convención de toda la página (precio mostrado siempre sin IVA +
        // nota aparte). 0/sin configurar = "sin descuento", el panel se
        // degrada mostrando precio normal sin badge ni tachado.
        $cashDiscountPercent = (float) \App\Models\Setting::get('ecommerce.cash_discount_percent', 0);
        $hasCashDiscount = $cashDiscountPercent > 0;
        $cashPrice = $hasCashDiscount
            ? round($product->base_price * (1 - $cashDiscountPercent / 100), 2)
            : $product->base_price;

        // Tier 1 (contado) = todo método activo excepto crédito (línea de
        // crédito no es "pago de contado"). Tier 2 (MSI) = subconjunto de
        // tier 1 con pasarela/digital que además tenga meses configurados
        // de verdad (details.msi, dato ya real desde el admin).
        $tier1Methods = $paymentMethods->where('type', '!=', 'credito')->values();
        $tier2Methods = $tier1Methods
            ->whereIn('type', ['pasarela', 'digital'])
            ->filter(fn ($m) => !empty($m->details['msi'] ?? null))
            ->values();
        // Unión de todos los meses distintos entre los métodos elegibles --
        // cada logo mostrado ya aclara qué procesador es, así que no hace
        // falta que todos soporten exactamente los mismos meses.
        $msiMonths = $tier2Methods
            ->flatMap(fn ($m) => $m->details['msi'] ?? [])
            ->map(fn ($m) => (int) $m)
            ->unique()
            ->sort()
            ->values();
        $showsInvoicing = $tier1Methods->contains(fn ($m) => $m->allows_invoicing);
    @endphp

    {{-- Compra protegida --}}
    <div class="product-price-box__trust">
        <svg class="product-price-box__trust-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2">
            <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"></path>
        </svg>
        <span class="product-price-box__trust-label">Compra 100% protegida</span>
    </div>

    {{-- Medios de pago --}}
    @if ($tier1Methods->isNotEmpty())
        <div class="product-price-box__payments">
            <div class="product-price-box__payments-header">
                <div>
                    <div class="product-price-box__payments-eyebrow">MEDIOS DE PAGO</div>
                    <div class="product-price-box__payments-title">Tu comodidad, tu pago.</div>
                </div>
                @if ($showsInvoicing)
                    <div class="product-price-box__payments-badge">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        <span>Factura CFDI 4.0</span>
                    </div>
                @endif
            </div>

            <div class="product-price-box__payments-body">
                {{-- Tier 1: contado --}}
                <div class="product-price-box__tier">
                    <div class="product-price-box__tier-header">
                        <div class="product-price-box__tier-title">1 · Pago de contado</div>
                        @if ($hasCashDiscount)
                            <span class="product-price-box__tier-badge">Ahorras {{ rtrim(rtrim(number_format($cashDiscountPercent, 2), '0'), '.') }}%</span>
                        @endif
                    </div>

                    <div class="product-price-box__tier-price-row">
                        <span class="product-price-box__tier-price">${{ number_format($cashPrice, 2) }}</span>
                        @if ($hasCashDiscount)
                            <span class="product-price-box__tier-price-original">${{ number_format($product->base_price, 2) }}</span>
                        @endif
                    </div>
                    <div class="product-price-box__tier-note">
                        {{ $hasCashDiscount ? 'Precio con descuento pagando de contado, más IVA.' : 'Precio de contado, más IVA.' }} Métodos disponibles abajo.
                    </div>

                    <div class="product-price-box__tier-methods">
                        @foreach ($tier1Methods as $method)
                            <div class="product-price-box__method">
                                @if ($method->logo_url)
                                    <img class="product-price-box__method-logo" src="{{ $method->logo_url }}" alt="{{ $method->name }}">
                                @else
                                    <div class="product-price-box__method-logo product-price-box__method-logo--placeholder">{{ mb_strtoupper(mb_substr($method->name, 0, 2)) }}</div>
                                @endif
                                <span class="product-price-box__method-name">{{ $method->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tier 2: MSI --}}
                @if ($tier2Methods->isNotEmpty())
                    <div class="product-price-box__tier">
                        <div class="product-price-box__tier-title">2 · Meses sin intereses</div>
                        <div class="product-price-box__tier-note">Con Visa, Mastercard, American Express o Carnet participantes. Monto por mes calculado sobre el precio del producto (no incluye envío).</div>

                        <div class="product-price-box__msi-grid">
                            @foreach ($msiMonths as $months)
                                <div class="product-price-box__msi-plan">
                                    <span class="product-price-box__msi-plan-months">{{ $months }} meses</span>
                                    <span class="product-price-box__msi-plan-amount">${{ number_format($product->final_price / $months, 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="product-price-box__tier-logos">
                            @foreach ($tier2Methods as $method)
                                @if ($method->logo_url)
                                    <img class="product-price-box__method-logo-sm" src="{{ $method->logo_url }}" alt="{{ $method->name }}" title="{{ $method->name }}">
                                @else
                                    <div class="product-price-box__method-logo-sm product-price-box__method-logo-sm--placeholder" title="{{ $method->name }}">{{ mb_strtoupper(mb_substr($method->name, 0, 2)) }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
