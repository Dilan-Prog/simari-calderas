<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Products;
use App\Models\WarehouseProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 5 del plan de integración N8N (ver
 * C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md). Solo
 * lectura -- ability única `products:read` (config/api_abilities.php).
 *
 * El modelo `Products` (plural, confirmado en app/Models/Products.php) no
 * declara una relación `warehouseStocks()` -- para no tocar ese modelo
 * compartido desde esta fase, show() consulta WarehouseProductStock aparte y
 * la "inyecta" vía setRelation(), que ProductResource lee con
 * whenLoaded()/relationLoaded() exactamente igual que si fuera una relación
 * real declarada.
 */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Products::query()->with(['category', 'brand']);

        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->string('sku') . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        if ($request->filled('availability')) {
            $query->where('availability', $request->string('availability'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 25;

        $products = $query->orderBy('id')->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function show(Products $product): JsonResponse
    {
        $product->load(['category', 'brand']);

        $stocks = WarehouseProductStock::with('warehouse')
            ->where('product_id', $product->id)
            ->get();

        $product->setRelation('warehouseStocks', $stocks);

        return response()->json(['data' => new ProductResource($product)]);
    }
}
