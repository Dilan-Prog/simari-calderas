<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => EmailTemplate::with('creator')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'subject'      => 'required|string|max:255',
            'html_body'    => 'required|string',
            'type'         => 'required|string|max:100',
            'blocks_json'  => 'nullable|array',
            'builder_mode' => 'nullable|in:code,blocks',
        ]);

        $data['created_by'] = auth()->id();

        $emailTemplate = EmailTemplate::create($data);

        return response()->json($emailTemplate, 201);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'subject'      => 'required|string|max:255',
            'html_body'    => 'required|string',
            'type'         => 'required|string|max:100',
            'blocks_json'  => 'nullable|array',
            'builder_mode' => 'nullable|in:code,blocks',
        ]);

        $emailTemplate->update($data);

        return response()->json($emailTemplate);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return response()->json(null, 204);
    }

    /**
     * Endpoint AJAX para el preview en vivo del template. El usuario admin
     * autenticado no es un Customer, así que se construye un Customer de
     * ejemplo en memoria (sin persistir) para alimentar el render().
     */
    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $sampleCustomer = new Customer([
            'first_name' => 'Juan',
            'last_name'  => 'Pérez',
            'email'      => 'juan.perez@ejemplo.com',
            'company'    => 'Empresa de Ejemplo S.A. de C.V.',
        ]);

        $rendered = app(EmailTemplateService::class)->render($emailTemplate, $sampleCustomer);

        return response()->json($rendered);
    }

    /**
     * Lista mínima {id,name} para poblar <select> en Campañas/Secuencias.
     */
    public function options()
    {
        return response()->json(EmailTemplate::orderBy('name')->get(['id', 'name']));
    }
}
