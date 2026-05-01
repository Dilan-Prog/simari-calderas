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

    public function store(Request $request)
    {
        $request->validate([
            'company_name'      => 'required|string|max:150',
            'contact_name'      => 'nullable|string|max:150',
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:150|unique:suppliers,email',
            'address'           => 'nullable|string',
            'rfc'               => 'nullable|string|max:20',
            'website'           => 'nullable|string|max:255',
            'status'            => 'required|in:active,inactive,suspended',
            'payment_terms'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'rating_quality'    => 'nullable|integer|min:0|max:5',
            'rating_compliance' => 'nullable|integer|min:0|max:5',
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

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor creado correctamente.');
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
            'company_name'      => 'required|string|max:150',
            'contact_name'      => 'nullable|string|max:150',
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:150|unique:suppliers,email,' . $id,
            'address'           => 'nullable|string',
            'rfc'               => 'nullable|string|max:20',
            'website'           => 'nullable|string|max:255',
            'status'            => 'required|in:active,inactive,suspended',
            'payment_terms'     => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'rating_quality'    => 'nullable|integer|min:0|max:5',
            'rating_compliance' => 'nullable|integer|min:0|max:5',
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

        return redirect()->route('admin.suppliers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
