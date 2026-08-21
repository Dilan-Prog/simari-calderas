<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * CRUD del catálogo "Webhooks" (admin, dentro de Integraciones). Mismo
 * patrón modal-based con respuestas JSON usado por CredentialController —
 * sin páginas propias de create/edit, todo vía index (IntegrationController)
 * + modal.
 *
 * A diferencia de Credential, Webhook no tiene ningún secreto propio (los
 * headers son solo nombres/valores que la persona capturó a mano; el
 * secreto real, si lo hay, vive en la Credential opcionalmente referenciada
 * por credential_id) — por eso edit() sí puede regresar headers/credential_id
 * completos, sin necesitar ocultarlos como hace CredentialController.
 */
class WebhookController extends Controller
{
    private function validateWebhook(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|string|max:2048',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
            'headers' => 'nullable|array',
            'headers.*.key' => 'nullable|string',
            'headers.*.value' => 'nullable|string',
            'credential_id' => 'nullable|exists:credentials,id',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateWebhook($request);

        Webhook::create([
            'name' => $data['name'],
            'url' => $data['url'],
            'method' => $data['method'] ?? 'POST',
            'headers' => $data['headers'] ?? null,
            'credential_id' => $data['credential_id'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json(['success' => true]);
    }

    public function edit(Webhook $webhook)
    {
        return response()->json([
            'id' => $webhook->id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'method' => $webhook->method,
            'headers' => $webhook->headers,
            'credential_id' => $webhook->credential_id,
        ]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $data = $this->validateWebhook($request);

        $webhook->update([
            'name' => $data['name'],
            'url' => $data['url'],
            'method' => $data['method'] ?? 'POST',
            'headers' => $data['headers'] ?? null,
            'credential_id' => $data['credential_id'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Lista de solo lectura para el <select> de credencial del modal —
     * nunca expone el payload/secreto. Filtra por type='webhook', mismo
     * criterio que WorkflowWebhookCredentialController usa para el picker
     * del lienzo de Automatizaciones.
     */
    public function credentials()
    {
        $credentials = Credential::where('type', 'webhook')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($credentials);
    }

    /**
     * Botón "Probar" del modal de crear/editar. Envía una petición real al
     * endpoint configurado (sin persistir nada) para confirmar que se puede
     * alcanzar. Mismo armado de headers (incluida la fusión con la
     * credencial tipo 'webhook', si aplica) que
     * WorkflowActionExecutor::callWebhook() usa en producción, para que la
     * prueba refleje exactamente lo que pasará al correr el workflow.
     */
    public function test(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string',
            'method' => 'nullable|string',
            'headers' => 'nullable|array',
            'credential_id' => 'nullable|exists:credentials,id',
        ]);

        $method = strtoupper((string) ($data['method'] ?? 'POST'));

        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $method = 'POST';
        }

        $headers = [];

        foreach ($data['headers'] ?? [] as $row) {
            $key = $row['key'] ?? null;

            if (blank($key)) {
                continue;
            }

            $headers[$key] = $row['value'] ?? null;
        }

        $credentialId = $data['credential_id'] ?? null;

        if ($credentialId) {
            $credential = Credential::find($credentialId);

            if ($credential && $credential->type === 'webhook') {
                $payload = $credential->payload;

                if (is_array($payload) && filled($payload['header_name'] ?? null) && filled($payload['header_value'] ?? null)) {
                    $headers[$payload['header_name']] = $payload['header_value'];
                }
            }
        }

        $start = microtime(true);

        try {
            $response = Http::timeout(10)->withHeaders($headers)->send($method, $data['url'], [
                'json' => ['test' => true, 'source' => 'Equiterm Industries'],
            ]);

            $ms = (int) round((microtime(true) - $start) * 1000);

            return response()->json([
                'ok' => $response->successful(),
                'status' => $response->status(),
                'ms' => $ms,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
