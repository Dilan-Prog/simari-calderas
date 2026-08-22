<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'visitor_uuid',
        'recovery_token',
        'customer_id',
        'last_activity_at',
        'checkout_started_at',
        'contact_name',
        'contact_email',
        'contact_phone',
        'converted_to_store_order_id',
        'dismissed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            if (empty($cart->recovery_token)) {
                $cart->recovery_token = Str::random(40);
            }
        });
    }

    // Sin estos casts, last_activity_at/checkout_started_at llegan como string
    // desde la BD y ->diffForHumans() en la vista de Carritos Abandonados falla.
    protected $casts = [
        'last_activity_at' => 'datetime',
        'checkout_started_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedToStoreOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'converted_to_store_order_id');
    }

    // Visita de anuncio (gclid/wbraid/UTM) que originó este carrito, si el
    // visitante llegó de una campaña de Google Ads.
    public function adVisit(): BelongsTo
    {
        return $this->belongsTo(AdVisit::class, 'visitor_uuid', 'visitor_uuid');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // unit_price_snapshot guarda el precio SIN IVA (ver CartController::add())
    // desde que el precio mostrado en tienda dejó de incluir impuesto — este
    // subtotal es, a propósito, el monto antes de IVA.
    public function subtotal(): float
    {
        return round($this->items->sum(fn (CartItem $item) => $item->quantity * $item->unit_price_snapshot), 2);
    }

    /**
     * IVA sobre el subtotal, a la tasa global vigente (Products::ivaRate()).
     * unit_price_snapshot ya normaliza cada línea a "sin IVA" al momento de
     * agregarla al carrito (independiente de cómo cada producto tenga
     * price_includes_tax) — así que basta aplicar una sola tasa plana sobre
     * el subtotal ya agregado, sin tener que revisar producto por producto.
     */
    public function taxTotal(): float
    {
        return round($this->subtotal() * (Products::ivaRate() / 100), 2);
    }

    /**
     * Convención: el envío se cobra UNA vez por línea de carrito que tenga
     * cargo aplicable (no se multiplica por quantity). Cada línea cae en uno
     * de estos casos, en este orden de prioridad:
     *
     *   1) Override propio del producto (products.shipping_cost > 0): gana
     *      SIEMPRE sobre cualquier ShippingRule de su marca/categoría --
     *      comportamiento histórico, sin cambios. Su free_shipping_threshold
     *      (si tiene) se compara contra el SUBTOTAL TOTAL DEL CARRITO (sin
     *      IVA, mismo criterio que subtotal()).
     *
     *   2) Sin override propio: se busca una ShippingRule activa que cubra
     *      al producto -- primero por brand_id, si no hay, por category_id
     *      exacto (sin subir a categorías padre). Si la regla tiene
     *      shipping_cost > 0, ese es el cargo de esa línea. La diferencia
     *      clave con el caso 1: el free_shipping_threshold de una REGLA se
     *      compara contra el SUBTOTAL DE SOLO LAS LÍNEAS DEL CARRITO QUE
     *      CAEN BAJO ESA MISMA REGLA (misma marca o categoría), no contra el
     *      carrito completo -- dos productos de una marca con envío se
     *      agrupan entre sí para ese cálculo, pero no se mezclan con otras
     *      marcas/categorías ni con el resto del carrito.
     *
     *   3) Sin override y sin regla aplicable (o regla con shipping_cost
     *      nulo/0): envío estándar gratis, sin cambios.
     *
     * shippingGroups() hace todo el trabajo de agrupar+decidir una sola vez;
     * shippingTotal() y freeShippingProgress() solo leen ese resultado.
     */
    /**
     * Monto final del carrito (subtotal + IVA + envío) -- el total real que
     * pagaría el cliente, usado por el correo de recordatorio de carrito
     * abandonado (EmailTemplateService::render()).
     */
    public function total(): float
    {
        return round($this->subtotal() + $this->taxTotal() + $this->shippingTotal(), 2);
    }

    public function getHasEmailAttribute(): bool
    {
        return filled($this->customer?->email) || filled($this->contact_email);
    }

    /**
     * Único campo que la automatización de seguimiento debe revisar para
     * decidir si sigue insistiendo. No basta con converted_to_store_order_id
     * -- un carrito también deja de estar "pendiente" cuando se recuperó
     * (CartRecoveryService::mergeInto(), que marca dismissed_at) SIN que ese
     * carrito en particular sea el que se convirtió a pedido (la compra
     * puede terminar en OTRO carrito, el de la sesión donde el cliente
     * inició sesión). Sin este accessor, la condición de la automatización
     * solo veía converted_to_store_order_id y seguía mandando recordatorios
     * de un carrito que el cliente ya recuperó por otro camino.
     */
    public function getIsStillPendingAttribute(): bool
    {
        return $this->converted_to_store_order_id === null && $this->dismissed_at === null;
    }

    public function shippingTotal(): float
    {
        return round(
            collect($this->shippingGroups())->sum(fn (array $group) => $group['charge']),
            2
        );
    }

    /**
     * Para cada grupo (override de producto o ShippingRule de marca/
     * categoría) que HOY está cobrando envío y todavía no alcanza su
     * umbral, devuelve cuánto le falta al cliente para desbloquear envío
     * gratis en esas líneas. Ordenado por "remaining" ascendente -- el
     * umbral más cerca de desbloquearse primero. Grupos sin threshold (el
     * envío de esa línea nunca se vuelve gratis por monto) no aparecen
     * aquí, ni tampoco los que ya están gratis.
     *
     * Formato de cada entrada: ['label', 'remaining', 'threshold', 'current']
     * -- 'current' es el subtotal (sin IVA) contra el que se compara ese
     * grupo en particular (el carrito completo para un override de
     * producto, o solo las líneas de esa marca/categoría para una regla).
     */
    public function freeShippingProgress(): array
    {
        return collect($this->shippingGroups())
            ->filter(fn (array $group) => !$group['is_free'] && $group['threshold'] !== null)
            ->map(fn (array $group) => [
                'label'     => $group['label'],
                'remaining' => round($group['threshold'] - $group['compare_subtotal'], 2),
                'threshold' => $group['threshold'],
                'current'   => $group['compare_subtotal'],
            ])
            ->sortBy('remaining')
            ->values()
            ->all();
    }

    /**
     * Agrupa las líneas del carrito por "qué las gobierna" para el cálculo
     * de envío -- ver el comentario largo arriba de total() para la regla de
     * negocio completa. Precarga TODAS las ShippingRule activas en una sola
     * query (agrupadas en memoria por brand_id/category_id) para no pagar
     * una query por item del carrito.
     *
     * Devuelve un array de grupos, cada uno con:
     *   - label: nombre de marca/categoría (regla) o nombre del producto
     *     (override individual) -- usado por freeShippingProgress().
     *   - shipping_cost: cargo fijo por línea de ese grupo.
     *   - threshold: free_shipping_threshold aplicable, o null si no tiene.
     *   - compare_subtotal: el subtotal (sin IVA) contra el que se compara
     *     ese threshold -- el carrito completo para overrides de producto,
     *     o solo las líneas de ese grupo para reglas de marca/categoría.
     *   - line_count: cuántas líneas de carrito caen en este grupo (el
     *     cargo se cobra una vez por línea, nunca por unidad).
     *   - is_free / charge: derivados, ya resueltos.
     */
    private function shippingGroups(): array
    {
        $cartSubtotal = $this->subtotal();

        $activeRules = ShippingRule::where('is_active', true)->get();
        $rulesByBrand = $activeRules->whereNotNull('brand_id')->keyBy('brand_id');
        $rulesByCategory = $activeRules->whereNotNull('category_id')->keyBy('category_id');

        $groups = [];

        foreach ($this->items as $item) {
            $product = $item->product;

            if (!$product) {
                continue;
            }

            // Caso 1: override propio del producto -- gana siempre, un grupo
            // por producto, comparado contra el subtotal TOTAL del carrito.
            if ($product->shipping_cost > 0) {
                $key = 'product:' . $product->id;
                $groups[$key] = [
                    'label'            => $product->name,
                    'shipping_cost'    => (float) $product->shipping_cost,
                    'threshold'        => $product->free_shipping_threshold !== null ? (float) $product->free_shipping_threshold : null,
                    'compare_subtotal' => $cartSubtotal,
                    'line_count'       => 1,
                ];

                continue;
            }

            // Caso 2: sin override propio -- busca ShippingRule aplicable,
            // marca primero, si no hay, categoría exacta del producto.
            $rule = null;
            $key = null;
            $label = null;

            if ($product->brand_id && $rulesByBrand->has($product->brand_id)) {
                $rule = $rulesByBrand->get($product->brand_id);
                $key = 'brand:' . $product->brand_id;
                $label = $product->brand->name ?? 'esta marca';
            } elseif ($product->category_id && $rulesByCategory->has($product->category_id)) {
                $rule = $rulesByCategory->get($product->category_id);
                $key = 'category:' . $product->category_id;
                $label = $product->category->name ?? 'esta categoría';
            }

            // Sin regla aplicable, o regla sin cargo (shipping_cost nulo/0):
            // envío estándar gratis para esta línea (caso 3), no forma grupo.
            if (!$rule || !($rule->shipping_cost > 0)) {
                continue;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label'            => $label,
                    'shipping_cost'    => (float) $rule->shipping_cost,
                    'threshold'        => $rule->free_shipping_threshold !== null ? (float) $rule->free_shipping_threshold : null,
                    'compare_subtotal' => 0.0,
                    'line_count'       => 0,
                ];
            }

            // Acumula SOLO el subtotal de las líneas que caen bajo esta
            // misma regla -- este es el punto central que distingue una
            // regla de marca/categoría de un override de producto.
            $groups[$key]['compare_subtotal'] += $item->quantity * $item->unit_price_snapshot;
            $groups[$key]['line_count']++;
        }

        foreach ($groups as &$group) {
            $group['is_free'] = $group['threshold'] !== null && $group['compare_subtotal'] >= $group['threshold'];
            $group['charge'] = $group['is_free'] ? 0.0 : $group['line_count'] * $group['shipping_cost'];
        }
        unset($group);

        return $groups;
    }
}
