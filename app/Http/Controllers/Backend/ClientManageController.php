<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class ClientManageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::with('customer_addresses:city,state,customer_id,id')
            ->get(['id', 'first_name', 'last_name', 'company', 'email', 'phone', 'rfc', 'status', 'source']);

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
            'full_name' => 'required|string|max:200',
            'company' => 'nullable|string|max:150',
            'email' => 'required|email|max:150|unique:customers,email',
            'phone' => 'nullable|string|max:30',
            'rfc' => 'nullable|string|max:20',
            'document_type' => 'nullable|string|max:30',
            'document_numer' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'source' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer = new Customer;
        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->company = $request->company;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->password_hash = 'OdioAlosNegros';
        $customer->rfc = $request->rfc;
        $customer->notes = $request->notes;
        $customer->document_type = $request->document_type;
        $customer->document_numer = $request->document_numer ?? '';
        $customer->birth_date = $request->birth_date;
        $customer->source = $request->source;
        $customer->status = $request->status;
        $customer->save();

        if ($request->filled('address_line1')) {
            $customer->customer_addresses()->create([
                'label' => 'fiscal',
                'recipient_name' => $firstName.' '.$lastName,
                'phone' => $request->phone,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'México',
                'reference' => $request->reference,
                'is_default' => true,
            ]);
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

            // Normalizar: un solo espacio, sin saltos de línea
            $text = preg_replace('/\s+/', ' ', $rawText);

            // RFC
            $rfc = '';
            if (preg_match('/RFC:\s*([A-Z]{3,4}\d{6}[A-Z0-9]{3})/i', $text, $m))
                $rfc = trim($m[1]);

            // CURP
            $curp = '';
            if (preg_match('/CURP:\s*([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)/i', $text, $m))
                $curp = trim($m[1]);

            // Nombre(s) — labels concatenados sin espacios
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

            // Empresa — Nombre Comercial, fallback al nombre completo
            $company = '';
            if (preg_match('/NombreComercial:\s*(.+?)(?=Datosdel|Datos del)/i', $text, $m))
                $company = trim($m[1]);
            if (empty($company)) $company = $fullName;

            // Código Postal
            $postalCode = '';
            if (preg_match('/C[oó]digoPostal:(\d{5})/i', $text, $m))
                $postalCode = trim($m[1]);

            // Tipo de Vialidad
            $tipoVialidad = '';
            if (preg_match('/TipodeVialidad:\s*(.+?)(?=NombredeVialidad:)/i', $text, $m))
                $tipoVialidad = trim($m[1]);

            // Nombre de Vialidad
            $nombreVialidad = '';
            if (preg_match('/NombredeVialidad:\s*(.+?)(?=N[uú]meroExterior:)/i', $text, $m))
                $nombreVialidad = trim($m[1]);

            // Número Exterior
            $numExterior = '';
            if (preg_match('/N[uú]meroExterior:\s*(\S+?)(?=N[uú]meroInterior:)/i', $text, $m))
                $numExterior = trim($m[1]);

            // Número Interior
            $numInterior = '';
            if (preg_match('/N[uú]meroInterior:\s*(.+?)(?=NombredelaColonia:)/i', $text, $m))
                $numInterior = trim($m[1]);

            // Colonia
            $colonia = '';
            if (preg_match('/NombredelaColonia:\s*(.+?)(?=NombredelaLocalidad:)/i', $text, $m))
                $colonia = trim($m[1]);

            // Ciudad (Localidad)
            $city = '';
            if (preg_match('/NombredelaLocalidad:\s*(.+?)(?=NombredelMunicipio)/i', $text, $m))
                $city = trim($m[1]);

            // Estado (Entidad Federativa)
            $state = '';
            if (preg_match('/NombredelaEntidadFederativa:\s*(.+?)(?=EntreCalle:)/i', $text, $m))
                $state = trim($m[1]);

            // Construir dirección
            $parts = array_filter([
                $tipoVialidad,
                $nombreVialidad,
                $numExterior ? "#$numExterior" : '',
                $numInterior ? "Int.$numInterior" : '',
                $colonia     ? "Col.$colonia"     : '',
            ]);
            $addressLine1 = implode(' ', $parts);

            // Fecha de nacimiento desde CURP (posiciones 4-9: AAMMDD)
            $birthDate = '';
            if (!empty($curp) && strlen($curp) >= 10) {
                $yy       = substr($curp, 4, 2);
                $mo       = substr($curp, 6, 2);
                $dd       = substr($curp, 8, 2);
                $fullYear = ((int) $yy > (int) date('y')) ? "19$yy" : "20$yy";
                $birthDate = "$fullYear-$mo-$dd";
            }

            return response()->json([
                'rfc'            => $rfc,
                'curp'           => $curp,
                'full_name'      => $fullName,
                'company'        => $company,
                'document_type'  => 'curp',
                'document_numer' => $curp,
                'address_line1'  => $addressLine1,
                'city'           => $city,
                'state'          => $state,
                'postal_code'    => $postalCode,
                'country'        => 'México',
                'birth_date'     => $birthDate,
                'status'         => 'active',
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
            $query->select('id', 'customer_id', 'city', 'state', 'address_line1',
                'address_line2', 'postal_code', 'country', 'reference');
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
            'full_name' => 'required|string|max:200',
            'company' => 'nullable|string|max:150',
            'email' => 'required|email|max:150|unique:customers,email,'.$id,
            'phone' => 'nullable|string|max:30',
            'rfc' => 'nullable|string|max:20',
            'document_type' => 'nullable|string|max:30',
            'document_numer' => 'nullable|string|max:30',
            'birth_date' => 'nullable|date',
            'source' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $customer->first_name = $firstName;
        $customer->last_name = $lastName;
        $customer->company = $request->company;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->rfc = $request->rfc;
        $customer->document_type = $request->document_type;
        $customer->document_numer = $request->document_numer ?? '';
        $customer->birth_date = $request->birth_date;
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        abort_if($customer->id === auth()->id(), 403, 'No puedes eliminarte a ti mismo.');
        $customer->customer_addresses()->delete();
        $customer->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
