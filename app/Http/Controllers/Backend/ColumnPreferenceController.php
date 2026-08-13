<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserColumnPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ColumnPreferenceController extends Controller
{
    /**
     * Claves válidas de table_key — contrato fijo con el cableado que se
     * hace por módulo (19 tablas de listado del admin). No agregar ni
     * quitar claves aquí sin actualizar también los 19 index() que las usan.
     */
    private const VALID_TABLE_KEYS = [
        'clients.index',
        'users.index',
        'roles.index',
        'service-reports.index',
        'categories.index',
        'purchase-orders.index',
        'quotes.index',
        'technical-services.index',
        'collections.index',
        'menus.index',
        'products.index',
        'home-sections.index',
        'brands.index',
        'suppliers.index',
        'service-pages.index',
        'sales-orders.index',
        'orders.index',
        'chemical-planning.index',
        'google-ads.index',
        'inventory-movements.index',
    ];

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_key' => ['required', 'string', Rule::in(self::VALID_TABLE_KEYS)],
            'columns' => ['required', 'array'],
            'columns.*' => ['string'],
        ]);

        UserColumnPreference::updateOrCreate(
            ['user_id' => auth()->id(), 'table_key' => $validated['table_key']],
            ['columns' => $validated['columns']]
        );

        return response()->json(['success' => true]);
    }
}
