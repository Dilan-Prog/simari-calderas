<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:120',
            'slug'        => 'required|string|max:150|unique:product_categories,slug',
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'seo_title'   => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
        ]);

        $category = new Category();
        $category->name        = $request->name;
        $category->slug        = Str::slug($request->slug);
        $category->parent_id   = $request->parent_id   ?? null;
        $category->description = $request->description ?? null;
        $category->image_url   = $request->image_url   ?? null;
        $category->is_active   = $request->boolean('is_active', true);
        $category->sort_order  = $request->sort_order  ?? 0;
        $category->seo_title     = $request->seo_title     ?? null;
        $category->seo_description = $request->seo_description ?? null;
        $category->save();

        return response()->json([
            'success'  => true,
            'category' => $category->load('parent'),
        ]);
    }

    public function edit(string $id)
    {
        $category = Category::with(['parent.parent'])->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:120',
            'slug'        => 'required|string|max:150|unique:product_categories,slug,' . $id,
            'parent_id'   => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $category->name        = $request->name;
        $category->slug        = Str::slug($request->slug);
        $category->parent_id   = $request->parent_id   ?? null;
        $category->description = $request->description ?? null;
        $category->image_url   = $request->image_url   ?? null;
        $category->is_active   = $request->boolean('is_active', true);
        $category->sort_order  = $request->sort_order  ?? 0;
        $category->seo_title     = $request->seo_title     ?? null;
        $category->seo_description = $request->seo_description ?? null;
        $category->save();

        return response()->json([
            'success'  => true,
            'category' => $category->load('parent'),
        ]);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Prevent deleting if has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar una categoría que tiene subcategorías.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }
}
