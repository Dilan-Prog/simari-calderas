<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\GalleryImage;
use App\Models\HomeSectionSlide;
use App\Models\ProductImage;
use App\Models\Products;
use App\Models\ServicePage;
use App\Models\ServiceReportImage;
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

        // Cada fuente normalizada a las mismas columnas (uid, image_path,
        // label, sublabel, created_at) y unida en una sola query paginable
        // — así el contrato {data, has_more, next_page} que consume
        // image-picker.js no cambia. uid usa el esquema "sourcetype:id"
        // (mismo vocabulario de sourceType que ImageReferenceService::listAll(),
        // p. ej. 'brand', 'category', 'product_cover') en vez de los prefijos
        // de un solo carácter ('g'/'p') de antes — con solo 2 fuentes esas
        // colisionaban poco, pero con 9 "s" de "service_page_cover" y "service_report_image"
        // ya no cabían en un solo carácter distinguible.
        $galleryQ = DB::table('gallery_images')
            ->selectRaw("CONCAT('gallery_image:', id) as uid, path as image_path, COALESCE(NULLIF(original_name, ''), 'Galería') as label, 'Galería' as sublabel, created_at")
            ->whereNotNull('path')->where('path', '!=', '')
            ->where('path', 'not like', 'http%');

        $productQ = DB::table('product_images')
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->selectRaw("CONCAT('product_image:', product_images.id) as uid, product_images.image_url as image_path, products.name as label, COALESCE(products.sku, '') as sublabel, product_images.created_at")
            ->whereNotNull('product_images.image_url')->where('product_images.image_url', '!=', '')
            ->where('product_images.image_url', 'not like', 'http%');

        $brandQ = DB::table('brands')
            ->selectRaw("CONCAT('brand:', id) as uid, logo_url as image_path, name as label, 'Marca' as sublabel, created_at")
            ->whereNotNull('logo_url')->where('logo_url', '!=', '')
            ->where('logo_url', 'not like', 'http%');

        // Tabla real es product_categories (el modelo Category la remapea vía
        // $table), no "categories" — verificado en Category.php antes de asumir.
        $categoryQ = DB::table('product_categories')
            ->selectRaw("CONCAT('category:', id) as uid, image_url as image_path, name as label, 'Categoría' as sublabel, created_at")
            ->whereNotNull('image_url')->where('image_url', '!=', '')
            ->where('image_url', 'not like', 'http%');

        $collectionQ = DB::table('collections')
            ->selectRaw("CONCAT('collection:', id) as uid, image_url as image_path, name as label, 'Colección' as sublabel, created_at")
            ->whereNotNull('image_url')->where('image_url', '!=', '')
            ->where('image_url', 'not like', 'http%');

        $productCoverQ = DB::table('products')
            ->selectRaw("CONCAT('product_cover:', id) as uid, cover_image_url as image_path, name as label, COALESCE(sku, '') as sublabel, created_at")
            ->whereNotNull('cover_image_url')->where('cover_image_url', '!=', '')
            ->where('cover_image_url', 'not like', 'http%');

        $servicePageCoverQ = DB::table('service_pages')
            ->selectRaw("CONCAT('service_page_cover:', id) as uid, cover_image_url as image_path, name as label, 'Página de servicio' as sublabel, created_at")
            ->whereNotNull('cover_image_url')->where('cover_image_url', '!=', '')
            ->where('cover_image_url', 'not like', 'http%');

        $slideQ = DB::table('home_section_slides')
            ->selectRaw("CONCAT('home_section_slide:', id) as uid, image_url as image_path, COALESCE(NULLIF(title, ''), 'Slide') as label, 'Slider principal' as sublabel, created_at")
            ->whereNotNull('image_url')->where('image_url', '!=', '')
            ->where('image_url', 'not like', 'http%');

        // service_report_images.path no es una URL completa (a diferencia de
        // las demás fuentes) — se resuelve vía UploadPath::url() más abajo,
        // igual que hacen ServiceReportImage::getUrlAttribute() y
        // ImageReferenceService. El label sale de un join a service_reports
        // (report_number, ver ServiceReport.php) porque la fila de imagen en
        // sí no tiene ningún campo identificable para el usuario.
        $reportImageQ = DB::table('service_report_images')
            ->join('service_reports', 'service_reports.id', '=', 'service_report_images.service_report_id')
            ->selectRaw("CONCAT('service_report_image:', service_report_images.id) as uid, service_report_images.path as image_path, CONCAT('Reporte #', service_reports.report_number) as label, 'Reporte de servicio' as sublabel, service_report_images.created_at")
            ->whereNotNull('service_report_images.path')->where('service_report_images.path', '!=', '')
            ->where('service_report_images.path', 'not like', 'http%');

        if ($term !== '') {
            $galleryQ->where('original_name', 'like', "%{$term}%");
            $productQ->where(function ($q) use ($term) {
                $q->where('products.name', 'like', "%{$term}%")
                    ->orWhere('products.sku', 'like', "%{$term}%");
            });
            $brandQ->where('name', 'like', "%{$term}%");
            $categoryQ->where('name', 'like', "%{$term}%");
            $collectionQ->where('name', 'like', "%{$term}%");
            $productCoverQ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
            $servicePageCoverQ->where('name', 'like', "%{$term}%");
            $slideQ->where('title', 'like', "%{$term}%");
            $reportImageQ->where(function ($q) use ($term) {
                $q->where('service_reports.report_number', 'like', "%{$term}%")
                    ->orWhere('service_reports.customer_name', 'like', "%{$term}%");
            });
        }

        $unionQ = $galleryQ
            ->unionAll($productQ)
            ->unionAll($brandQ)
            ->unionAll($categoryQ)
            ->unionAll($collectionQ)
            ->unionAll($productCoverQ)
            ->unionAll($servicePageCoverQ)
            ->unionAll($slideQ)
            ->unionAll($reportImageQ);

        // Agrupado por image_path: la misma imagen puede vivir en varias
        // fuentes a la vez (p. ej. subida a Galería y reutilizada como
        // cover de producto) — antes se listaba una tarjeta por cada fila,
        // pareciendo un archivo duplicado cuando en realidad es 1 solo
        // compartido. used_count = # de referencias (de cualquier fuente)
        // que apuntan a esa ruta.
        $grouped = DB::query()
            ->fromSub($unionQ, 'media')
            ->select('image_path')
            ->selectRaw('MAX(uid) as representative_uid')
            ->selectRaw('MAX(created_at) as latest_created_at')
            ->selectRaw('COUNT(*) as used_count')
            ->groupBy('image_path')
            ->orderByDesc('latest_created_at')
            ->orderByDesc('representative_uid')
            ->paginate(24, ['*'], 'page', (int) $request->input('page', 1));

        $representatives = collect($grouped->items())->mapWithKeys(function ($row) {
            [$sourceType, $id] = explode(':', $row->representative_uid, 2);

            return [$row->representative_uid => $this->libraryItemLabel($sourceType, (int) $id)];
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

    /**
     * Label/sublabel legibles para la fila representativa de un grupo del
     * picker, según su sourceType (mismo vocabulario que
     * ImageReferenceService::listAll(), más 'service_report_image' que es
     * nuevo aquí). Vive separado de library() solo por legibilidad — sigue
     * siendo un simple switch, sin llamadas cruzadas ni estado compartido.
     */
    private function libraryItemLabel(string $sourceType, int $id): array
    {
        switch ($sourceType) {
            case 'gallery_image':
                $g = GalleryImage::find($id);
                return ['label' => $g?->original_name ?: 'Galería', 'sublabel' => 'Galería'];

            case 'product_image':
                $p = ProductImage::with('product:id,name,sku')->find($id);
                return [
                    'label'    => $p?->product?->name,
                    'sublabel' => $p?->product?->sku ? 'SKU: ' . $p->product->sku : '',
                ];

            case 'brand':
                $brand = Brand::find($id);
                return ['label' => $brand?->name, 'sublabel' => 'Marca'];

            case 'category':
                $category = Category::find($id);
                return ['label' => $category?->name, 'sublabel' => 'Categoría'];

            case 'collection':
                $collection = Collection::find($id);
                return ['label' => $collection?->name, 'sublabel' => 'Colección'];

            case 'product_cover':
                $product = Products::find($id);
                return [
                    'label'    => $product?->name,
                    'sublabel' => $product?->sku ? 'SKU: ' . $product->sku : '',
                ];

            case 'service_page_cover':
                $servicePage = ServicePage::find($id);
                return ['label' => $servicePage?->name, 'sublabel' => 'Página de servicio'];

            case 'home_section_slide':
                $slide = HomeSectionSlide::find($id);
                return ['label' => $slide?->title ?: 'Slide', 'sublabel' => 'Slider principal'];

            case 'service_report_image':
                $image = ServiceReportImage::with('report:id,report_number')->find($id);
                return [
                    'label'    => $image?->report ? 'Reporte #' . $image->report->report_number : 'Reporte de servicio',
                    'sublabel' => 'Reporte de servicio',
                ];

            default:
                return ['label' => null, 'sublabel' => ''];
        }
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
