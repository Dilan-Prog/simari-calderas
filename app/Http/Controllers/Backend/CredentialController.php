<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\Request;

/**
 * CRUD del catálogo "Credenciales" (admin). Mismo patrón modal-based con
 * respuestas JSON usado por PaymentMethodController — sin páginas propias
 * de create/edit, todo vía index + modal.
 *
 * El secreto (`payload`) nunca sale del servidor: el modelo lo oculta con
 * $hidden y aquí nunca se incluye en las respuestas de edit/index.
 */
class CredentialController extends Controller
{
    public function index()
    {
        $credentials = Credential::latest()->get();

        return view('admin.credentials.index', compact('credentials'));
    }

    private function validateCredential(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:100',
            'payload' => 'nullable|string',
        ]);

        if (filled($data['payload'] ?? null) && json_decode($data['payload']) === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payload' => 'El payload debe ser un JSON válido.',
            ]);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validateCredential($request);

        $credential = new Credential();
        $credential->name = $data['name'];
        $credential->type = $data['type'];
        $credential->encrypted_payload = Credential::storePayload(
            json_decode($data['payload'] ?? '', true) ?? []
        );
        $credential->created_by = $request->user()?->id;
        $credential->save();

        return response()->json(['success' => true]);
    }

    public function edit(Credential $credential)
    {
        return response()->json([
            'id' => $credential->id,
            'name' => $credential->name,
            'type' => $credential->type,
        ]);
    }

    public function update(Request $request, Credential $credential)
    {
        $data = $this->validateCredential($request);

        $credential->name = $data['name'];
        $credential->type = $data['type'];

        if (filled($data['payload'] ?? null)) {
            $credential->encrypted_payload = Credential::storePayload(
                json_decode($data['payload'], true) ?? []
            );
        }

        $credential->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Credential $credential)
    {
        $credential->delete();

        return response()->json(['success' => true]);
    }
}
