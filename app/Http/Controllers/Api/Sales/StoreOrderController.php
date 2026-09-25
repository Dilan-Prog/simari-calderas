<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreOrderResource;
use App\Models\StoreOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 4 del plan de integración N8N -- solo lectura por ahora (el plan es
 * explícito: "sincronización hacia afuera, no creación vía API en esta fase").
 */
class StoreOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StoreOrder::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100) ?: 15;
        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => StoreOrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(StoreOrder $storeOrder): JsonResponse
    {
        $storeOrder->load(['customer', 'items']);

        return response()->json(['data' => new StoreOrderResource($storeOrder)]);
    }
}
