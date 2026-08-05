<?php

namespace App\Http\Controllers\Backend;

use App\Exports\SupplierProductsExport;
use App\Exports\SupplierProductsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SupplierProductsImport;
use App\Models\Products;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SupplierProductImportExportController extends Controller
{
    public function downloadTemplate(Request $request)
    {
        if (!Supplier::where('status', 'active')->exists() || !Products::where('is_active', true)->exists()) {
            return redirect()->route('admin.suppliers.index')
                ->with('error', 'Registra al menos un proveedor y un producto activos antes de descargar la plantilla.');
        }

        // Descarga "escopeada" desde la pestaña Productos de un proveedor
        // específico: los ejemplos de la hoja de datos usan su nombre real
        // en vez de 2 proveedores cualquiera, para que quede claro que la
        // columna Proveedor ya viene resuelta por contexto.
        $forcedSupplierName = null;
        if ($supplierId = $request->input('supplier_id')) {
            $forcedSupplierName = Supplier::find($supplierId)?->company_name;
        }

        if ($request->input('format') === 'pdf') {
            return Pdf::loadView('admin.supplier.pdf.template', [
                'rows' => (new \App\Exports\Sheets\SupplierProductsTemplateInstructionsSheet())->instructionRows(),
            ])->setPaper('a4', 'portrait')->download('plantilla-proveedores-productos.pdf');
        }

        return Excel::download(
            new SupplierProductsTemplateExport($forcedSupplierName),
            'plantilla-proveedores-productos.xlsx'
        );
    }

    public function export(Request $request)
    {
        if ($request->input('format') === 'pdf') {
            $links = SupplierProduct::with(['supplier:id,company_name', 'product:id,name,sku'])
                ->join('suppliers', 'suppliers.id', '=', 'suppliers_products.supplier_id')
                ->orderBy('suppliers.company_name')
                ->select('suppliers_products.*')
                ->get();

            return Pdf::loadView('admin.supplier.pdf.catalog', ['links' => $links])
                ->setPaper('a4', 'landscape')->download('proveedores-productos-' . now()->format('Y-m-d') . '.pdf');
        }

        return Excel::download(new SupplierProductsExport(), 'proveedores-productos-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ], [
            'file.required' => 'Selecciona un archivo antes de subirlo.',
            'file.file'     => 'El archivo recibido no es válido.',
            'file.mimes'    => 'El archivo debe ser de tipo .xlsx, .xls o .csv.',
            'file.max'      => 'El archivo no debe pesar más de 5 MB.',
        ]);

        set_time_limit(300);

        try {
            $import = new SupplierProductsImport($request->input('supplier_id') ? (int) $request->input('supplier_id') : null);
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Supplier-product import failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el archivo. Verifica que uses la plantilla y que las columnas no hayan sido modificadas.',
                'debug'   => $e->getMessage(),
            ], 422);
        }

        $failures = collect($import->failures())->map(fn ($f) => [
            'row' => $f->row(),
            'campo' => $f->attribute(),
            'errores' => $f->errors(),
        ])->values();

        return response()->json([
            'success' => true,
            'created' => $import->createdCount,
            'updated' => $import->updatedCount,
            'failures' => $failures,
        ]);
    }
}
