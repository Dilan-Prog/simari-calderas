<?php

namespace App\Services;

use App\Models\HomeSection;
use App\Models\ProductSectionAssignment;
use App\Models\Products;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bloques dinámicos de la página de producto: lectura para el frontend y
 * asignación (individual y masiva) para el admin. Los bloques son
 * HomeSection de page 'product_template' (plantilla ligada, compartida por N
 * productos) o 'product_custom' (propia de un producto).
 */
class ProductBlocks
{
    public const MODE_APPEND  = 'append';
    public const MODE_PREPEND = 'prepend';
    public const MODE_REPLACE = 'replace';
    public const MODE_REMOVE  = 'remove';

    /**
     * Bloques visibles de un producto separados por zona, en orden.
     * Solo bloques activos; la zona sale de HomeSection::zone.
     *
     * @return array{stack: Collection, sidebar: Collection}
     */
    public static function forProduct(Products $product): array
    {
        $sections = ProductSectionAssignment::query()
            ->where('product_id', $product->id)
            ->where('is_visible', true)
            ->with('section')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->pluck('section')
            ->filter(fn (?HomeSection $s) => $s && $s->is_active
                && in_array($s->page, [HomeSection::PAGE_PRODUCT_TEMPLATE, HomeSection::PAGE_PRODUCT_CUSTOM], true))
            ->values();

        return [
            'stack'   => $sections->where('zone', HomeSection::ZONE_STACK)->values(),
            'sidebar' => $sections->where('zone', HomeSection::ZONE_SIDEBAR)->values(),
        ];
    }

    /**
     * Asigna/quita plantillas a varios productos en una transacción
     * (acción del editor masivo y del formulario de producto).
     *
     * - append:  agrega al final (ignora las que el producto ya tiene).
     * - prepend: agrega al inicio y recorre el resto.
     * - replace: borra TODAS las asignaciones de plantilla del producto y
     *            pone estas (las secciones propias no se tocan).
     * - remove:  quita estas plantillas del producto.
     *
     * @param  int[]  $productIds
     * @param  int[]  $sectionIds  ids de HomeSection page=product_template
     * @return array{products: int, added: int, removed: int}
     */
    public static function applyTemplates(array $productIds, array $sectionIds, string $mode = self::MODE_APPEND): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        $sectionIds = HomeSection::productTemplates()
            ->whereIn('id', array_map('intval', $sectionIds))
            ->pluck('id')
            ->all();

        $added = 0;
        $removed = 0;

        DB::transaction(function () use ($productIds, $sectionIds, $mode, &$added, &$removed) {
            foreach (Products::whereIn('id', $productIds)->pluck('id') as $productId) {
                $current = ProductSectionAssignment::where('product_id', $productId);

                if ($mode === self::MODE_REMOVE) {
                    $removed += ProductSectionAssignment::where('product_id', $productId)
                        ->whereIn('home_section_id', $sectionIds)->delete();
                    continue;
                }

                if ($mode === self::MODE_REPLACE) {
                    $removed += ProductSectionAssignment::where('product_id', $productId)
                        ->whereIn('home_section_id', HomeSection::productTemplates()->select('id'))
                        ->delete();
                }

                $existing = ProductSectionAssignment::where('product_id', $productId)
                    ->pluck('home_section_id')->all();
                $toAdd = array_values(array_diff($sectionIds, $existing));

                if (!$toAdd) {
                    continue;
                }

                if ($mode === self::MODE_PREPEND) {
                    ProductSectionAssignment::where('product_id', $productId)->increment('sort_order', count($toAdd));
                    $start = 0;
                } else {
                    $start = (int) (ProductSectionAssignment::where('product_id', $productId)->max('sort_order') ?? -1) + 1;
                }

                foreach ($toAdd as $i => $sectionId) {
                    ProductSectionAssignment::create([
                        'product_id'      => $productId,
                        'home_section_id' => $sectionId,
                        'sort_order'      => $start + $i,
                        'is_visible'      => true,
                    ]);
                    $added++;
                }
            }
        });

        return ['products' => count($productIds), 'added' => $added, 'removed' => $removed];
    }

    /** Cuántos productos usan cada plantilla (id => total). */
    public static function usageCounts(): Collection
    {
        return ProductSectionAssignment::query()
            ->select('home_section_id', DB::raw('count(*) as total'))
            ->groupBy('home_section_id')
            ->pluck('total', 'home_section_id');
    }

    /**
     * "Convertir en copia propia": duplica la plantilla como sección
     * 'product_custom' y reemplaza la asignación (misma posición/visibilidad).
     */
    public static function copyToCustom(ProductSectionAssignment $assignment): HomeSection
    {
        return DB::transaction(function () use ($assignment) {
            $template = $assignment->section;

            $copy = $template->replicate(['page', 'name']);
            $copy->page = HomeSection::PAGE_PRODUCT_CUSTOM;
            $copy->name = $template->name ? $template->name . ' (copia)' : null;
            $copy->save();

            foreach ($template->slides as $slide) {
                $copy->slides()->create($slide->only(['image_url', 'badge_text', 'title', 'title_highlight', 'description', 'link_url', 'sort_order', 'is_active']));
            }

            $assignment->update(['home_section_id' => $copy->id]);

            return $copy;
        });
    }

    /**
     * Guarda el orden/visibilidad completo de un producto de una vez.
     *
     * @param  array<int, array{id:int, is_visible?:bool}>  $rows  ids de ProductSectionAssignment en el orden nuevo
     */
    public static function reorder(Products $product, array $rows): void
    {
        DB::transaction(function () use ($product, $rows) {
            foreach (array_values($rows) as $i => $row) {
                ProductSectionAssignment::where('product_id', $product->id)
                    ->where('id', (int) $row['id'])
                    ->update(array_filter([
                        'sort_order' => $i,
                        'is_visible' => isset($row['is_visible']) ? (bool) $row['is_visible'] : null,
                    ], fn ($v) => $v !== null));
            }
        });
    }
}
