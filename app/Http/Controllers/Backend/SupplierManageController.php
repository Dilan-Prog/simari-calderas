<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierManageController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::get([
            'id', 'contact_name', 'company_name', 'email',
            'phone', 'rfc', 'status', 'rating_quality', 'rating_compliance', 'payment_terms'
        ]);

        return view('admin.supplier.index', compact('suppliers'));
    }

    public function show(string $id)
    {
        $supplier = Supplier::with('products')->findOrFail($id);
        return response()->json($supplier);
    }

    public function information(string $id){
        $supplier = Supplier::with(['products'=>function($query){
            $query->select('products.id', 'products.name', 'products.sku')
            ->withPivot('cost', 'lead_time_days', 'is_primary');
        }
        ])->findOrFail($id);
        return view('admin.supplier.partials._modal_show', compact('supplier'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // FIX BE-12: company_name/website used to accept any character;
            // contact_name accepted digits/symbols; phone allowed up to 30.
            // Regexes mirror the character sets the JS input filters in
            // _scripts.blade.php already enforce, so a direct API call can't
            // bypass what the UI blocks.
            'company_name'      => ['required', 'string', 'max:150', 'regex:/^[\p{L}\p{N}\s.\-_\/,:]+$/u'],
            'contact_name'      => ['nullable', 'string', 'max:150', 'regex:/^[\p{L}\s]+$/u'],
            'phone'             => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:150|unique:suppliers,email',
            'address'           => 'nullable|string',
            'rfc'               => 'nullable|string|max:20',
            'website'           => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s.\-_\/,:]+$/u'],
            'status'            => 'required|in:active,inactive,suspended',
            'payment_terms'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'rating_quality'    => 'nullable|integer|min:0|max:5',
            'rating_compliance' => 'nullable|integer|min:0|max:5',
        ], [
            'company_name.regex' => 'El nombre de la empresa no puede contener caracteres especiales.',
            'contact_name.regex' => 'El contacto principal solo puede contener letras y espacios.',
            'website.regex'      => 'El sitio web no puede contener caracteres especiales.',
        ]);

        $supplier = new Supplier;
        $supplier->company_name      = $request->company_name;
        $supplier->contact_name      = $request->contact_name;
        $supplier->phone             = $request->phone;
        $supplier->email             = $request->email;
        $supplier->address           = $request->address;
        $supplier->rfc               = $request->rfc;
        $supplier->website           = $request->website;
        $supplier->status            = $request->status;
        $supplier->payment_terms     = $request->payment_terms;
        $supplier->notes             = $request->notes;
        $supplier->rating_quality    = $request->rating_quality    ?? 0;
        $supplier->rating_compliance = $request->rating_compliance ?? 0;
        $supplier->save();

        // FIX: this used to redirect() like a classic form POST, but the
        // modal in _scripts.blade.php submits via fetch() and always calls
        // res.json() on the response. fetch() silently follows the 302 to
        // the HTML index page, so res.ok was true but res.json() threw a
        // SyntaxError parsing "<!DOCTYPE ..." — the supplier WAS created,
        // but the user only ever saw a misleading "Error de conexión" toast,
        // the modal never closed, and the list never refreshed.
        return response()->json([
            'success'  => true,
            'supplier' => $supplier,
            'message'  => 'Proveedor creado correctamente.',
        ], 201);
    }

    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'company_name'      => ['required', 'string', 'max:150', 'regex:/^[\p{L}\p{N}\s.\-_\/,:]+$/u'],
            'contact_name'      => ['nullable', 'string', 'max:150', 'regex:/^[\p{L}\s]+$/u'],
            'phone'             => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:150|unique:suppliers,email,' . $id,
            'address'           => 'nullable|string',
            'rfc'               => 'nullable|string|max:20',
            'website'           => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s.\-_\/,:]+$/u'],
            'status'            => 'required|in:active,inactive,suspended',
            'payment_terms'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'rating_quality'    => 'nullable|integer|min:0|max:5',
            'rating_compliance' => 'nullable|integer|min:0|max:5',
        ], [
            'company_name.regex' => 'El nombre de la empresa no puede contener caracteres especiales.',
            'contact_name.regex' => 'El contacto principal solo puede contener letras y espacios.',
            'website.regex'      => 'El sitio web no puede contener caracteres especiales.',
        ]);

        $supplier->company_name      = $request->company_name;
        $supplier->contact_name      = $request->contact_name;
        $supplier->phone             = $request->phone;
        $supplier->email             = $request->email;
        $supplier->address           = $request->address;
        $supplier->rfc               = $request->rfc;
        $supplier->website           = $request->website;
        $supplier->status            = $request->status;
        $supplier->payment_terms     = $request->payment_terms;
        $supplier->notes             = $request->notes;
        $supplier->rating_quality    = $request->rating_quality    ?? 0;
        $supplier->rating_compliance = $request->rating_compliance ?? 0;
        $supplier->save();

        return response()->json([
            'success'  => true,
            'supplier' => $supplier,
        ]);
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->products()->detach();
        $supplier->delete();

        // FIX: same redirect-vs-JSON mismatch as store() — the delete modal's
        // fetch() expects JSON and calls res.json() unconditionally.
        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }
}
