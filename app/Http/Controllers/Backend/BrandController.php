<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120|unique:brands,name',
            'slug'        => 'required|string|max:150|unique:brands,slug',
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $brand = new Brand();
        $brand->name        = $request->name;
        $brand->slug        = Str::slug($request->slug);
        $brand->description = $request->description ?? null;
        $brand->logo_url    = $request->logo_url    ?? null;
        $brand->is_active   = $request->boolean('is_active', true);
        $brand->save();

        return response()->json([
            'success' => true,
            'brand'   => $brand->loadCount('products'),
        ]);
    }

    public function edit(string $id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, string $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:120|unique:brands,name,' . $id,
            'slug'        => 'required|string|max:150|unique:brands,slug,' . $id,
            'description' => 'nullable|string',
            'logo_url'    => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $brand->name        = $request->name;
        $brand->slug        = Str::slug($request->slug);
        $brand->description = $request->description ?? null;
        $brand->logo_url    = $request->logo_url    ?? null;
        $brand->is_active   = $request->boolean('is_active', true);
        $brand->save();

        return response()->json([
            'success' => true,
            'brand'   => $brand->loadCount('products'),
        ]);
    }

    public function destroy(string $id)
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        if ($brand->products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar una marca que tiene productos asociados.',
            ], 422);
        }

        $brand->delete();
        return response()->json(['success' => true]);
    }
}
