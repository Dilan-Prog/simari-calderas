<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SupplierProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierProductController extends Controller
{
    // Usado desde ambos lados de la UI: pestaña "Proveedores" de un
    // Producto, y pestaña "Productos" de un Proveedor.

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'product_id'     => 'required|exists:products,id',
            'sku'            => 'nullable|string|max:100',
            'cost'           => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_primary'     => 'nullable|boolean',
        ]);

        $exists = SupplierProduct::where('supplier_id', $validated['supplier_id'])
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto ya está asignado a este proveedor.',
            ], 422);
        }

        $pivot = DB::transaction(function () use ($validated) {
            $row = SupplierProduct::create($validated);

            if ($row->is_primary) {
                SupplierProduct::demoteOtherPrimaries($validated['product_id'], $row->id);
            }

            return $row;
        });

        return response()->json([
            'success' => true,
            'message' => 'Proveedor vinculado correctamente.',
            'pivot'   => $pivot,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pivot = SupplierProduct::findOrFail($id);

        $validated = $request->validate([
            'sku'            => 'nullable|string|max:100',
            'cost'           => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'is_primary'     => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($pivot, $validated) {
            $pivot->update($validated);

            if ($pivot->is_primary) {
                SupplierProduct::demoteOtherPrimaries($pivot->product_id, $pivot->id);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente.',
            'pivot'   => $pivot->fresh(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $pivot = SupplierProduct::findOrFail($id);
        $pivot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vínculo eliminado correctamente.',
        ]);
    }
}
