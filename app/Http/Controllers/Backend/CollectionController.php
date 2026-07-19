<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\CollectionRule;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $categories = Category::where('is_active', true)->get(['id', 'name']);
        $brands     = Brand::where('is_active', true)->get(['id', 'name']);

        return view('admin.collections.index', compact('collections', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'slug'        => 'required|string|max:180|unique:collections,slug',
            'description' => 'nullable|string',
            'type'        => 'required|in:manual,automatic',
            'match_type'  => 'required_if:type,automatic|nullable|in:all,any',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $collection = new Collection();
        $collection->name        = $request->name;
        $collection->slug        = Str::slug($request->slug);
        $collection->description = $request->description ?? null;
        $collection->type        = $request->type;
        $collection->match_type  = $request->type === 'automatic' ? $request->match_type : null;
        $collection->image_url   = $request->image_url ?? null;
        $collection->sort_order  = $request->sort_order ?? 0;
        $collection->is_active   = $request->boolean('is_active', true);
        $collection->save();

        $this->syncRules($collection, $request);

        return response()->json([
            'success'    => true,
            'collection' => $collection->load('rules'),
        ]);
    }

    public function edit(string $id)
    {
        $collection = Collection::with('rules')->findOrFail($id);
        return response()->json($collection);
    }

    public function update(Request $request, string $id)
    {
        $collection = Collection::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:150',
            'slug'        => 'required|string|max:180|unique:collections,slug,' . $id,
            'description' => 'nullable|string',
            'type'        => 'required|in:manual,automatic',
            'match_type'  => 'required_if:type,automatic|nullable|in:all,any',
            'image_url'   => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
        ]);

        $collection->name        = $request->name;
        $collection->slug        = Str::slug($request->slug);
        $collection->description = $request->description ?? null;
        $collection->type        = $request->type;
        $collection->match_type  = $request->type === 'automatic' ? $request->match_type : null;
        $collection->image_url   = $request->image_url ?? null;
        $collection->sort_order  = $request->sort_order ?? 0;
        $collection->is_active   = $request->boolean('is_active', true);
        $collection->save();

        $this->syncRules($collection, $request);

        return response()->json([
            'success'    => true,
            'collection' => $collection->load('rules'),
        ]);
    }

    public function destroy(string $id)
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();

        return response()->json(['success' => true]);
    }

    public function show(string $id)
    {
        $collection = Collection::with(['manualProducts' => function ($q) {
            $q->with(['category:id,name', 'brand:id,name']);
        }])->findOrFail($id);

        return view('admin.collections.show', compact('collection'));
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');

        $products = Products::where('name', 'like', "%{$q}%")
            ->orWhere('sku', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'name', 'sku', 'cover_image_url', 'price']);

        return response()->json($products);
    }

    public function addProduct(Request $request, string $collection)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $collectionModel = Collection::findOrFail($collection);

        $alreadyExists = $collectionModel->manualProducts()
            ->where('products.id', $request->product_id)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false,
                'message' => 'El producto ya está en esta colección.',
            ], 422);
        }

        $nextOrder = ($collectionModel->manualProducts()->max('collection_products.sort_order') ?? -1) + 1;

        $collectionModel->manualProducts()->attach($request->product_id, [
            'sort_order' => $nextOrder,
        ]);

        $product = Products::select('id', 'name', 'sku', 'cover_image_url', 'price')
            ->findOrFail($request->product_id);

        return response()->json([
            'success' => true,
            'product' => array_merge($product->toArray(), ['sort_order' => $nextOrder]),
        ]);
    }

    public function removeProduct(string $collection, string $product)
    {
        $collectionModel = Collection::findOrFail($collection);
        $collectionModel->manualProducts()->detach($product);

        return response()->json(['success' => true]);
    }

    public function reorderProducts(Request $request, string $collection)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:products,id',
        ]);

        $collectionModel = Collection::findOrFail($collection);

        $currentIds = $collectionModel->manualProducts()->pluck('products.id')->all();

        if (count($request->order) !== count($currentIds) || array_diff($request->order, $currentIds)) {
            return response()->json([
                'success' => false,
                'message' => 'La lista de productos no coincide con la colección.',
            ], 422);
        }

        foreach ($request->order as $index => $productId) {
            $collectionModel->manualProducts()->updateExistingPivot($productId, [
                'sort_order' => $index,
            ]);
        }

        return response()->json(['success' => true]);
    }

    protected function syncRules(Collection $collection, Request $request): void
    {
        if ($collection->type !== 'automatic') {
            return;
        }

        $collection->rules()->delete();

        if ($request->filled('rule_field')) {
            foreach ($request->rule_field as $i => $field) {
                $value = $request->rule_value[$i] ?? '';
                if (empty($field) || $value === '') {
                    continue;
                }

                CollectionRule::create([
                    'collection_id' => $collection->id,
                    'field'         => $field,
                    'operator'      => $request->rule_operator[$i] ?? 'equals',
                    'value'         => $value,
                ]);
            }
        }
    }
}
