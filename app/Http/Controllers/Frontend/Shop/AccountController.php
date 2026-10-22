<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Support\CustomerOrderPresenter;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    // Tope de pedidos que se renderizan (cada uno trae su detalle en la misma página).
    private const ORDERS_LIMIT = 50;

    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $customer->load([
            'customer_addresses' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'),
            'portalRequest',
        ]);

        // Solo pedidos del propio cliente; CustomerOrderPresenter define qué
        // datos llegan a la vista (nunca notas internas ni datos del admin).
        $orders = CustomerOrderPresenter::forCustomer($customer->id, self::ORDERS_LIMIT);
        $ordersHasMore = $orders->count() >= self::ORDERS_LIMIT;

        return view('frontend.shop.account.index', compact('customer', 'orders', 'ordersHasMore'));
    }
}
