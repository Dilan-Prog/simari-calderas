<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\GalleryImage;
use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\ProductImage;
use App\Models\Products;
use App\Models\ServicePage;
use App\Support\ImageReference;
use App\Support\UploadPath;
use Illuminate\Support\Arr;

/**
 * Uniformiza las formas reales en las que el catálogo guarda una ruta de
 * imagen (fila de product_images, columnas simples en Brand/Category/
 * Collection/Products/ServicePage, fila de HomeSectionSlide, o llaves
 * dentro del JSON config de HomeSection) para que el detector/consolidador
 * de duplicados pueda leer, comparar y reescribir cualquiera de ellas sin
 * tocar sus controladores.
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

        foreach (Products::whereNotNull('cover_image_url')->where('cover_image_url', '!=', '')->get() as $product) {
            $raw = $product->getRawOriginal('cover_image_url');
            if (!$raw || str_starts_with($raw, 'http')) {
                continue;
            }
            $refs[] = new ImageReference('product_cover', $product->id, 'cover_image_url', $raw, $product->name, 'Imagen de portada');
        }

        foreach (ServicePage::whereNotNull('cover_image_url')->where('cover_image_url', '!=', '')->get() as $sp) {
            $raw = $sp->getRawOriginal('cover_image_url');
            if (!$raw || str_starts_with($raw, 'http')) {
                continue;
            }
            $refs[] = new ImageReference('service_page_cover', $sp->id, 'cover_image_url', $raw, $sp->name, 'Portada de servicio');
        }

        foreach (GalleryImage::whereNotNull('path')->where('path', '!=', '')->get() as $galleryImage) {
            $raw = $galleryImage->getRawOriginal('path');
            if (!$raw || str_starts_with($raw, 'http')) {
                continue;
            }
            $refs[] = new ImageReference('gallery_image', $galleryImage->id, 'path', $raw, $galleryImage->original_name ?: 'Imagen de galería', 'Galería');
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
            'product_image' => $this->rewriteProductImage($ref->sourceId, $newImageUrl),
            'product_cover' => Products::where('id', $ref->sourceId)->update(['cover_image_url' => $newImageUrl]),
            'service_page_cover' => ServicePage::where('id', $ref->sourceId)->update(['cover_image_url' => $newImageUrl]),
            'gallery_image' => $this->rewriteGalleryImage($ref->sourceId, $newImageUrl),
            'brand' => Brand::where('id', $ref->sourceId)->update(['logo_url' => $newImageUrl]),
            'category' => Category::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'collection' => Collection::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'collection_og' => Collection::where('id', $ref->sourceId)->update(['og_image_url' => $newImageUrl]),
            'home_section_slide' => HomeSectionSlide::where('id', $ref->sourceId)->update(['image_url' => $newImageUrl]),
            'home_section' => $this->rewriteHomeSectionField($ref->sourceId, $ref->fieldPath, $newImageUrl),
            default => throw new \InvalidArgumentException("Tipo de referencia desconocido: {$ref->sourceType}"),
        };
    }

    /**
     * A diferencia de las demás fuentes (columna única por fila), un producto
     * puede tener varias filas ProductImage en su propia galería. Si la URL
     * ganadora ya está presente en la galería de ESTE producto bajo otra
     * fila, actualizar in-place solo crearía una segunda fila idéntica —
     * se borra la fila perdedora en su lugar.
     */
    protected function rewriteProductImage(int $productImageId, string $newImageUrl): void
    {
        $current = ProductImage::find($productImageId);
        if (!$current) {
            return;
        }

        $duplicateExists = ProductImage::where('product_id', $current->product_id)
            ->where('id', '!=', $productImageId)
            ->where('image_url', $newImageUrl)
            ->exists();

        if ($duplicateExists) {
            $current->delete();
            return;
        }

        $current->update(['image_url' => $newImageUrl]);
    }

    /**
     * A diferencia de las columnas únicas por fila (Brand/Category/etc.),
     * gallery_images es una lista plana global — casi cualquier imagen del
     * catálogo pasó por aquí en algún momento. Si la URL ganadora YA es el
     * path de otra fila GalleryImage, actualizar in-place crearía dos filas
     * idénticas en la biblioteca — se borra la fila perdedora en su lugar
     * (mismo criterio que rewriteProductImage()).
     */
    protected function rewriteGalleryImage(int $galleryImageId, string $newImageUrl): void
    {
        $current = GalleryImage::find($galleryImageId);
        if (!$current) {
            return;
        }

        $duplicateExists = GalleryImage::where('id', '!=', $galleryImageId)
            ->where('path', $newImageUrl)
            ->exists();

        if ($duplicateExists) {
            $current->delete();
            return;
        }

        $current->update(['path' => $newImageUrl]);
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
     * ninguna fuente conocida sigue apuntando a esa ruta.
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
