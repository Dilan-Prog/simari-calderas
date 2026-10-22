<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use App\Support\CustomerOrderPresenter;

/**
 * "Mis pedidos" del portal de servicios: pedidos de la tienda web del propio
 * cliente (StoreOrder). Solo lectura. Comparte con /cuenta el armado de datos
 * (CustomerOrderPresenter), que decide qué campos son visibles al cliente.
 *
 * El parámetro de ruta {order} es el folio (order_number, ej. PW-2026-0004);
 * se resuelve aquí de forma explícita y siempre acotado al cliente autenticado,
 * así que el pedido de otro cliente responde 404.
 */
class OrderController extends Controller
{
    private const LIST_LIMIT = 100;

    public function index()
    {
        $orders = CustomerOrderPresenter::forCustomer(auth('customer')->id(), self::LIST_LIMIT);

        return view('customers.orders.index', [
            'orders'  => $orders,
            'hasMore' => $orders->count() >= self::LIST_LIMIT,
        ]);
    }

    public function show(string $order)
    {
        $storeOrder = StoreOrder::query()
            ->where('customer_id', auth('customer')->id())
            ->where('order_number', $order)
            ->with(CustomerOrderPresenter::eagerLoads())
            ->firstOrFail();

        return view('customers.orders.show', [
            'o' => CustomerOrderPresenter::build($storeOrder),
        ]);
    }
}
