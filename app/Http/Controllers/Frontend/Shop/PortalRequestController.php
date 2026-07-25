<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerPortalRequest;
use App\Support\UploadPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PortalRequestController extends Controller
{
    /**
     * Autosave del wizard: guarda UNA respuesta en cuanto el cliente la
     * contesta (upsert sobre la única fila del cliente).
     */
    public function answer(Request $request)
    {
        $data = $request->validate([
            'field' => ['required', 'in:purchase_frequency,purchase_amount,reason'],
            'value' => ['required', 'string', 'max:500'],
        ]);

        $customer = Auth::guard('customer')->user();

        if ($customer->portal_access) {
            return response()->json(['success' => false, 'message' => 'Ya tienes acceso al portal.'], 422);
        }

        CustomerPortalRequest::updateOrCreate(
            ['customer_id' => $customer->id],
            [$data['field'] => $data['value']]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Paso final del wizard: datos fiscales + constancia. Marca la solicitud
     * como completada (completed_at) para que el admin la revise.
     */
    public function finish(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'responsible_name'   => ['required', 'string', 'max:150'],
            'rfc'                => ['required', 'string', 'max:20'],
            'tax_regime'         => ['required', 'string', 'max:150'],
            'contact_email'      => ['required', 'email', 'max:255'],
            'phone'              => ['required', 'string', 'max:30'],
            'fiscal_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ], [
            'fiscal_certificate.required' => 'Sube tu Constancia de Situación Fiscal (PDF o imagen).',
            'fiscal_certificate.mimes'    => 'La constancia debe ser PDF o imagen (JPG/PNG).',
            'fiscal_certificate.max'      => 'La constancia no debe pesar más de 8 MB.',
        ]);

        // Documento sensible: vive bajo UploadPath (fuera de public_html en
        // producción) en una carpeta que MediaServeController NO sirve
        // públicamente; solo el admin lo descarga vía ruta protegida.
        $file = $request->file('fiscal_certificate');
        $dir = UploadPath::full('portal-requests');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'constancia-' . $customer->id . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        CustomerPortalRequest::updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'responsible_name'        => $data['responsible_name'],
                'rfc'                     => strtoupper($data['rfc']),
                'tax_regime'              => $data['tax_regime'],
                'contact_email'           => strtolower($data['contact_email']),
                'phone'                   => $data['phone'],
                'fiscal_certificate_path' => 'portal-requests/' . $filename,
                'completed_at'            => now(),
            ]
        );

        return redirect()->route('shop.account')
            ->with('status', 'Gracias por la información, la estaremos analizando. Te avisaremos cuando tu acceso al portal esté activo.');
    }
}
