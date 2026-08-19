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
            // Google exige image_link como campo obligatorio y rechaza
            // (o marca como "Falta la imagen del producto") cualquier item
            // sin uno — se excluyen del feed en vez de enviarlos sin
            // imagen, para no ensuciar el diagnóstico de Merchant Center
            // con productos que de todos modos serían rechazados.
            ->where(function ($q) {
                $q->whereNotNull('cover_image_url')->orWhereHas('images');
            })
            ->with(['brand', 'category.parent', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return response()
            ->view('frontend.feeds.google-merchant', compact('products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
