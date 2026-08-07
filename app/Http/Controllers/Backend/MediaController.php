<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\ProductImage;
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
 * permission-gated sections — but still requires the requesting user to
 * have a role assigned (same minimum bar as CheckPermission), so a staff
 * account with zero modules granted still can't reach it.
 */
class MediaController extends Controller
{
    use ImageUploadTrait;

    private function ensureStaffRoleAssigned(): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->role, 403, 'No tienes un rol asignado. Contacta al administrador.');
    }

    public function library(Request $request)
    {
        $this->ensureStaffRoleAssigned();

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

        // Agrupado por image_path: la misma imagen puede vivir tanto en
        // gallery_images como reutilizada en varios product_images — antes
        // se listaba una tarjeta por cada fila, pareciendo un archivo
        // duplicado cuando en realidad es 1 solo compartido. used_count = #
        // de referencias (gallery + productos) que apuntan a esa ruta.
        $grouped = DB::query()
            ->fromSub($galleryQ->unionAll($productQ), 'media')
            ->select('image_path')
            ->selectRaw('MAX(uid) as representative_uid')
            ->selectRaw('MAX(created_at) as latest_created_at')
            ->selectRaw('COUNT(*) as used_count')
            ->groupBy('image_path')
            ->orderByDesc('latest_created_at')
            ->orderByDesc('representative_uid')
            ->paginate(24, ['*'], 'page', (int) $request->input('page', 1));

        $representatives = collect($grouped->items())->mapWithKeys(function ($row) {
            $prefix = substr($row->representative_uid, 0, 1);
            $id = (int) substr($row->representative_uid, 1);

            if ($prefix === 'g') {
                $g = GalleryImage::find($id);
                return [$row->representative_uid => [
                    'label'    => $g?->original_name ?: 'Galería',
                    'sublabel' => 'Galería',
                ]];
            }

            $p = ProductImage::with('product:id,name,sku')->find($id);
            return [$row->representative_uid => [
                'label'    => $p?->product?->name,
                'sublabel' => $p?->product?->sku ? 'SKU: ' . $p->product->sku : '',
            ]];
        });

        return response()->json([
            'data' => collect($grouped->items())->map(function ($row) use ($representatives) {
                $rep = $representatives->get($row->representative_uid, []);
                return [
                    'id'           => $row->representative_uid,
                    'url'          => str_starts_with($row->image_path, 'http') ? $row->image_path : UploadPath::url($row->image_path),
                    'product_name' => $rep['label'] ?? null,
                    'product_sku'  => $rep['sublabel'] ?? null,
                    'used_count'   => (int) $row->used_count,
                ];
            }),
            'has_more'  => $grouped->hasMorePages(),
            'next_page' => $grouped->currentPage() + 1,
        ]);
    }

    public function upload(Request $request)
    {
        $this->ensureStaffRoleAssigned();

        $request->validate([
            'file' => 'nullable|mimes:jpg,jpeg,png,gif,bmp,webp|max:8192',
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
