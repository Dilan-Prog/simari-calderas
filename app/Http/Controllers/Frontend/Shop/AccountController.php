<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $customer->load([
            'customer_addresses' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'),
            'portalRequest',
        ]);

        return view('frontend.shop.account.index', compact('customer'));
    }
}
