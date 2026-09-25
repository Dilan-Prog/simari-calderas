<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 5 del plan de integración N8N. Solo lectura -- ability única
 * `collections:read` (config/api_abilities.php).
 */
class CollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Collection::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 25;

        $collections = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => CollectionResource::collection($collections->items()),
            'meta' => [
                'current_page' => $collections->currentPage(),
                'last_page'    => $collections->lastPage(),
                'per_page'     => $collections->perPage(),
                'total'        => $collections->total(),
            ],
        ]);
    }

    public function show(Collection $collection): JsonResponse
    {
        // Solo el conteo (no la lista completa de productos, que puede ser
        // grande y ya tiene su propio endpoint de solo-lectura en
        // ProductController::index para paginar/filtrar aparte).
        $collection->setAttribute('product_count', $collection->productsQuery()->count());

        return response()->json(['data' => new CollectionResource($collection)]);
    }
}
