<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\HomeSection;

class CollectionController extends Controller
{
    public function show(string $slug)
    {
        $collection = Collection::where('slug', $slug)
            ->where('is_active', true)
            ->with('rules')
            ->firstOrFail();

        $products = $collection->productsQuery()
            ->paginate(24)
            ->withQueryString();

        $sections = HomeSection::where('page', 'collection')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.shop.collection.show', compact('collection', 'products', 'sections'));
    }
}
