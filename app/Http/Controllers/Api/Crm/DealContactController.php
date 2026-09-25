<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\DealContactResource;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 3 (API N8N) — CRM / Deal Contacts. Nested bajo /deals/{deal}/contacts.
 * Deal::contacts() es belongsToMany(Customer, 'deal_contacts')->withPivot('role')
 * (app/Models/Deal.php) -- no hay modelo DealContact propio, así que se
 * opera directo sobre esa relación en vez de inventar un modelo Eloquent
 * que no existe en el esquema.
 */
class DealContactController extends Controller
{
    public function index(Deal $deal): JsonResponse
    {
        $contacts = $deal->contacts()->get();

        return response()->json(['data' => DealContactResource::collection($contacts)]);
    }

    public function store(Request $request, Deal $deal): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'role'        => 'nullable|string|max:255',
        ]);

        // syncWithoutDetaching respeta la UNIQUE(deal_id, customer_id) de la
        // tabla pivote sin lanzar un 500 si N8N reintenta el mismo par
        // (ej. tras un timeout) -- actualiza el role si ya existía en vez de
        // duplicar o fallar.
        $deal->contacts()->syncWithoutDetaching([
            $data['customer_id'] => ['role' => $data['role'] ?? null],
        ]);

        // Vuelve a leer el contacto desde la propia relación (en vez de
        // Customer::find() + setRelation manual) para que el pivot venga
        // cargado tal cual lo arma belongsToMany, sin tocar su query interna.
        $contact = $deal->contacts()->where('customers.id', $data['customer_id'])->firstOrFail();

        return response()->json(['data' => new DealContactResource($contact)], 201);
    }
}
