<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Products;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Products::where('slug', $slug)
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images', 'documents', 'brand', 'category.parent'])
            ->firstOrFail();

        $specifications = $product->specifications
            ? collect(json_decode($product->specifications, true) ?? [])
            : collect();

        $related = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images' => function ($q) {
                $q->orderBy('sort_order')->limit(1);
            }])
            ->take(12)
            ->get();

        return view('frontend.shop.product.show', compact('product', 'specifications', 'related'));
    }
}
