<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\Products;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Products::where('slug', $slug)
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images', 'documents', 'brand', 'category.parent.parent'])
            ->firstOrFail();

        $specifications = $product->specifications
            ? collect(json_decode($product->specifications, true) ?? [])
            : collect();

        $sections = HomeSection::where('page', 'product')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.shop.product.show', compact('product', 'specifications', 'sections'));
    }
}
