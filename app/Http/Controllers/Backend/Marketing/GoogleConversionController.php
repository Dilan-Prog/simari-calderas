<?php

namespace App\Http\Controllers\Backend\Marketing;

use App\Http\Controllers\Controller;
use App\Models\GoogleConversion;
use App\Jobs\SendGoogleConversionsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GoogleConversionController extends Controller
{
    /**
     * Registra una conversión cuando el usuario completa una acción
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gclid'            => 'required|string|max:255',
            'conversion_name'  => 'required|string|max:255',
            'conversion_value' => 'nullable|numeric|min:0',
            'currency_code'    => 'nullable|string|size:3',
            'order_id'         => 'nullable|string|unique:google_conversions,order_id',
        ]);

        $conversion = GoogleConversion::create([
            ...$validated,
            'conversion_time' => now(),
            'currency_code'   => $validated['currency_code'] ?? 'MXN',
            'status'          => 'stored',
        ]);

        return response()->json([
            'message' => 'Conversión registrada correctamente.',
            'data'    => $conversion,
        ], 201);
    }

    /**
     * Lista conversiones con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $conversions = GoogleConversion::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from,   fn($q) => $q->whereDate('conversion_time', '>=', $request->from))
            ->when($request->to,     fn($q) => $q->whereDate('conversion_time', '<=', $request->to))
            ->latest()
            ->paginate(50);

        return response()->json($conversions);
    }

    /**
     * Reintenta manualmente las conversiones fallidas
     */
    public function retry(): JsonResponse
    {
        $failed = GoogleConversion::failed()->get();

        $failed->each(fn($c) => SendGoogleConversionsJob::dispatch($c));

        return response()->json([
            'message' => "{$failed->count()} conversiones reenviadas.",
        ]);
    }
}
