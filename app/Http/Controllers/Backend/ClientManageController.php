<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser;

class ClientManageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::with('customer_addresses:city,state,customer_id,id')
            ->get(['id', 'first_name', 'last_name', 'company', 'email', 'phone', 'rfc', 'status', 'source', 'password_hash']);

        return view('admin.client.index', compact('customers'));
    }

    public function information(string $id)
    {
        $customer = Customer::with(['customer_addresses'])->findOrFail($id);
        return view('admin.client.partials.show', compact('customer'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nameParts = explode(' ', trim($request->full_name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $request->validate([
            // FIX QA-1: Added regex — letters and spaces only (matches the
            // real-time dynamicLettersOnly() filter already applied in JS).
            'full_name' => ['required', 'string', 'max:200', 'regex:/^[a-zA-ZÀ-ÿ\s]+$/'],
            // FIX QA-3: Added regex — alphanumeric + spaces only (matches the
            // real-time dynamicAlphanumeric() filter already applied in JS).
            'company' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
            'email' => 'required|email|max:150|unique:customers,email',
            'phone' => 'nullable|string|max:30',
            'rfc' => 'nullable|string|max:20',
            // FIX QA-5: Changed to 'required|in:...' — document_type is NOT NULL
            // in the DB but had no required rule, so a request bypassing the JS
            // guard (direct POST, missing field) passed validation and crashed
            // the INSERT with a silent 500 QueryException instead of a 422.
            'document_type' => 'required|in:ine,pasaporte,curp,cfdi',
            // FIX QA-6: Changed to 'required|in:...' — source is NOT NULL in the
            // DB but had no required rule, causing the same silent 500 pattern
            // as document_type (QA-5) when the field is missing/empty.
            'source' => 'required|in:web,whatsapp,admin,campaña,referido',
            'status' => 'required|in:active,inactive,suspended',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            // FIX QA-3: Added regex — alphanumeric + spaces only (matches the
            // real-time dynamicAlphanumeric() filter already applied in JS).
            'notes' => ['nullable', 'string', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
        ]);

        $customer = new Customer;
        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->company = $request->company;
        // FIX QA-4: Added strtolower() so the stored value is always lowercase
        // even if the request bypasses the JS real-time lowercasing (direct API
        // call). Applied after validation, so the unique:customers,email check
        // above still runs against the raw submitted value.
        $customer->email = strtolower($request->email);
        $customer->phone = $request->phone;
        $customer->rfc = $request->rfc;
        $customer->notes = $request->notes;
        $customer->document_type = $request->document_type;
        $customer->source = $request->source;
        $customer->status = $request->status;
        $customer->password_hash = 'TemporalPassword';
        $customer->save();

        if ($request->filled('address_line1')) {
            // FIX QA-12: this only ever created ONE 'fiscal' address, cramming
            // the "Dirección de Envío" form field into that same record's
            // address_line2 column — same_as_fiscal was never checked and no
            // 'envio' address was ever created. update() already does this
            // correctly (two separate address records); mirrored here so a
            // client created with a different shipping address doesn't lose
            // it, and doesn't get silently restructured the first time
            // someone opens Editar.
            $customer->customer_addresses()->create([
                'label' => 'fiscal',
                'recipient_name' => $firstName . ' ' . $lastName,
                'phone' => $request->phone,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line1,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'México',
                'reference' => $request->reference,
                'is_default' => true,
            ]);

            if (!$request->boolean('same_as_fiscal') && $request->filled('address_line2')) {
                $customer->customer_addresses()->create([
                    'label' => 'envio',
                    'recipient_name' => $firstName . ' ' . $lastName,
                    'phone' => $request->phone,
                    'address_line1' => $request->address_line2,
                    'address_line2' => $request->address_line2,
                    'city' => $request->city,
                    'state' => $request->state,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country ?? 'México',
                    'is_default' => false,
                ]);
            }
        }

        return redirect()->route('admin.clients.index')->with('success', 'Cliente creado correctamente.');
    }

    public function parseCfdi(Request $request): JsonResponse
    {
        $request->validate(['cfdi_pdf' => 'required|file|mimes:pdf|max:5120']);

        try {
            $parser  = new Parser();
            $pdf     = $parser->parseFile($request->file('cfdi_pdf')->getPathname());
            $rawText = '';
            foreach ($pdf->getPages() as $page) {
                $rawText .= $page->getText();
            }

            $text = preg_replace('/\s+/', ' ', $rawText);

            // RFC
            $rfc = '';
            if (preg_match('/RFC:\s*([A-Z]{3,4}\d{6}[A-Z0-9]{3})/i', $text, $m))
                $rfc = trim($m[1]);

            // CURP
            $curp = '';
            if (preg_match('/CURP:\s*([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)/i', $text, $m))
                $curp = trim($m[1]);

            // Names — concatenated labels without spaces
            $nombres = '';
            if (preg_match('/Nombre\(s\):\s*(.+?)(?=PrimerApellido:|Primer Apellido:)/i', $text, $m))
                $nombres = trim($m[1]);

            $apellido1 = '';
            if (preg_match('/PrimerApellido:\s*(.+?)(?=SegundoApellido:|Segundo Apellido:)/i', $text, $m))
                $apellido1 = trim($m[1]);

            $apellido2 = '';
            if (preg_match('/SegundoApellido:\s*(.+?)(?=Fechainicio|Fecha inicio)/i', $text, $m))
                $apellido2 = trim($m[1]);

            $fullName = trim("$nombres $apellido1 $apellido2");

            // Company — Commercial Name, fallback to full name
            $company = '';
            if (preg_match('/NombreComercial:\s*(.+?)(?=Datosdel|Datos del)/i', $text, $m))
                $company = trim($m[1]);
            if (empty($company)) $company = $fullName;

            // Postal Code
            $postalCode = '';
            if (preg_match('/C[oó]digoPostal:(\d{5})/i', $text, $m))
                $postalCode = trim($m[1]);

            // Street Type
            $tipoVialidad = '';
            if (preg_match('/TipodeVialidad:\s*(.+?)(?=NombredeVialidad:)/i', $text, $m))
                $tipoVialidad = trim($m[1]);

            // Street Name
            $nombreVialidad = '';
            if (preg_match('/NombredeVialidad:\s*(.+?)(?=N[uú]meroExterior:)/i', $text, $m))
                $nombreVialidad = trim($m[1]);

            // Exterior Number
            $numExterior = '';
            if (preg_match('/N[uú]meroExterior:\s*(\S+?)(?=N[uú]meroInterior:)/i', $text, $m))
                $numExterior = trim($m[1]);

            // Interior Number
            $numInterior = '';
            if (preg_match('/N[uú]meroInterior:\s*(.+?)(?=NombredelaColonia:)/i', $text, $m))
                $numInterior = trim($m[1]);

            // Neighborhood
            $colonia = '';
            if (preg_match('/NombredelaColonia:\s*(.+?)(?=NombredelaLocalidad:)/i', $text, $m))
                $colonia = trim($m[1]);

            // City (Locality)
            $city = '';
            if (preg_match('/NombredelaLocalidad:\s*(.+?)(?=NombredelMunicipio)/i', $text, $m))
                $city = trim($m[1]);

            // State (Federal Entity)
            $state = '';
            if (preg_match('/NombredelaEntidadFederativa:\s*(.+?)(?=EntreCalle:)/i', $text, $m))
                $state = trim($m[1]);

            // Build address
            $parts = array_filter([
                $tipoVialidad,
                $nombreVialidad,
                $numExterior ? "#$numExterior" : '',
                $numInterior ? "Int.$numInterior" : '',
                $colonia     ? "Col.$colonia"     : '',
            ]);
            $addressLine1 = implode(' ', $parts);

            // Birth Date from CURP (positions 4-9: AAMMDD)
            $birthDate = '';
            if (!empty($curp) && strlen($curp) >= 10) {
                $yy       = substr($curp, 4, 2);
                $mo       = substr($curp, 6, 2);
                $dd       = substr($curp, 8, 2);
                $fullYear = ((int) $yy > (int) date('y')) ? "19$yy" : "20$yy";
                $birthDate = "$fullYear-$mo-$dd";
            }

            return response()->json([
                'rfc'           => $rfc,
                'full_name'     => $fullName,
                'company'       => $company,
                'document_type' => 'curp',
                'address_line1' => $addressLine1,
                'city'          => $city,
                'state'         => $state,
                'postal_code'   => $postalCode,
                'country'       => 'México',
                'status'        => 'active',
            ]);
        } catch (\Exception) {
            return response()->json([
                'error' => 'No se pudo leer el PDF. Verifica que sea una Constancia de Situación Fiscal del SAT.',
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Customer::with(['customer_addresses' => function ($query) {
            $query->select(
                'id',
                'customer_id',
                'city',
                'state',
                'address_line1',
                'address_line2',
                'postal_code',
                'country',
                'reference'
            );
        }])->findOrFail($id);

        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $nameParts = explode(' ', trim($request->full_name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $request->validate([
            // FIX QA-1: Added regex — letters and spaces only (matches the
            // real-time dynamicLettersOnly() filter already applied in JS).
            'full_name' => ['required', 'string', 'max:200', 'regex:/^[a-zA-ZÀ-ÿ\s]+$/'],
            // FIX QA-3: Added regex — alphanumeric + spaces only (matches the
            // real-time dynamicAlphanumeric() filter already applied in JS).
            'company' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
            'email' => 'required|email|max:150|unique:customers,email,' . $id,
            'phone' => 'nullable|string|max:30',
            'rfc' => 'nullable|string|max:20',
            // FIX QA-5: document_type is NOT NULL in the DB; require it here too
            // so update() matches store() and a bypassed/missing value returns
            // a 422 instead of crashing the UPDATE with a QueryException.
            'document_type' => 'required|in:ine,pasaporte,curp,cfdi',
            // FIX QA-6: Changed to 'required|in:...' — same NOT NULL mismatch as
            // document_type, mirrored here so update() matches store().
            'source' => 'required|in:web,whatsapp,admin,campaña,referido',
            'status' => 'required|in:active,inactive,suspended',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            // FIX QA-3: Added regex — alphanumeric + spaces only (matches the
            // real-time dynamicAlphanumeric() filter already applied in JS).
            'notes' => ['nullable', 'string', 'regex:/^[a-zA-ZÀ-ÿ0-9\s]+$/'],
        ]);

        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->company = $request->company;
        // FIX QA-4: Added strtolower() so the stored value is always lowercase
        // even if the request bypasses the JS real-time lowercasing (direct API
        // call). Applied after validation — the unique rule is untouched.
        $customer->email = strtolower($request->email);
        $customer->phone = $request->phone;
        $customer->rfc = $request->rfc;
        $customer->document_type = $request->document_type;
        $customer->source = $request->source;
        $customer->notes = $request->notes;
        $customer->status = $request->status;
        $customer->save();

        // Delete all existing addresses and recreate
        $customer->customer_addresses()->delete();

        if ($request->filled('address_line1')) {
            $customer->customer_addresses()->create([
                'label'          => 'fiscal',
                'recipient_name' => $firstName . ' ' . $lastName,
                'phone'          => $request->phone,
                'address_line1'  => $request->address_line1,
                'address_line2'  => $request->address_line1,
                'city'           => $request->city,
                'state'          => $request->state,
                'postal_code'    => $request->postal_code,
                'country'        => $request->country ?? 'México',
                'reference'      => $request->reference,
                'is_default'     => true,
            ]);

            if (!$request->boolean('same_as_fiscal') && $request->filled('address_line2')) {
                $customer->customer_addresses()->create([
                    'label'          => 'envio',
                    'recipient_name' => $firstName . ' ' . $lastName,
                    'phone'          => $request->phone,
                    'address_line1'  => $request->address_line2,
                    'address_line2'  => $request->address_line2,
                    'city'           => $request->city,
                    'state'          => $request->state,
                    'postal_code'    => $request->postal_code,
                    'country'        => $request->country ?? 'México',
                    'is_default'     => false,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'customer' => $customer->load('customer_addresses'),
        ]);
    }

    public function grantAccess(Request $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $hadAccess = !empty($customer->password_hash);

        $customer->password_hash = Hash::make($request->password);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => $hadAccess
                ? 'Contraseña actualizada correctamente.'
                : 'Acceso al portal otorgado correctamente.',
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $customer->status = $request->status;
        $customer->save();

        $labels = ['active' => 'activado', 'inactive' => 'desactivado', 'suspended' => 'suspendido'];

        return response()->json([
            'success' => true,
            'status'  => $customer->status,
            'message' => 'Panel del cliente ' . $labels[$customer->status] . ' correctamente.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // FIX QA-11: withCount(['orders', 'quotes', 'serviceReports']) crashed
        // with a BadMethodCallException on every delete — none of those
        // relations existed on Customer. Replaced with the relations that
        // actually exist: 'services' (TechnicalService, this domain's real
        // "order" concept) and 'serviceReports'. Dropped 'quotes' — Quote has
        // no customer_id (guest-only fields), so it can't be counted per customer.
        $customer = Customer::withCount([
            'services',
            'serviceReports',
        ])->findOrFail($id);

        // Detectar dependencias
        $blocking = [];
        if (($customer->services_count        ?? 0) > 0) $blocking[] = $customer->services_count . ' orden(es) de servicio';
        if (($customer->service_reports_count ?? 0) > 0) $blocking[] = $customer->service_reports_count . ' reporte(s) de servicio';

        if (!empty($blocking)) {
            return response()->json([
                'success' => false,
                'message' => "El cliente {$customer->first_name} {$customer->last_name} no puede ser eliminado porque tiene " . implode(', ', $blocking) . ' asignados.',
            ], 422);
        }

        $customer->customer_addresses()->delete();
        $customer->delete();

        return response()->json(['success' => true]);
    }
}
