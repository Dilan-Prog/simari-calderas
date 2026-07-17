<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportExportController extends Controller
{
    public function downloadTemplate()
    {
        if (!Category::where('is_active', true)->exists() || !Brand::where('is_active', true)->exists()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Registra al menos una categoría y una marca activas antes de descargar la plantilla.');
        }

        return Excel::download(new ProductsTemplateExport(), 'plantilla-productos.xlsx');
    }

    public function export()
    {
        return Excel::download(new ProductsExport(), 'productos-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            // The project has no lang/es files installed, so unlabeled
            // rules silently fall back to Laravel's built-in English
            // messages — this one gets its own Spanish text explicitly so
            // a real file-validation failure doesn't look like a broken
            // page to a non-technical user.
            'file.required' => 'Selecciona un archivo antes de subirlo.',
            'file.file'     => 'El archivo recibido no es válido.',
            'file.mimes'    => 'El archivo debe ser de tipo .xlsx, .xls o .csv.',
            'file.max'      => 'El archivo no debe pesar más de 5 MB.',
        ]);

        // Bumped from 120s: rows with "Imagen URL" trigger a real HTTP
        // download per product after the batch insert (see
        // ProductsImport::afterImport()), which can add meaningful time on
        // a catalog with many images.
        set_time_limit(300);

        try {
            $import = new ProductsImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            // Logged so the real cause is visible in storage/logs/laravel.log
            // even in production, where nobody is watching a terminal —
            // the generic on-screen message alone wasn't enough to diagnose
            // a real failure ("solo dice que falló, no dice por qué").
            Log::error('Product import failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar el archivo. Verifica que uses la plantilla y que las columnas no hayan sido modificadas.',
                // Shown in the UI alongside the friendly message above so it
                // can be diagnosed immediately from the browser, without
                // needing server/log access (Hostinger shared hosting).
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
            'skipped_duplicates' => $import->skippedDuplicates,
            'failures' => $failures,
            'image_download_failures' => $import->imageDownloadFailures,
        ]);
    }
}
