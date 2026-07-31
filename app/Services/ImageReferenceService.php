<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\ProductImage;
use App\Support\ImageReference;
use App\Support\UploadPath;
use Illuminate\Support\Arr;

/**
 * Uniformiza las 6 formas reales en las que el catálogo guarda una ruta de
 * imagen (fila de product_images, columnas simples en Brand/Category/
 * Collection, fila de HomeSectionSlide, o llaves dentro del JSON config de
 * HomeSection) para que el detector/consolidador de duplicados pueda leer,
 * comparar y reescribir cualquiera de ellas sin tocar sus controladores.
 */
class ImageReferenceService
{
    /**
     * @return ImageReference[]
     */
    public function listAll(): array
    {
        $refs = [];

        foreach (ProductImage::with('product:id,name,sku')->whereHas('product')->get() as $img) {
            if (!$img->image_url) {
                continue;
            }
            $refs[] = new ImageReference(
                'product_image',
                $img->id,
                'image_url',
                $img->image_url,
                $img->product->name,
                $img->product->sku ? 'SKU: ' . $img->product->sku : 'Producto'
            );
        }

        foreach (Brand::whereNotNull('logo_url')->where('logo_url', '!=', '')->get() as $brand) {
            $raw = $brand->getRawOriginal('logo_url');
            if (!$raw) {
                continue;
            }
            $refs[] = new ImageReference('brand', $brand->id, 'logo_url', $raw, $brand->name, 'Logo de marca');
        }

        foreach (Category::whereNotNull('image_url')->where('image_url', '!=', '')->get() as $cat) {
            $raw = $cat->getRawOriginal('image_url');
            if (!$raw) {
                continue;
            }
            $refs[] = new ImageReference('category', $cat->id, 'image_url', $raw, $cat->name, 'Categoría');
        }

        foreach (Collection::all() as $col) {
            if ($col->image_url) {
                $refs[] = new ImageReference('collection', $col->id, 'image_url', $col->image_url, $col->name, 'Colección');
            }
            if ($col->og_image_url) {
                $refs[] = new ImageReference('collection_og', $col->id, 'og_image_url', $col->og_image_url, $col->name, 'Colección (OG)');
            }
        }

        foreach (HomeSectionSlide::whereNotNull('image_url')->where('image_url', '!=', '')->get() as $slide) {
            $raw = $slide->getRawOriginal('image_url');
            if (!$raw || str_starts_with($raw, 'http')) {
                continue;
            }
            $refs[] = new ImageReference('home_section_slide', $slide->id, 'image_url', $raw, $slide->title ?: 'Slide', 'Slider principal');
        }

        foreach (HomeSection::whereIn('type', ['banner', 'dual_banner', 'product_carousel_banner'])->get() as $section) {
            $config = $section->config ?? [];
            $label = $section->title ?: 'Sección';

            if ($section->type === 'banner' && !empty($config['image_url'])) {
                $refs[] = new ImageReference('home_section', $section->id, 'image_url', $config['image_url'], $label, 'Banner');
            }

            if ($section->type === 'dual_banner') {
                foreach (['left' => 'Banner doble (izq.)', 'right' => 'Banner doble (der.)'] as $side => $sublabel) {
                    if (!empty($config[$side]['image_url'])) {
                        $refs[] = new ImageReference('home_section', $section->id, "{$side}.image_url", $config[$side]['image_url'], $label, $sublabel);
                    }
                }
            }

            if ($section->type === 'product_carousel_banner' && !empty($config['banner_image_url'])) {
                $refs[] = new ImageReference('home_section', $section->id, 'banner_image_url', $config['banner_image_url'], $label, 'Carrusel con banner');
            }
        }

        // Las URLs externas (http...) no se pueden hashear/borrar localmente;
        // ya se excluyen por tipo arriba donde aplica, este filtro final es
        // solo una red de seguridad.
        return array_values(array_filter(
            $refs,
            fn (ImageReference $r) => $r->imageUrl !== '' && !str_starts_with($r->imageUrl, 'http')
        ));
    }

    /**
     * @return string[]
     */
    public function distinctLocalImageUrls(): array
    {
        return collect($this->listAll())->pluck('imageUrl')->unique()->values()->all();
    }

    public function resolveDiskPath(string $imageUrl): ?string
    {
        if (str_starts_with($imageUrl, 'http')) {
            return null;
        }

        return UploadPath::full($imageUrl);
    }

    /**
     * @return ImageReference[]
     */
    public function findReferences(string $imageUrl): array
    {
        return array_values(array_filter(
            $this->listAll(),
            fn (ImageReference $r) => $r->imageUrl === $imageUrl
        ));
    }

    public function isReferencedElsewhere(string $imageUrl, ?ImageReference $excluding = null): bool
    {
        foreach ($this->findReferences($imageUrl) as $ref) {
            if ($excluding
                && $ref->sourceType === $excluding->sourceType
                && $ref->sourceId === $excluding->sourceId
                && $ref->fieldPath === $excluding->fieldPath
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function rewriteReference(ImageReference $ref, string $newImageUrl): void
    {
        match ($ref->sourceType) {
            'product_image' => ProductImage::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'brand' => Brand::where('id', $ref->sourceId)->update(['logo_url' => $newImageUrl]),
            'category' => Category::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'collection' => Collection::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'collection_og' => Collection::where('id', $ref->sourceId)->update(['og_image_url' => $newImageUrl]),
            'home_section_slide' => HomeSectionSlide::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'home_section' => $this->rewriteHomeSectionField($ref->sourceId, $ref->fieldPath, $newImageUrl),
            default => throw new \InvalidArgumentException("Tipo de referencia desconocido: {$ref->sourceType}"),
        };
    }

    protected function rewriteHomeSectionField(int $sectionId, string $fieldPath, string $newImageUrl): void
    {
        $section = HomeSection::findOrFail($sectionId);
        $config = $section->config ?? [];
        Arr::set($config, $fieldPath, $newImageUrl);
        $section->config = $config;
        $section->save();
    }

    /**
     * Borra el archivo físico solo si, tras las reescrituras ya aplicadas,
     * ninguna de las 6 fuentes sigue apuntando a esa ruta.
     */
    public function deletePhysicalFileIfOrphaned(string $imageUrl): bool
    {
        if ($this->isReferencedElsewhere($imageUrl)) {
            return false;
        }

        $path = $this->resolveDiskPath($imageUrl);

        if ($path && file_exists($path)) {
            @unlink($path);

            return true;
        }

        return false;
    }
}
