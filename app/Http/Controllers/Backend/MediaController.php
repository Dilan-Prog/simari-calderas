<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Support\UploadPath;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;

/**
 * Shared image picker used by any admin form with a single "URL de Imagen"
 * field (Home Sections, Collections, Categorías, slides, etc.) — lets the
 * user browse images already uploaded to the product catalog, upload a new
 * file, or paste an external URL, and always gets back a local stored URL.
 * Not gated behind a specific resource permission since it's read-only
 * browsing plus a generic upload utility, used across many different
 * permission-gated sections.
 */
class MediaController extends Controller
{
    use ImageUploadTrait;

    public function library(Request $request)
    {
        $query = ProductImage::with('product:id,name,sku')
            ->whereHas('product')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->whereHas('product', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $images = $query->paginate(24, ['*'], 'page', (int) $request->input('page', 1));

        return response()->json([
            'data' => $images->getCollection()->map(fn ($img) => [
                'id'           => $img->id,
                'url'          => $img->url,
                'product_name' => $img->product->name,
                'product_sku'  => $img->product->sku,
            ]),
            'has_more'  => $images->hasMorePages(),
            'next_page' => $images->currentPage() + 1,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'nullable|image|max:8192',
            'url'  => 'nullable|url',
        ]);

        $path = null;

        if ($request->hasFile('file')) {
            $paths = $this->uploadImages([$request->file('file')], 'uploads');
            $path = $paths[0] ?? null;
        } elseif ($request->filled('url')) {
            $path = $this->downloadImageFromUrl($request->input('url'), 'uploads');
        }

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la imagen. Verifica el archivo o la URL.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'url'     => UploadPath::url($path),
        ]);
    }
}
