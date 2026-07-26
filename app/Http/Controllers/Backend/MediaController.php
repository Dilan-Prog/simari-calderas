<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Support\UploadPath;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared image picker used by any admin form with a single "URL de Imagen"
 * field (Home Sections, Collections, Categorías, slides, etc.) — lets the
 * user browse the combined media library (Galería uploads + product catalog
 * images), upload a new file, or paste an external URL, and always gets
 * back a local stored URL. Uploads are registered in gallery_images so they
 * stay browsable from the Galería module (no more orphan files).
 * Not gated behind a specific resource permission since it's read-only
 * browsing plus a generic upload utility, used across many different
 * permission-gated sections.
 */
class MediaController extends Controller
{
    use ImageUploadTrait;

    public function library(Request $request)
    {
        $term = trim((string) $request->input('search', ''));

        // Dos fuentes normalizadas a las mismas columnas y unidas en una sola
        // query paginable — así el contrato {data, has_more, next_page} que
        // consume image-picker.js no cambia.
        $galleryQ = DB::table('gallery_images')
            ->selectRaw("CONCAT('g', id) as uid, path as image_path, COALESCE(NULLIF(original_name, ''), 'Galería') as label, 'Galería' as sublabel, created_at");

        $productQ = DB::table('product_images')
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->selectRaw("CONCAT('p', product_images.id) as uid, product_images.image_url as image_path, products.name as label, COALESCE(products.sku, '') as sublabel, product_images.created_at");

        if ($term !== '') {
            $galleryQ->where('original_name', 'like', "%{$term}%");
            $productQ->where(function ($q) use ($term) {
                $q->where('products.name', 'like', "%{$term}%")
                    ->orWhere('products.sku', 'like', "%{$term}%");
            });
        }

        $images = DB::query()
            ->fromSub($galleryQ->unionAll($productQ), 'media')
            ->orderByDesc('created_at')
            ->orderByDesc('uid')
            ->paginate(24, ['*'], 'page', (int) $request->input('page', 1));

        return response()->json([
            'data' => collect($images->items())->map(fn ($img) => [
                'id'           => $img->uid,
                'url'          => str_starts_with($img->image_path, 'http') ? $img->image_path : UploadPath::url($img->image_path),
                'product_name' => $img->label,
                'product_sku'  => $img->sublabel,
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
        $originalName = null;

        if ($request->hasFile('file')) {
            $originalName = $request->file('file')->getClientOriginalName();
            $paths = $this->uploadImages([$request->file('file')], 'uploads');
            $path = $paths[0] ?? null;
        } elseif ($request->filled('url')) {
            $originalName = basename(parse_url($request->input('url'), PHP_URL_PATH) ?: '') ?: null;
            $path = $this->downloadImageFromUrl($request->input('url'), 'uploads');
        }

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar la imagen. Verifica el archivo o la URL.',
            ], 422);
        }

        // Registrar en la Galería para que la subida no quede huérfana.
        GalleryImage::create([
            'path'          => $path,
            'original_name' => $originalName,
            'uploaded_by'   => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'url'     => UploadPath::url($path),
        ]);
    }
}
