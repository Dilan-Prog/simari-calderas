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
        $emailTemplates = EmailTemplate::with('creator')->latest()->paginate(15);

        return view('admin.email-templates.index', compact('emailTemplates'));
    }

    public function create()
    {
        return view('admin.email-templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:255',
            'html_body' => 'required|string',
            'type'      => 'required|string|max:100',
        ]);

        $data['created_by'] = auth()->id();

        $emailTemplate = EmailTemplate::create($data);

        return redirect()->route('admin.email-templates.index')
            ->with('success', "Plantilla \"{$emailTemplate->name}\" creada exitosamente.");
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', compact('emailTemplate'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:255',
            'html_body' => 'required|string',
            'type'      => 'required|string|max:100',
        ]);

        $emailTemplate->update($data);

        return redirect()->route('admin.email-templates.index')
            ->with('success', "Plantilla \"{$emailTemplate->name}\" actualizada exitosamente.");
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->route('admin.email-templates.index')
            ->with('success', "Plantilla \"{$emailTemplate->name}\" eliminada.");
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
}
