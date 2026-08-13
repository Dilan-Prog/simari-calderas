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
    public function index()
    {
        $whatsappAccounts = WhatsappAccount::latest()->get();

        return view('admin.whatsapp-accounts.index', compact('whatsappAccounts'));
    }

    private function validateAccount(Request $request): array
    {
        return $request->validate([
            'name'                          => 'required|string|max:100',
            'phone_number'                  => 'required|string|max:30',
            'phone_number_id'               => 'nullable|string|max:100',
            'whatsapp_business_account_id'  => 'nullable|string|max:100',
            'webhook_verify_token'          => 'nullable|string|max:100',
            'access_token'                  => 'nullable|string',
            'is_active'                     => 'nullable|boolean',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAccount($request);

        $account = new WhatsappAccount();
        $account->name = $data['name'];
        $account->phone_number = $data['phone_number'];
        $account->phone_number_id = $data['phone_number_id'] ?? null;
        $account->whatsapp_business_account_id = $data['whatsapp_business_account_id'] ?? null;
        $account->webhook_verify_token = $data['webhook_verify_token'] ?? null;
        $account->provider = 'meta_cloud_api';
        $account->is_active = $request->boolean('is_active', true);

        if (filled($data['access_token'] ?? null)) {
            $account->encrypted_access_token = WhatsappAccount::encryptAccessToken($data['access_token']);
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

        $whatsappAccount->save();

        return response()->json(['success' => true, 'whatsappAccount' => $whatsappAccount]);
    }

    public function destroy(WhatsappAccount $whatsappAccount)
    {
        $whatsappAccount->delete();

        return response()->json(['success' => true]);
    }
}
