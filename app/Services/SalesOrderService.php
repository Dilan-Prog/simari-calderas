<?php

namespace App\Services;

use App\Models\Products;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    /**
     * Genera un Pedido a partir de las líneas de producto (product_id no
     * nulo) de una cotización aceptada. Las líneas de servicio no pasan por
     * aquí — siguen el flujo manual de "Generar Servicio" de siempre.
     */
    public function createFromQuoteItems(Quote $quote, Collection $productItems): SalesOrder
    {
        return DB::transaction(function () use ($quote, $productItems) {
            $order = SalesOrder::create([
                'order_number'       => SalesOrder::generateOrderNumber(),
                'quote_id'           => $quote->id,
                'customer_id'        => $quote->customer_id,
                'created_by_user_id' => auth()->id(),
                'status'             => 'pendiente',
            ]);

            foreach ($productItems->values() as $i => $item) {
                $order->items()->create([
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku'  => $item->product_sku,
                    'unit'         => $item->product?->stock_unit,
                    'quantity_ordered' => $item->quantity,
                    'sort_order'   => $i,
                ]);
            }

            return $order;
        });
    }

    /**
     * Registra una entrega (parcial o total) contra una línea del pedido:
     * nunca sobre-entrega, descuenta el stock del producto, y recalcula el
     * status del pedido — todo dentro de una transacción.
     */
    public function registerDelivery(SalesOrderItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $quantity = min($quantity, $item->pending);

            if ($quantity <= 0) {
                return;
            }

            $item->increment('quantity_delivered', $quantity);

            if ($item->product_id) {
                app(\App\Services\InventoryService::class)->applyMovement(
                    app(\App\Services\InventoryService::class)->defaultWarehouseId(),
                    $item->product_id,
                    'salida',
                    $quantity,
                    \App\Models\SalesOrderItem::class,
                    $item->id,
                    "Entrega Pedido " . ($item->salesOrder->order_number ?? '')
                );
            }

            $item->salesOrder->recalculateStatus();
        });
    }
}
