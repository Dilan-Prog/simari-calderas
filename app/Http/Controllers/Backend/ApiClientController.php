<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Http\Request;

/**
 * CRUD de clientes de API (App\Models\ApiClient) + emisión/revocación de sus
 * tokens Sanctum, anidado bajo Integraciones ("API / N8N") -- mismo patrón
 * que WebhookController (catálogo reutilizable, modal-based, permiso
 * 'settings' del módulo de Integraciones).
 */
class ApiClientController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        ApiClient::create([
            ...$data,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Cliente de API creado. Ahora genera un token para él.');
    }

    public function toggle(ApiClient $apiClient)
    {
        $apiClient->update(['is_active' => ! $apiClient->is_active]);

        return back()->with('success', $apiClient->is_active
            ? 'Cliente de API activado.'
            : 'Cliente de API desactivado. Sus tokens dejarán de funcionar de inmediato.');
    }

    public function destroy(ApiClient $apiClient)
    {
        // Borra en cascada sus tokens (morphMany sobre personal_access_tokens,
        // sin FK física -- se limpia a mano antes de borrar el cliente).
        $apiClient->tokens()->delete();
        $apiClient->delete();

        return back()->with('success', 'Cliente de API eliminado.');
    }

    /**
     * Emite un token nuevo con las abilities elegidas del catálogo en
     * config/api_abilities.php. El valor plano solo se muestra una vez, en
     * la respuesta de este request -- Sanctum solo guarda el hash, igual
     * que el resto de los secretos de este panel (Mercado Pago, WhatsApp QR).
     */
    public function createToken(Request $request, ApiClient $apiClient)
    {
        $allAbilities = collect(config('api_abilities'))->flatMap(fn ($group) => array_keys($group))->all();

        $data = $request->validate([
            'token_name' => ['required', 'string', 'max:100'],
            'abilities'  => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'in:' . implode(',', $allAbilities)],
        ]);

        $token = $apiClient->createToken($data['token_name'], $data['abilities']);

        return back()->with('success', 'Token generado.')->with('plain_text_token', $token->plainTextToken);
    }

    public function revokeToken(ApiClient $apiClient, int $tokenId)
    {
        $apiClient->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'Token revocado. Cualquier integración que lo use dejará de funcionar de inmediato.');
    }
}
