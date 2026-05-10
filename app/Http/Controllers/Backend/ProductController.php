<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Products;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Brand;

class ProductController extends Controller
{
    public function index()
    {
        $products = Products::with(['category', 'brand'])->get([
            'id',
            'name',
            'sku',
            'price',
            'cost',
            'stock',
            'is_active',
            'is_featured',
            'cover_image_url',
            'category_id',
            'brand_id',
        ]);

        return view('admin.products.index', compact('products'));
    }
    public function create()
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)->select('id', 'name', 'parent_id')])
            ->get(['id', 'name']);

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        return view('admin.products.create_product.create', compact('categories', 'brands'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku',
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'weight'            => 'nullable|numeric|min:0',
            'height'            => 'nullable|numeric|min:0',
            'width'             => 'nullable|numeric|min:0',
            'length'            => 'nullable|numeric|min:0',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            'slug'              => 'nullable|string|max:255|unique:products,slug',
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
        ]);

        $product = new Products();
        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $product->price             = $request->price;
        $product->cost              = $request->cost              ?? 0;
        $product->compare_price     = $request->compare_price     ?? null;
        $product->stock             = $request->stock             ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description       ?? null;
        $product->weight            = $request->weight            ?? null;
        $product->height            = $request->height            ?? null;
        $product->width             = $request->width             ?? null;
        $product->length            = $request->length            ?? null;
        $product->seo_title         = $request->seo_title         ?? null;
        $product->seo_description   = $request->seo_description   ?? null;
        $product->category_id = $request->category_id ?? null;
        $product->brand_id    = $request->brand_id    ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->save();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }
    public function edit($id)
    {
        $product = Products::with(['category', 'brand'])->findOrFail($id);

        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->get(['id', 'name']);

        $brands = Brand::where('is_active', true)->get(['id', 'name']);

        return view('admin.products.edit_product.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, string $id)
    {
        $product = Products::findOrFail($id);

        $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:products,sku,' . $id,
            'price'             => 'required|numeric|min:0',
            'cost'              => 'nullable|numeric|min:0',
            'compare_price'     => 'nullable|numeric|min:0',
            'stock'             => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'weight'            => 'nullable|numeric|min:0',
            'height'            => 'nullable|numeric|min:0',
            'width'             => 'nullable|numeric|min:0',
            'length'            => 'nullable|numeric|min:0',
            'seo_title'         => 'nullable|string|max:60',
            'seo_description'   => 'nullable|string|max:160',
            'slug'              => 'nullable|string|max:255|unique:products,slug,' . $id,
            'is_active'         => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
        ]);

        $product->name              = $request->name;
        $product->sku               = $request->sku;
        $product->slug              = $request->slug ? Str::slug($request->slug) : Str::slug($request->name);
        $product->price             = $request->price;
        $product->cost              = $request->cost              ?? 0;
        $product->compare_price     = $request->compare_price     ?? null;
        $product->stock             = $request->stock             ?? 0;
        $product->short_description = $request->short_description ?? null;
        $product->description       = $request->description       ?? null;
        $product->weight            = $request->weight            ?? null;
        $product->height            = $request->height            ?? null;
        $product->width             = $request->width             ?? null;
        $product->length            = $request->length            ?? null;
        $product->seo_title         = $request->seo_title         ?? null;
        $product->category_id       = $request->category_id       ?? null;
        $product->brand_id          = $request->brand_id          ?? null;
        $product->seo_description   = $request->seo_description   ?? null;
        $product->is_active         = $request->boolean('is_active',  true);
        $product->is_featured       = $request->boolean('is_featured', false);
        $product->save();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $product = Products::findOrFail($id);
        $product->suppliers()->detach();
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
