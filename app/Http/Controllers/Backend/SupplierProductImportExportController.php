<?php

namespace App\Http\Controllers\Backend;

use App\Exports\SupplierProductsExport;
use App\Exports\SupplierProductsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SupplierProductsImport;
use App\Models\Products;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SupplierProductImportExportController extends Controller
{
    public function downloadTemplate()
    {
        if (!Supplier::where('status', 'active')->exists() || !Products::where('is_active', true)->exists()) {
            return redirect()->route('admin.suppliers.index')
                ->with('error', 'Registra al menos un proveedor y un producto activos antes de descargar la plantilla.');
        }

        return Excel::download(new SupplierProductsTemplateExport(), 'plantilla-proveedores-productos.xlsx');
    }

    public function export()
    {
        return Excel::download(new SupplierProductsExport(), 'proveedores-productos-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Selecciona un archivo antes de subirlo.',
            'file.file'     => 'El archivo recibido no es válido.',
            'file.mimes'    => 'El archivo debe ser de tipo .xlsx, .xls o .csv.',
            'file.max'      => 'El archivo no debe pesar más de 5 MB.',
        ]);

        set_time_limit(300);

        try {
            $import = new SupplierProductsImport();
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
