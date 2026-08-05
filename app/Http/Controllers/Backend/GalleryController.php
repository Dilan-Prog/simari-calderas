<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\GalleryImage;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\ImageDuplicateGroup;
use App\Models\ImageDuplicateScan;
use App\Models\ProductImage;
use App\Services\ImageReferenceService;
use App\Support\UploadPath;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    use ImageUploadTrait;

    protected array $tabs = ['galeria', 'productos', 'marcas', 'categorias', 'colecciones', 'banners', 'duplicados'];

    public function __construct(protected ImageReferenceService $referenceService)
    {
    }

    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), $this->tabs, true) ? $request->input('tab') : 'galeria';

        // La pestaña Galería trabaja directo con los modelos (subir/eliminar);
        // el resto son vistas de solo lectura normalizadas a {url, label,
        // sublabel} para que la vista use un único grid.
        $galleryImages = null;
        $items = collect();
        $paginator = null;
        $duplicateGroups = collect();
        $lastScan = null;

        switch ($tab) {
            case 'galeria':
                $galleryImages = GalleryImage::latest('id')->paginate(24)->withQueryString();
                break;

            case 'productos':
                // Agrupado por image_url: la misma foto puede estar en la
                // galería de varios productos (reutilización vía
                // "Biblioteca") — antes se listaba una tarjeta por cada uno,
                // pareciendo un archivo duplicado cuando en realidad es 1
                // solo compartido. used_count = # de productos que la usan.
                $paginator = ProductImage::whereHas('product')
                    ->select('image_url')
                    ->selectRaw('MAX(id) as representative_id')
                    ->selectRaw('COUNT(DISTINCT product_id) as used_count')
                    ->groupBy('image_url')
                    ->orderByDesc('representative_id')
                    ->paginate(24)
                    ->withQueryString();

                $representatives = ProductImage::with('product:id,name,sku')
                    ->whereIn('id', $paginator->getCollection()->pluck('representative_id'))
                    ->get()
                    ->keyBy('id');

                $items = $paginator->getCollection()->map(function ($row) use ($representatives) {
                    $rep = $representatives->get($row->representative_id);
                    return [
                        'url'        => $rep?->url,
                        'label'      => $rep?->product?->name,
                        'sublabel'   => $rep?->product?->sku ? 'SKU: ' . $rep->product->sku : '',
                        'used_count' => (int) $row->used_count,
                    ];
                });
                break;

            case 'marcas':
                $paginator = Brand::whereNotNull('logo_url')->where('logo_url', '!=', '')
                    ->orderBy('name')->paginate(24)->withQueryString();
                $items = $paginator->getCollection()->map(fn ($brand) => [
                    'url'      => $this->resolveUrl($brand->logo_url),
                    'label'    => $brand->name,
                    'sublabel' => 'Logo de marca',
                ]);
                break;

            case 'categorias':
                $paginator = Category::whereNotNull('image_url')->where('image_url', '!=', '')
                    ->orderBy('name')->paginate(24)->withQueryString();
                $items = $paginator->getCollection()->map(fn ($cat) => [
                    'url'      => $this->resolveUrl($cat->image_url),
                    'label'    => $cat->name,
                    'sublabel' => 'Categoría',
                ]);
                break;

            case 'colecciones':
                $paginator = Collection::whereNotNull('image_url')->where('image_url', '!=', '')
                    ->orderBy('name')->paginate(24)->withQueryString();
                $items = $paginator->getCollection()->map(fn ($col) => [
                    'url'      => $this->resolveUrl($col->image_url),
                    'label'    => $col->name,
                    'sublabel' => 'Colección',
                ]);
                break;

            case 'banners':
                // Volúmenes pequeños: slides del hero + banners guardados en
                // el config JSON de las secciones. Sin paginación de BD.
                $items = collect();

                foreach (HomeSectionSlide::orderByDesc('id')->get() as $slide) {
                    if ($slide->image_url) {
                        $items->push([
                            'url'      => $this->resolveUrl($slide->image_url),
                            'label'    => $slide->title ?: 'Slide',
                            'sublabel' => 'Slider principal',
                        ]);
                    }
                }

                $bannerSections = HomeSection::whereIn('type', ['banner', 'dual_banner', 'product_carousel_banner'])->get();
                foreach ($bannerSections as $section) {
                    $config = $section->config ?? [];
                    $label = $section->title ?: 'Sección';

                    if ($section->type === 'banner' && !empty($config['image_url'])) {
                        $items->push(['url' => $this->resolveUrl($config['image_url']), 'label' => $label, 'sublabel' => 'Banner']);
                    }
                    if ($section->type === 'dual_banner') {
                        foreach (['left' => 'Banner doble (izq.)', 'right' => 'Banner doble (der.)'] as $side => $sublabel) {
                            if (!empty($config[$side]['image_url'])) {
                                $items->push(['url' => $this->resolveUrl($config[$side]['image_url']), 'label' => $label, 'sublabel' => $sublabel]);
                            }
                        }
                    }
                    if ($section->type === 'product_carousel_banner' && !empty($config['banner_image_url'])) {
                        $items->push(['url' => $this->resolveUrl($config['banner_image_url']), 'label' => $label, 'sublabel' => 'Carrusel con banner']);
                    }
                }
                break;

            case 'duplicados':
                $lastScan = ImageDuplicateScan::latest('id')->first();

                // Un mismo mapa de referencias (una sola pasada por las 6
                // fuentes) para armar la etiqueta "usada en N lugares" de
                // cada imagen sin repetir la consulta por cada una.
                $refsByUrl = [];
                foreach ($this->referenceService->listAll() as $ref) {
                    $refsByUrl[$ref->imageUrl][] = $ref;
                }

                $groups = ImageDuplicateGroup::where('status', 'pending')
                    ->with('images')
                    ->latest('id')
                    ->get();

                $duplicateGroups = $groups->map(function (ImageDuplicateGroup $group) use ($refsByUrl) {
                    return [
                        'id' => $group->id,
                        'images' => $group->images->map(function ($img) use ($refsByUrl) {
                            $refs = $refsByUrl[$img->image_url] ?? [];
                            $first = $refs[0] ?? null;

                            return [
                                'image_url' => $img->image_url,
                                'url' => $this->resolveUrl($img->image_url),
                                'label' => $first?->label ?? $img->image_url,
                                'sublabel' => $first?->sublabel ?? '',
                                'used_in' => count($refs),
                            ];
                        })->values(),
                    ];
                })->values();
                break;
        }

        return view('admin.gallery.index', compact('tab', 'galleryImages', 'items', 'paginator', 'duplicateGroups', 'lastScan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files'   => ['nullable', 'array'],
            'files.*' => ['image', 'max:8192'],
            'url'     => ['nullable', 'url'],
        ], [
            'files.*.image' => 'Uno de los archivos no es una imagen válida.',
            'files.*.max'   => 'Cada imagen debe pesar máximo 8 MB.',
        ]);

        if (!$request->hasFile('files') && !$request->filled('url')) {
            return response()->json(['success' => false, 'message' => 'Selecciona al menos una imagen o una URL.'], 422);
        }

        $uploaded = 0;
        $failed = 0;

        // Uno a uno: el trait traga errores por archivo; iterando sabemos
        // cuál falló y conservamos su nombre original.
        foreach ($request->file('files', []) as $file) {
            $originalName = $file->getClientOriginalName();
            $paths = $this->uploadImages([$file], 'uploads');

            if (!empty($paths[0])) {
                GalleryImage::create([
                    'path'          => $paths[0],
                    'original_name' => $originalName,
                    'uploaded_by'   => auth()->id(),
                ]);
                $uploaded++;
            } else {
                $failed++;
            }
        }

        if ($request->filled('url')) {
            $path = $this->downloadImageFromUrl($request->input('url'), 'uploads');

            if ($path) {
                GalleryImage::create([
                    'path'          => $path,
                    'original_name' => basename(parse_url($request->input('url'), PHP_URL_PATH) ?: '') ?: null,
                    'uploaded_by'   => auth()->id(),
                ]);
                $uploaded++;
            } else {
                $failed++;
            }
        }

        if ($uploaded === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar ninguna imagen. Verifica los archivos o la URL.',
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'uploaded' => $uploaded,
            'failed'   => $failed,
            'message'  => $uploaded . ' imagen(es) subida(s)' . ($failed ? ", {$failed} fallida(s)" : '') . '.',
        ]);
    }

    public function destroy(string $id)
    {
        $image = GalleryImage::findOrFail($id);

        if (!str_starts_with($image->path, 'http')) {
            $this->deleteImage($image->path);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }

    protected function resolveUrl(string $path): string
    {
        return str_starts_with($path, 'http') ? $path : (UploadPath::url($path) ?? '');
    }
}
