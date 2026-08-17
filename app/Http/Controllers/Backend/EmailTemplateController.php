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

    /**
     * Lista los logos reales disponibles en public/images/logo/ (y sus
     * subcarpetas de variante Blanco/Blanco-color/Negro/Negro-color) para
     * el selector de logo del bloque "Logo" del armador de plantillas.
     * Excluye favicon/icon-web (son íconos, no logos para email) y
     * desktop.ini. Solo lee del disco, sin tabla nueva -- el picker
     * necesita ver los archivos que el usuario ya subió directamente a esa
     * carpeta.
     */
    public function logos()
    {
        $base = public_path('images/logo');
        $variantFolders = ['', 'Blanco', 'Blanco-color', 'Negro', 'Negro-color'];
        $extensions = ['png', 'svg', 'jpg', 'jpeg'];

        $logos = [];

        foreach ($variantFolders as $folder) {
            $dir = $folder === '' ? $base : $base . DIRECTORY_SEPARATOR . $folder;

            if (!is_dir($dir)) {
                continue;
            }

            foreach (scandir($dir) as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                if (!in_array($ext, $extensions, true)) {
                    continue;
                }

                // rawurlencode por segmento -- varios archivos reales tienen
                // espacios en el nombre ("Recurso 1equiterm-logo-blanco.png"),
                // que deben ir codificados en la URL aunque el navegador
                // suela tolerar el espacio literal dentro de src="".
                $encodedSegments = array_map('rawurlencode', $folder === '' ? [$file] : [$folder, $file]);

                $logos[] = [
                    'url'   => asset('images/logo/' . implode('/', $encodedSegments)),
                    'label' => ($folder !== '' ? $folder . ' / ' : '') . $file,
                ];
            }
        }

        return response()->json(['data' => $logos]);
    }
}
