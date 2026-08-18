<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Products;

/**
 * Feed XML (RSS 2.0 + espacio de nombres g:) que Google Merchant Center jala
 * por "Recuperación programada" cada 24h. Solo incluye productos que ya son
 * públicos en el catálogo (is_active + publish_on_website) Y que el admin no
 * excluyó explícitamente vía el switch "Mostrar en Google Merchant Center"
 * (Productos > Organización) — show_in_merchant_center.
 */
class GoogleMerchantFeedController extends Controller
{
    public function index()
    {
        $products = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->where('show_in_merchant_center', true)
            ->with(['brand', 'category.parent', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return response()
            ->view('frontend.feeds.google-merchant', compact('products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
