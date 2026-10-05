<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Models\ProductSectionAssignment;
use App\Models\Products;
use App\Models\SystemLog;
use App\Services\ProductBlocks;
use App\Support\LinkTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Bloques dinámicos del detalle de producto (pestaña "Bloques" del formulario
 * de edición y acción "Plantillas de bloques" del editor por lotes).
 *
 * Los bloques son HomeSection (page 'product_template' compartida por N
 * productos, o 'product_custom' propia de UN producto) unidos al producto por
 * ProductSectionAssignment. Toda la lógica de asignación vive en
 * App\Services\ProductBlocks; aquí solo se valida, se arma la respuesta JSON y
 * se deja registro en la bitácora (system_logs).
 */
class ProductBlockController extends Controller
{
    private const TYPE_LABELS = [
        'hero_slider'             => 'Slider Principal',
        'banner'                  => 'Banner',
        'dual_banner'             => 'Banner Doble',
        'product_carousel'        => 'Carrusel de Productos',
        'product_carousel_banner' => 'Carrusel con Banner',
        'category_grid'           => 'Grid de Categorías',
        'brand_carousel'          => 'Carrusel de Marcas',
        'html_block'              => 'Bloque HTML',
        'faq'                     => 'Preguntas Frecuentes',
    ];

    private const MODES = [
        ProductBlocks::MODE_APPEND,
        ProductBlocks::MODE_PREPEND,
        ProductBlocks::MODE_REPLACE,
        ProductBlocks::MODE_REMOVE,
    ];

    private const BULK_MAX_PRODUCTS = 5000;

    // ─────────────────────────────────────────────────────────────────
    // Pestaña "Bloques" de un producto
    // ─────────────────────────────────────────────────────────────────

    /** Lista de bloques del producto (ambas zonas, en orden). */
    public function index(string $id): JsonResponse
    {
        $product = Products::findOrFail($id);

        return response()->json(['success' => true, 'blocks' => $this->blocksPayload($product)]);
    }

    /**
     * Plantillas disponibles (selector con buscador). Parámetros:
     *  - q: texto (nombre/título/tipo)
     *  - zone: stack|sidebar (opcional)
     *  - product_id: excluye las que ese producto ya tiene
     *  - include_inactive=1: incluye plantillas inactivas (lote: quitar)
     */
    public function templates(Request $request): JsonResponse
    {
        $request->validate([
            'q'                => 'nullable|string|max:100',
            'zone'             => ['nullable', Rule::in([HomeSection::ZONE_STACK, HomeSection::ZONE_SIDEBAR])],
            'product_id'       => 'nullable|integer',
            'include_inactive' => 'nullable|boolean',
        ]);

        $q = trim((string) $request->input('q', ''));

        $query = HomeSection::productTemplates()->orderBy('name')->orderBy('id');

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }
        if ($zone = $request->input('zone')) {
            $query->where('zone', $zone);
        }
        if ($productId = $request->input('product_id')) {
            $query->whereNotIn('id', ProductSectionAssignment::where('product_id', $productId)->select('home_section_id'));
        }
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('type', 'like', $like);
            });
        }

        $sections = $query->limit(60)->get();
        $usage = ProductBlocks::usageCounts();

        return response()->json([
            'success'   => true,
            'templates' => $sections->map(fn (HomeSection $s) => $this->templatePayload($s, (int) ($usage[$s->id] ?? 0)))->values(),
        ]);
    }

    /** "Usar plantilla": agrega al final las plantillas elegidas. */
    public function attach(Request $request, string $id): JsonResponse
    {
        $product = Products::findOrFail($id);

        $data = $request->validate([
            'section_ids'   => 'required|array|min:1|max:50',
            'section_ids.*' => 'integer',
        ]);

        $result = ProductBlocks::applyTemplates([$product->id], $data['section_ids'], ProductBlocks::MODE_APPEND);

        if ($result['added'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esas plantillas ya estaban asignadas al producto o no están disponibles.',
                'blocks'  => $this->blocksPayload($product),
            ], 422);
        }

        $this->log($product->id, 'block_templates_attached', "{$result['added']} plantilla(s) asignada(s)", ['section_ids' => $data['section_ids']]);

        return response()->json(['success' => true, 'result' => $result, 'blocks' => $this->blocksPayload($product)]);
    }

    /**
     * Red de seguridad tras crear/editar una sección propia desde el editor
     * (HomeSectionEditor): garantiza que quede asignada a este producto.
     * Idempotente; solo acepta secciones product_custom que no pertenezcan a
     * otro producto.
     */
    public function adopt(Request $request, string $id): JsonResponse
    {
        $product = Products::findOrFail($id);

        $data = $request->validate(['section_id' => 'required|integer']);

        $section = HomeSection::where('page', HomeSection::PAGE_PRODUCT_CUSTOM)->find($data['section_id']);
        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Sección propia no encontrada.'], 422);
        }

        $ownedByOther = ProductSectionAssignment::where('home_section_id', $section->id)
            ->where('product_id', '!=', $product->id)->exists();
        if ($ownedByOther) {
            return response()->json(['success' => false, 'message' => 'Esa sección pertenece a otro producto.'], 422);
        }

        $exists = ProductSectionAssignment::where('product_id', $product->id)
            ->where('home_section_id', $section->id)->exists();

        if (!$exists) {
            ProductSectionAssignment::create([
                'product_id'      => $product->id,
                'home_section_id' => $section->id,
                'sort_order'      => (int) (ProductSectionAssignment::where('product_id', $product->id)->max('sort_order') ?? -1) + 1,
                'is_visible'      => true,
            ]);
            $this->log($product->id, 'block_custom_created', 'Sección propia agregada', ['section_id' => $section->id]);
        }

        return response()->json(['success' => true, 'blocks' => $this->blocksPayload($product)]);
    }

    /** Guarda el orden y la visibilidad de todos los bloques del producto. */
    public function reorder(Request $request, string $id): JsonResponse
    {
        $product = Products::findOrFail($id);

        $data = $request->validate([
            'rows'              => 'required|array|min:1|max:200',
            'rows.*.id'         => 'required|integer',
            'rows.*.is_visible' => 'nullable|boolean',
        ]);

        $rows = collect($data['rows'])->unique('id')->values();

        // Los bloques que no vengan en el payload (p. ej. agregados desde otra
        // pestaña) se conservan al final en su orden actual, sin colisionar.
        $sentIds = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
        $rest = ProductSectionAssignment::where('product_id', $product->id)
            ->whereNotIn('id', $sentIds)->orderBy('sort_order')->orderBy('id')->pluck('id');

        $all = $rows->map(fn ($r) => [
            'id'         => (int) $r['id'],
            'is_visible' => array_key_exists('is_visible', $r) && $r['is_visible'] !== null ? (bool) $r['is_visible'] : null,
        ])->concat($rest->map(fn ($rid) => ['id' => (int) $rid]))->all();

        ProductBlocks::reorder($product, $all);

        $this->log($product->id, 'blocks_reordered', 'Orden/visibilidad de bloques actualizado', ['rows' => $data['rows']]);

        return response()->json(['success' => true, 'blocks' => $this->blocksPayload($product)]);
    }

    /**
     * Quita un bloque del producto. Una plantilla solo se desasigna (sigue
     * existiendo para los demás productos); una sección propia se elimina
     * también, porque nadie más la puede usar.
     */
    public function destroy(string $id, string $assignment): JsonResponse
    {
        $product = Products::findOrFail($id);
        $row = ProductSectionAssignment::where('product_id', $product->id)->with('section')->findOrFail($assignment);
        $section = $row->section;

        DB::transaction(function () use ($row, $section) {
            $row->delete();
            if ($section && $section->page === HomeSection::PAGE_PRODUCT_CUSTOM) {
                $section->delete();
            }
        });

        $this->log($product->id, 'block_removed', 'Bloque quitado del producto', [
            'section_id' => $section?->id,
            'kind'       => $section?->page,
        ]);

        return response()->json(['success' => true, 'blocks' => $this->blocksPayload($product)]);
    }

    /** "Convertir en copia propia": la plantilla pasa a sección propia de este producto. */
    public function copyToCustom(string $id, string $assignment): JsonResponse
    {
        $product = Products::findOrFail($id);
        $row = ProductSectionAssignment::where('product_id', $product->id)->with('section')->findOrFail($assignment);

        if (!$row->section || $row->section->page !== HomeSection::PAGE_PRODUCT_TEMPLATE) {
            return response()->json(['success' => false, 'message' => 'Solo las plantillas se pueden convertir en copia propia.'], 422);
        }

        $templateId = $row->section->id;
        $copy = ProductBlocks::copyToCustom($row);

        $this->log($product->id, 'block_copied_to_custom', 'Plantilla convertida en copia propia', [
            'template_id' => $templateId,
            'copy_id'     => $copy->id,
        ]);

        return response()->json(['success' => true, 'blocks' => $this->blocksPayload($product)]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Editor por lotes
    // ─────────────────────────────────────────────────────────────────

    /** Dry-run: cuántos cambios haría la aplicación, sin escribir nada. */
    public function bulkPreview(Request $request): JsonResponse
    {
        [$productIds, $sectionIds, $mode] = $this->validateBulk($request);

        return response()->json(['success' => true] + $this->computePreview($productIds, $sectionIds, $mode));
    }

    /** Aplica (transaccional) y deja registro en la bitácora. */
    public function bulkApply(Request $request): JsonResponse
    {
        [$productIds, $sectionIds, $mode] = $this->validateBulk($request);

        $result = ProductBlocks::applyTemplates($productIds, $sectionIds, $mode);

        SystemLog::create([
            'entity_type'          => 'product',
            'entity_id'            => 0,
            'action'               => 'bulk_block_templates',
            'description'          => count($productIds) . ' producto(s), modo ' . $mode,
            'old_value'            => null,
            'new_value'            => ['ids' => $productIds, 'section_ids' => $sectionIds, 'mode' => $mode, 'result' => $result],
            'performed_by_user_id' => auth()->id(),
            'performed_at'         => now(),
            'ip_address'           => $request->ip(),
            'user_agent'           => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'mode'    => $mode,
            'result'  => $result,
            'message' => $this->applyMessage($mode, $result),
            // Nombres de plantillas por producto, para refrescar la columna
            // "Plantillas" del editor por lotes sin recargar la página.
            'templates_by_product' => $this->templateNamesByProduct($productIds),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Internos
    // ─────────────────────────────────────────────────────────────────

    /** @return array{0: int[], 1: int[], 2: string} */
    private function validateBulk(Request $request): array
    {
        $data = $request->validate([
            'ids'           => 'required|array|min:1|max:' . self::BULK_MAX_PRODUCTS,
            'ids.*'         => 'integer',
            'section_ids'   => 'required|array|min:1|max:50',
            'section_ids.*' => 'integer',
            'mode'          => ['required', Rule::in(self::MODES)],
        ]);

        $productIds = Products::whereIn('id', array_unique($data['ids']))->pluck('id')->map(fn ($v) => (int) $v)->all();
        $sectionIds = HomeSection::productTemplates()->whereIn('id', array_unique($data['section_ids']))
            ->pluck('id')->map(fn ($v) => (int) $v)->all();

        abort_if(!$productIds, 422, 'Ninguno de los productos seleccionados existe.');
        abort_if(!$sectionIds, 422, 'Ninguna de las plantillas elegidas existe.');

        return [$productIds, $sectionIds, $data['mode']];
    }

    /**
     * Cuenta, sin escribir, lo que haría ProductBlocks::applyTemplates.
     *
     * @param  int[]  $productIds
     * @param  int[]  $sectionIds
     */
    private function computePreview(array $productIds, array $sectionIds, string $mode): array
    {
        $productCount  = count($productIds);
        $templateCount = count($sectionIds);
        $pairs         = $productCount * $templateCount;

        // Pares (producto, plantilla elegida) que ya existen.
        $existing = ProductSectionAssignment::whereIn('product_id', $productIds)
            ->whereIn('home_section_id', $sectionIds)
            ->select('home_section_id', DB::raw('count(*) as total'))
            ->groupBy('home_section_id')->pluck('total', 'home_section_id');
        $alreadyHad = (int) $existing->sum();

        $sections = HomeSection::whereIn('id', $sectionIds)->get(['id', 'name', 'title', 'type', 'is_active']);
        $breakdown = $sections->map(fn (HomeSection $s) => [
            'id'      => $s->id,
            'name'    => $this->displayName($s),
            'already' => (int) ($existing[$s->id] ?? 0),
        ])->values();

        $toAdd = 0;
        $toRemove = 0;

        if ($mode === ProductBlocks::MODE_REMOVE) {
            $toRemove = $alreadyHad;
        } elseif ($mode === ProductBlocks::MODE_REPLACE) {
            // Se borran TODAS las plantillas del producto y se ponen estas.
            $toRemove = ProductSectionAssignment::whereIn('product_id', $productIds)
                ->whereIn('home_section_id', HomeSection::productTemplates()->select('id'))->count();
            $toAdd = $pairs;
        } else {
            $toAdd = $pairs - $alreadyHad;
        }

        $preview = [
            'mode'      => $mode,
            'products'  => $productCount,
            'templates' => $templateCount,
            'to_add'    => $toAdd,
            'to_remove' => $toRemove,
            'already'   => $alreadyHad,
            'breakdown' => $breakdown,
        ];
        $preview['message'] = $this->previewMessage($preview);

        return $preview;
    }

    private function previewMessage(array $p): string
    {
        $tpl = $p['templates'] === 1 ? '1 plantilla' : "{$p['templates']} plantillas";
        $prd = $p['products'] === 1 ? '1 producto' : "{$p['products']} productos";

        return match ($p['mode']) {
            ProductBlocks::MODE_REMOVE => "Se quitarán {$tpl} de {$prd}: se eliminarán {$p['to_remove']} asignación(es); "
                . ($p['products'] * $p['templates'] - $p['already']) . ' combinación(es) producto-plantilla no la tenían (se omiten).',
            ProductBlocks::MODE_REPLACE => "Se reemplazarán las plantillas de {$prd}: se quitarán {$p['to_remove']} asignación(es) actuales y se aplicarán {$tpl} ({$p['to_add']} asignación(es) en total). Las secciones propias no se tocan.",
            default => "Se aplicarán {$tpl} a {$prd}: {$p['to_add']} asignación(es) nueva(s); {$p['already']} ya la tenían"
                . ($p['mode'] === ProductBlocks::MODE_PREPEND ? ' (se omiten). Irán al inicio.' : ' (se omiten). Irán al final.'),
        };
    }

    private function applyMessage(string $mode, array $r): string
    {
        $prd = $r['products'] === 1 ? '1 producto' : "{$r['products']} productos";

        return match ($mode) {
            ProductBlocks::MODE_REMOVE  => "Listo: se quitaron {$r['removed']} asignación(es) en {$prd}.",
            ProductBlocks::MODE_REPLACE => "Listo: en {$prd} se quitaron {$r['removed']} y se asignaron {$r['added']} plantilla(s).",
            default                     => "Listo: se agregaron {$r['added']} asignación(es) en {$prd}.",
        };
    }

    /** @param int[] $productIds  @return array<int, string[]> product_id => nombres de plantillas, en orden */
    private function templateNamesByProduct(array $productIds): array
    {
        $out = array_fill_keys($productIds, []);

        ProductSectionAssignment::whereIn('product_id', $productIds)
            ->whereIn('home_section_id', HomeSection::productTemplates()->select('id'))
            ->with('section:id,name,title,type')
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->each(function (ProductSectionAssignment $a) use (&$out) {
                if ($a->section) {
                    $out[$a->product_id][] = $this->displayName($a->section);
                }
            });

        return $out;
    }

    /** Bloques del producto serializados para la pestaña. */
    private function blocksPayload(Products $product): array
    {
        $rows = ProductSectionAssignment::where('product_id', $product->id)
            ->with('section')
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $usage = ProductBlocks::usageCounts();

        return $rows
            ->filter(fn (ProductSectionAssignment $a) => $a->section)
            ->map(function (ProductSectionAssignment $a) use ($usage) {
                $s = $a->section;
                $isTemplate = $s->page === HomeSection::PAGE_PRODUCT_TEMPLATE;
                $link = $s->heading_link;

                return [
                    'id'          => $a->id,
                    'section_id'  => $s->id,
                    'kind'        => $isTemplate ? 'template' : 'custom',
                    'zone'        => $s->zone ?: HomeSection::ZONE_STACK,
                    'name'        => $this->displayName($s),
                    'type'        => $s->type,
                    'type_label'  => self::TYPE_LABELS[$s->type] ?? $s->type,
                    'usage'       => $isTemplate ? (int) ($usage[$s->id] ?? 1) : 1,
                    'is_visible'  => (bool) $a->is_visible,
                    'is_active'   => (bool) $s->is_active,
                    'link_label'  => LinkTarget::label($link),
                    'link_broken' => LinkTarget::isBroken($link),
                ];
            })->values()->all();
    }

    private function templatePayload(HomeSection $s, int $usage): array
    {
        return [
            'id'         => $s->id,
            'name'       => $this->displayName($s),
            'type'       => $s->type,
            'type_label' => self::TYPE_LABELS[$s->type] ?? $s->type,
            'zone'       => $s->zone ?: HomeSection::ZONE_STACK,
            'zone_label' => ($s->zone ?: HomeSection::ZONE_STACK) === HomeSection::ZONE_SIDEBAR ? 'Columna lateral' : 'Pila principal',
            'usage'      => $usage,
            'is_active'  => (bool) $s->is_active,
        ];
    }

    private function displayName(HomeSection $s): string
    {
        return $s->name ?: ($s->title ?: (self::TYPE_LABELS[$s->type] ?? $s->type) . ' #' . $s->id);
    }

    private function log(int $productId, string $action, string $description, array $payload = []): void
    {
        SystemLog::create([
            'entity_type'          => 'product',
            'entity_id'            => $productId,
            'action'               => $action,
            'description'          => $description,
            'old_value'            => null,
            'new_value'            => $payload ?: null,
            'performed_by_user_id' => auth()->id(),
            'performed_at'         => now(),
            'ip_address'           => request()?->ip(),
            'user_agent'           => request()?->userAgent(),
        ]);
    }
}
