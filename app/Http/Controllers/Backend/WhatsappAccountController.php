<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;

/**
 * CRUD del catálogo "Cuentas de WhatsApp" (admin). Modal-based con
 * respuestas JSON, mismo patrón que CredentialController — el
 * access_token real nunca sale del servidor (el modelo lo oculta con
 * $hidden), así que editar una cuenta re-captura el token desde cero.
 */
class WhatsappAccountController extends Controller
{
    /**
     * La pantalla standalone se fusionó dentro de Integraciones (paneles
     * "WhatsApp API" / "WhatsApp Web") -- este GET ya no tiene vista propia,
     * solo redirige para no dejar bookmarks/enlaces viejos rotos. store/
     * update/destroy/edit/qrStatus siguen siendo el backend real, ahora
     * consumido por fetch desde admin.integrations.index.
     */
    public function index()
    {
        return redirect()->route('admin.integrations.index');
    }

    private function validateAccount(Request $request): array
    {
        return $request->validate([
            'name'                          => 'required|string|max:100',
            // Las cuentas "baileys_qr" (Part D) no capturan número visible en
            // el formulario -- lo reporta el microservicio de Baileys una vez
            // conectada la sesión -- así que solo es obligatorio para
            // meta_cloud_api (incluyendo cuando el campo no se envía, que es
            // el default del modelo/columna).
            'phone_number'                  => 'required_unless:connection_type,baileys_qr|nullable|string|max:30',
            'phone_number_id'               => 'nullable|string|max:100',
            'whatsapp_business_account_id'  => 'nullable|string|max:100',
            'webhook_verify_token'          => 'nullable|string|max:100',
            'access_token'                  => 'nullable|string',
            'app_secret'                    => 'nullable|string',
            'connection_type'               => 'nullable|in:meta_cloud_api,baileys_qr',
            'is_active'                     => 'nullable|boolean',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAccount($request);

        $account = new WhatsappAccount();
        $account->name = $data['name'];
        $account->phone_number = $data['phone_number'] ?? null;
        $account->phone_number_id = $data['phone_number_id'] ?? null;
        $account->whatsapp_business_account_id = $data['whatsapp_business_account_id'] ?? null;
        $account->webhook_verify_token = $data['webhook_verify_token'] ?? null;
        $account->provider = 'meta_cloud_api';
        $account->connection_type = $data['connection_type'] ?? 'meta_cloud_api';
        $account->is_active = $request->boolean('is_active', true);

        if (filled($data['access_token'] ?? null)) {
            $account->encrypted_access_token = WhatsappAccount::encryptAccessToken($data['access_token']);
        }

        if (filled($data['app_secret'] ?? null)) {
            $account->encrypted_app_secret = WhatsappAccount::encryptAppSecret($data['app_secret']);
        }

        $account->save();

        return response()->json(['success' => true, 'whatsappAccount' => $account]);
    }

    public function edit(WhatsappAccount $whatsappAccount)
    {
        return response()->json([
            'id'                            => $whatsappAccount->id,
            'name'                          => $whatsappAccount->name,
            'phone_number'                  => $whatsappAccount->phone_number,
            'phone_number_id'               => $whatsappAccount->phone_number_id,
            'whatsapp_business_account_id'  => $whatsappAccount->whatsapp_business_account_id,
            'webhook_verify_token'          => $whatsappAccount->webhook_verify_token,
            'is_active'                     => $whatsappAccount->is_active,
            'webhook_url'                   => route('whatsapp.webhook.receive'),
        ]);
    }

    public function update(Request $request, WhatsappAccount $whatsappAccount)
    {
        $data = $this->validateAccount($request);

        $whatsappAccount->name = $data['name'];
        $whatsappAccount->phone_number = $data['phone_number'];
        $whatsappAccount->phone_number_id = $data['phone_number_id'] ?? null;
        $whatsappAccount->whatsapp_business_account_id = $data['whatsapp_business_account_id'] ?? null;
        $whatsappAccount->webhook_verify_token = $data['webhook_verify_token'] ?? null;
        $whatsappAccount->is_active = $request->boolean('is_active', true);

        if (filled($data['access_token'] ?? null)) {
            $whatsappAccount->encrypted_access_token = WhatsappAccount::encryptAccessToken($data['access_token']);
        }

        if (filled($data['app_secret'] ?? null)) {
            $whatsappAccount->encrypted_app_secret = WhatsappAccount::encryptAppSecret($data['app_secret']);
        }

        $whatsappAccount->save();

        return response()->json(['success' => true, 'whatsappAccount' => $whatsappAccount]);
    }

    public function destroy(WhatsappAccount $whatsappAccount)
    {
        $whatsappAccount->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Polling endpoint del modal "Escanea el código QR" (Part D, conexión
     * baileys_qr). Si la cuenta todavía no tiene sesión activa (nunca se
     * llamó o quedó "disconnected", incluyendo el caso de "Reconectar"),
     * el primer poll dispara startSession() -- que persiste
     * session_id/session_status en la cuenta como side effect y devuelve el
     * primer QR. Los polls siguientes, ya con sesión en curso, solo
     * consultan el estado (sessionStatus() puede devolver un QR renovado
     * mientras siga 'qr_pending', ya que los códigos de Baileys expiran).
     */
    public function qrStatus(WhatsappAccount $whatsappAccount)
    {
        $service = app(\App\Services\WhatsappBaileysService::class);

        if (blank($whatsappAccount->session_status) || $whatsappAccount->session_status === 'disconnected') {
            $result = $service->startSession($whatsappAccount);
        } else {
            $result = $service->sessionStatus($whatsappAccount);
        }

        return response()->json($result);
    }
}
