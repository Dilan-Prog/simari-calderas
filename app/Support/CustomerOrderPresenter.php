<?php

namespace App\Support;

use App\Models\StoreOrder;
use Illuminate\Support\Collection;

/**
 * Convierte un StoreOrder en el arreglo que consumen las vistas de "Mis
 * pedidos" del cliente (tienda /cuenta y portal /customer/pedidos).
 *
 * Es la ÚNICA puerta entre el modelo y las vistas del cliente: aquí se decide
 * qué se expone. Nunca se pasan notas internas, motivos de cancelación,
 * usuarios del admin, costos del envío ni la respuesta cruda de Mercado Pago.
 * Se incluyen precios y totales porque es la compra del propio cliente.
 */
class CustomerOrderPresenter
{
    /** Relaciones (con columnas acotadas) que necesita build(). */
    public static function eagerLoads(): array
    {
        return [
            'items:id,store_order_id,product_id,product_name,product_sku,quantity,unit_price,line_total',
            'items.product:id,cover_image_url',
            'paymentMethod:id,type,name',
            'shipment:id,store_order_id,carrier_id,guia,status,fecha_envio,eta,shipped_at',
            'shipment.carrier:id,name,tracking_url_template',
            // Sin note/motivo/changed_by: solo fechas de cada cambio de estatus.
            'statusLogs:id,store_order_id,to_status,created_at',
            'payments:id,store_order_id,charge_group,amount,installments,mp_payment_method,mp_payment_type,status,paid_at',
        ];
    }

    /** Pedidos propios de un cliente, más recientes primero. */
    public static function forCustomer(int $customerId, ?int $limit = null): Collection
    {
        $query = StoreOrder::query()
            ->where('customer_id', $customerId)
            ->with(self::eagerLoads())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (StoreOrder $order) => self::build($order));
    }

    public static function build(StoreOrder $order): array
    {
        $meta = StoreOrderStatus::meta((string) $order->status);

        $items = $order->items->map(fn ($item) => [
            'name'       => (string) $item->product_name,
            'sku'        => $item->product_sku ?: null,
            'qty'        => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'line_total' => (float) $item->line_total,
            'image'      => $item->product?->cover_image_url,
        ])->all();

        return [
            'id'            => $order->id,
            'number'        => (string) $order->order_number,
            'date'          => $order->created_at?->locale('es')->isoFormat('D MMM YYYY, HH:mm'),
            'date_short'    => $order->created_at?->locale('es')->isoFormat('D MMM YYYY'),
            'status'        => (string) $order->status,
            'status_label'  => $meta['label'] ?? (string) $order->status,
            'status_color'  => $meta['color'] ?? '#6b7280',
            'status_bg'     => $meta['bg'] ?? '#f1f2f4',
            'items'         => $items,
            'items_count'   => (int) $order->items->sum('quantity'),
            'currency'      => $order->currency ?: 'MXN',
            'subtotal'      => (float) $order->subtotal,
            'shipping'      => (float) $order->shipping_total,
            'discount'      => (float) $order->discount_total,
            'tax'           => (float) $order->tax_total,
            'total'         => (float) $order->total,
            'timeline'      => self::timeline($order),
            'address'       => self::address($order),
            'payment'       => self::payment($order),
            'shipment'      => self::shipment($order),
            'invoice'       => self::invoice($order),
        ];
    }

    private static function timeline(StoreOrder $order): array
    {
        $status = (string) $order->status;
        $flow = StoreOrderStatus::FLOW;
        $currentIndex = array_search($status, $flow, true);

        // Primera fecha en la que el pedido llegó a cada estatus.
        $reachedAt = [];
        foreach ($order->statusLogs->sortBy('created_at') as $log) {
            $reachedAt[$log->to_status] ??= $log->created_at;
        }
        $reachedAt['pendiente_pago'] = $order->created_at;

        $steps = [];
        foreach ($flow as $index => $key) {
            if ($currentIndex !== false) {
                $done = $index <= $currentIndex;
            } elseif ($status === 'pago_parcial') {
                $done = $index === 0;
            } else { // cancelado / reembolsado: solo lo que realmente ocurrió
                $done = isset($reachedAt[$key]);
            }

            $steps[] = [
                'label'    => StoreOrderStatus::META[$key]['label'],
                'done'     => $done,
                'terminal' => false,
                'date'     => $done && isset($reachedAt[$key])
                    ? $reachedAt[$key]->locale('es')->isoFormat('D MMM, HH:mm')
                    : null,
            ];
        }

        if ($status === 'pago_parcial') {
            $steps[1]['hint'] = 'Pago parcial: falta completar un cobro';
        }

        $terminals = match ($status) {
            'cancelado'   => ['cancelado'],
            'reembolsado' => ['cancelado', 'reembolsado'],
            default       => [],
        };
        foreach ($terminals as $terminal) {
            $steps[] = [
                'label'    => StoreOrderStatus::META[$terminal]['label'],
                'done'     => true,
                'terminal' => true,
                'date'     => isset($reachedAt[$terminal])
                    ? $reachedAt[$terminal]->locale('es')->isoFormat('D MMM, HH:mm')
                    : null,
            ];
        }

        return $steps;
    }

    private static function address(StoreOrder $order): array
    {
        $cityLine = trim(implode(', ', array_filter([$order->shipping_city, $order->shipping_state])));
        if ($order->shipping_postal_code) {
            $cityLine .= ($cityLine !== '' ? ' · ' : '') . 'CP ' . $order->shipping_postal_code;
        }

        // line2 suele repetir line1 (colonia/número interior); se omite si es idéntica.
        $line2 = $order->shipping_address_line2;
        if ($line2 === $order->shipping_address_line1) {
            $line2 = null;
        }

        return [
            'name'      => $order->contact_name,
            'phone'     => $order->contact_phone,
            'line1'     => $order->shipping_address_line1,
            'line2'     => $line2,
            'reference' => $order->shipping_reference,
            'city_line' => $cityLine,
        ];
    }

    private static function payment(StoreOrder $order): array
    {
        $status = (string) $order->status;

        $statusLabel = match ($status) {
            'pendiente_pago' => 'Pendiente de pago',
            'pago_parcial'   => 'Pago parcial',
            'cancelado'      => 'Sin cobro',
            'reembolsado'    => 'Reembolsado',
            default          => 'Pagado',
        };
        $tone = match ($status) {
            'pendiente_pago', 'pago_parcial' => 'warn',
            'cancelado', 'reembolsado'       => 'bad',
            default                          => 'ok',
        };

        // Detalle de tarjeta solo de cobros aprobados (Mercado Pago).
        $approved = $order->payments->where('status', 'approved');
        $detail = null;
        if ($approved->isNotEmpty()) {
            $typeLabels = [
                'credit_card'  => 'Tarjeta de crédito',
                'debit_card'   => 'Tarjeta de débito',
                'account_money' => 'Dinero en cuenta Mercado Pago',
                'ticket'       => 'Pago en efectivo',
                'bank_transfer' => 'Transferencia',
            ];
            $parts = [];
            foreach ($approved as $payment) {
                $label = $typeLabels[$payment->mp_payment_type] ?? null;
                if ($label && $payment->mp_payment_method && in_array($payment->mp_payment_type, ['credit_card', 'debit_card'], true)) {
                    $label .= ' ' . strtoupper($payment->mp_payment_method);
                }
                if ($label && (int) $payment->installments > 1) {
                    $label .= ' · ' . (int) $payment->installments . ' meses';
                }
                if ($label) {
                    $parts[] = $label;
                }
            }
            $detail = $parts ? implode(' + ', array_unique($parts)) : null;
        }

        return [
            'method'       => $order->paymentMethod?->name,
            'detail'       => $detail,
            'status_label' => $statusLabel,
            'tone'         => $tone,
            'paid_at'      => $approved->max('paid_at')?->locale('es')->isoFormat('D MMM YYYY, HH:mm'),
        ];
    }

    private static function shipment(StoreOrder $order): ?array
    {
        $shipment = $order->shipment;
        if (! $shipment) {
            return null;
        }

        $guia = trim((string) $shipment->guia);
        $template = (string) $shipment->carrier?->tracking_url_template;

        $trackingUrl = null;
        if ($guia !== '' && preg_match('#^https?://#i', $template)) {
            $cleanGuia = rawurlencode(str_replace(' ', '', $guia));
            $trackingUrl = str_contains($template, '{guia}')
                ? str_replace('{guia}', $cleanGuia, $template)
                : $template . $cleanGuia;
        }

        $statusMeta = ShipmentStatus::META[$shipment->status] ?? null;

        return [
            'carrier'      => $shipment->carrier?->name,
            'guia'         => $guia !== '' ? $guia : null,
            'status_label' => $statusMeta['label'] ?? null,
            'shipped_at'   => ($shipment->fecha_envio ?? $shipment->shipped_at)?->locale('es')->isoFormat('D MMM YYYY'),
            'eta'          => $shipment->eta?->locale('es')->isoFormat('D MMM YYYY'),
            'tracking_url' => $trackingUrl,
        ];
    }

    private static function invoice(StoreOrder $order): ?array
    {
        if (! $order->requires_invoice) {
            return null;
        }

        return [
            'razon_social' => $order->razon_social,
            'rfc'          => $order->rfc,
            'regimen'      => $order->regimen_fiscal,
            'uso_cfdi'     => $order->uso_cfdi,
            'cp_fiscal'    => $order->cp_fiscal,
        ];
    }
}
