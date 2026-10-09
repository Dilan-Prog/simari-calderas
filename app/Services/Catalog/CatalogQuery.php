<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Punto de entrada del listado. Filtra y calcula las facetas en UNA sola pasada
 * sobre CatalogIndex::get() y devuelve el DTO CatalogResult (ver su docblock:
 * es el contrato con las vistas).
 *
 * Facetado: para cada fila se calculan las dimensiones que incumple (marca,
 * precio, disponibilidad, envío gratis, cada grupo técnico, q, legado). Con 0
 * incumplidas entra a los resultados y cuenta en todas las facetas; con
 * exactamente 1 cuenta solo en la faceta de esa dimensión (así cada faceta
 * ignora su propio filtro y muestra cuánto daría al alternarla).
 */
class CatalogQuery
{
    /**
     * @param ?Category $scope  categoría de la ruta (null = /catalogo completo)
     * @param string    $baseUrl URL base para armar los href (ruta sin query), ej. route('catalog.category', $slug)
     */
    public function run(CatalogParams $params, ?Category $scope, string $baseUrl, int $perPage = 24): CatalogResult
    {
        $index = CatalogIndex::get();
        $state = $this->evaluate($index, $params, $scope);

        $results = $this->sortRows($state['results'], $params->order);
        $total = count($results);
        $pages = max(1, (int) ceil($total / $perPage));
        $requested = max(1, $params->page);
        $page = min($requested, $pages);
        $outOfRange = $requested > $pages;
        $params->page = $page;

        $models = $this->hydrate(array_slice($results, ($page - 1) * $perPage, $perPage));
        $paginator = (new LengthAwarePaginator($models, $total, $perPage, $page, ['path' => $baseUrl]))
            ->appends($params->toQuery(false));

        $href = function (callable $mutate) use ($params, $baseUrl): string {
            $p = clone $params;
            $mutate($p);

            return $p->url($baseUrl);
        };

        $facets = [
            'categories'    => $this->categoryFacet($index, $params, $scope, $state['catDirect'], $total),
            'brands'        => $this->brandFacet($index, $params, $state['brandCounts'], $href),
            'price'         => $this->priceFacet($params, $state, $baseUrl, $href),
            'availability'  => $this->availabilityFacet($params, $state, $href),
            'free_shipping' => [
                'label'     => 'Envío gratis',
                'count'     => $state['fgCount'],
                'selected'  => $params->freeShipping,
                'href'      => $href(fn (CatalogParams $p) => $p->freeShipping = !$p->freeShipping),
                'inputName' => 'envio_gratis',
                'value'     => '1',
            ],
            'tech'          => $this->techFacet($state['groups'], $params, $state['techCounts'], $href),
            'maxVisible'    => max(1, (int) CatalogSettings::get('filter_max_visible')),
        ];

        $activeCount = $params->activeCount();
        $noindex = $activeCount > 0 || $params->order !== 'relevancia' || $outOfRange;

        $meta = [
            'page'         => $page,
            'pages'        => $pages,
            'activeCount'  => $activeCount,
            'noindex'      => $noindex,
            'canonicalUrl' => (!$noindex && $page > 1) ? $baseUrl . '?page=' . $page : $baseUrl,
            'stateUrl'     => $params->url($baseUrl, true),
            'liveMessage'  => match (true) {
                $total === 0 => 'Sin resultados',
                $total === 1 => '1 resultado',
                default      => number_format($total) . ' resultados',
            },
        ];

        return new CatalogResult(
            paginator: $paginator,
            total: $total,
            facets: $facets,
            chips: $this->chips($index, $state['groups'], $params, $href),
            clearHref: $href(function (CatalogParams $p) {
                $p->q = '';
                $p->priceMin = $p->priceMax = null;
                $p->brands = $p->availability = $p->tech = $p->legacyCategories = [];
                $p->freeShipping = false;
            }),
            sortOptions: $this->sortOptions($params, $href),
            meta: $meta,
            params: $params,
        );
    }

    /**
     * Búsqueda del overlay del header (/buscar-en-vivo): mismo motor, sin
     * categoría de ruta. Los conteos de categorías (planas, por la categoría
     * directa del producto) y de marcas son facetados, como en el catálogo.
     *
     * @return array{total:int, categories:array, brands:array, products:Collection}
     */
    public function search(CatalogParams $params, int $perPage = 12): array
    {
        $index = CatalogIndex::get();
        $state = $this->evaluate($index, $params, null);

        $results = $this->sortRows($state['results'], $params->order);

        $categories = [];
        foreach ($state['catFlat'] as $id => $count) {
            if (isset($index['cats'][$id])) {
                $categories[] = ['id' => $id, 'name' => $index['cats'][$id]['name'], 'count' => $count];
            }
        }

        $brands = [];
        if ($state['brandCounts']) {
            foreach (Brand::query()->whereIn('id', array_keys($state['brandCounts']))->get(['id', 'name']) as $brand) {
                $brands[] = ['id' => (int) $brand->id, 'name' => $brand->name, 'count' => $state['brandCounts'][$brand->id]];
            }
        }

        $byName = fn (array $a, array $b) => strcmp(self::sortKey($a['name']), self::sortKey($b['name']));
        usort($categories, $byName);
        usort($brands, $byName);

        return [
            'total'      => count($results),
            'categories' => $categories,
            'brands'     => $brands,
            'products'   => $this->hydrate(array_slice($results, 0, $perPage)),
        ];
    }

    // ── Pasada única: filtrado + conteos facetados ──────────────────────────

    private function evaluate(array $index, CatalogParams $params, ?Category $scope): array
    {
        $scopeId = $scope?->id !== null ? (int) $scope->id : null;
        $scopeSet = $scopeId !== null ? CatalogIndex::subtreeIds($index, $scopeId) : null;
        $qIds = $params->q !== '' ? array_flip($this->searchIds($params->q)) : null;

        $groups = CatalogIndex::scopeGroups($index, $scopeId);
        $optionGroup = [];
        foreach ($groups as $gid => $group) {
            foreach ($group['options'] as $option) {
                $optionGroup[$option['id']] = $gid;
            }
        }
        $techSel = [];
        foreach ($params->tech as $gid => $ids) {
            if (isset($groups[$gid]) && $ids) {
                $techSel[$gid] = array_flip($ids);
            }
        }

        $brandSel = $params->brands ? array_flip($params->brands) : null;
        $legacySel = $params->legacyCategories ? array_flip($params->legacyCategories) : null;
        $min = $params->priceMin;
        $max = $params->priceMax;
        $hasPrice = $min !== null || $max !== null;
        $needStock = in_array('stock', $params->availability, true);
        $need24 = in_array('24_48h', $params->availability, true);
        $needFree = $params->freeShipping;

        $results = [];
        $scopePrices = [];
        $priceList = [];
        $brandCounts = [];
        $catDirect = [];
        $catFlat = [];
        $techCounts = [];
        $stockCount = 0;
        $fastCount = 0;
        $fgCount = 0;

        foreach ($index['rows'] as $row) {
            if ($scopeSet !== null && !isset($scopeSet[$row['c']])) {
                continue;
            }
            $scopePrices[] = $row['p'];

            $viol = 0;
            $dim = '';

            if ($brandSel !== null && !isset($brandSel[$row['b']])) {
                $viol++;
                $dim = 'brand';
            }
            if ($hasPrice && !PriceBuckets::within($row['p'], $min, $max)) {
                if (++$viol > 1) {
                    continue;
                }
                $dim = 'price';
            }
            if (($needStock && !$row['st']) || ($need24 && !$row['f24'])) {
                if (++$viol > 1) {
                    continue;
                }
                $dim = 'avail';
            }
            if ($needFree && !$row['fg']) {
                if (++$viol > 1) {
                    continue;
                }
                $dim = 'fg';
            }
            foreach ($techSel as $gid => $selected) {
                $hit = false;
                foreach ($row['o'] as $optionId) {
                    if (isset($selected[$optionId])) {
                        $hit = true;
                        break;
                    }
                }
                if (!$hit) {
                    if (++$viol > 1) {
                        continue 2;
                    }
                    $dim = 'g' . $gid;
                }
            }
            if ($qIds !== null && !isset($qIds[$row['id']])) {
                if (++$viol > 1) {
                    continue;
                }
                $dim = 'q';
            }
            if ($legacySel !== null && !isset($legacySel[$row['c']])) {
                if (++$viol > 1) {
                    continue;
                }
                $dim = 'legacy';
            }

            $all = $viol === 0;

            if ($all) {
                $results[] = $row;
                if ($row['c'] !== null) {
                    $catDirect[$row['c']] = ($catDirect[$row['c']] ?? 0) + 1;
                }
            }
            if (($all || $dim === 'brand') && $row['b'] !== null) {
                $brandCounts[$row['b']] = ($brandCounts[$row['b']] ?? 0) + 1;
            }
            if ($all || $dim === 'price') {
                $priceList[] = $row['p'];
            }
            if ($all || $dim === 'avail') {
                $stockCount += $row['st'] ? 1 : 0;
                $fastCount += $row['f24'] ? 1 : 0;
            }
            if (($all || $dim === 'fg') && $row['fg']) {
                $fgCount++;
            }
            if ($optionGroup && $row['o']) {
                foreach ($row['o'] as $optionId) {
                    $gid = $optionGroup[$optionId] ?? null;
                    if ($gid !== null && ($all || $dim === 'g' . $gid)) {
                        $techCounts[$optionId] = ($techCounts[$optionId] ?? 0) + 1;
                    }
                }
            }
            if (($all || $dim === 'legacy') && $row['c'] !== null) {
                $catFlat[$row['c']] = ($catFlat[$row['c']] ?? 0) + 1;
            }
        }

        return compact(
            'results', 'scopePrices', 'priceList', 'brandCounts', 'catDirect', 'catFlat',
            'techCounts', 'stockCount', 'fastCount', 'fgCount', 'groups'
        );
    }

    /** IDs de productos cuyo nombre, SKU, descripción o marca contienen $q (% y _ escapados). */
    private function searchIds(string $q): array
    {
        $like = '%' . addcslashes($q, '\\%_') . '%';

        return Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereIn('brand_id', Brand::query()->select('id')->where('name', 'like', $like));
            })
            ->pluck('id')
            ->all();
    }

    private function sortRows(array $rows, string $order): array
    {
        $relevance = fn (array $a, array $b) => ($b['feat'] <=> $a['feat'])
            ?: ($b['ts'] <=> $a['ts'])
            ?: ($b['id'] <=> $a['id']);

        $compare = match ($order) {
            'descuento'   => fn ($a, $b) => (($b['d'] ?? 0) <=> ($a['d'] ?? 0)) ?: $relevance($a, $b),
            'precio_asc'  => fn ($a, $b) => ($a['p'] <=> $b['p']) ?: $relevance($a, $b),
            'precio_desc' => fn ($a, $b) => ($b['p'] <=> $a['p']) ?: $relevance($a, $b),
            'vendidos'    => fn ($a, $b) => ($b['u'] <=> $a['u']) ?: $relevance($a, $b),
            'nuevos'      => fn ($a, $b) => ($b['ts'] <=> $a['ts']) ?: ($b['id'] <=> $a['id']),
            default       => $relevance,
        };

        usort($rows, $compare);

        return $rows;
    }

    /** Carga solo los modelos de la página (con eager loading), conservando el orden. */
    private function hydrate(array $rows): Collection
    {
        $ids = array_column($rows, 'id');
        if (!$ids) {
            return collect();
        }

        $models = Products::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['brand', 'category', 'images' => fn ($q) => $q->orderBy('sort_order')])
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $models->get($id))->filter()->values();
    }

    // ── Facetas ──────────────────────────────────────────────────────────────

    private function categoryFacet(array $index, CatalogParams $params, ?Category $scope, array $catDirect, int $total): array
    {
        // Al cambiar de categoría se conservan q/marca/disp/envío/orden y se
        // descartan precio, f y page.
        $keep = new CatalogParams();
        $keep->q = $params->q;
        $keep->brands = $params->brands;
        $keep->availability = $params->availability;
        $keep->freeShipping = $params->freeShipping;
        $keep->order = $params->order;

        $scopeId = $scope?->id !== null ? (int) $scope->id : null;

        $items = [];
        foreach ($index['cats'] as $cat) {
            if (!$cat['is_active'] || $cat['parent_id'] !== $scopeId) {
                continue;
            }
            $subtree = CatalogIndex::subtreeIds($index, $cat['id']);
            $count = array_sum(array_intersect_key($catDirect, $subtree));
            if ($count <= 0) {
                continue;
            }
            $items[] = [
                'id'    => $cat['id'],
                'name'  => $cat['name'],
                'url'   => $keep->url(route('catalog.category', $cat['slug'])),
                'count' => (int) $count,
                '_sort' => [$cat['sort_order'], self::sortKey($cat['name'])],
            ];
        }
        usort($items, fn ($a, $b) => $a['_sort'] <=> $b['_sort']);
        $items = array_map(function ($item) {
            unset($item['_sort']);

            return $item;
        }, $items);

        $current = null;
        $back = null;
        if ($scopeId !== null) {
            $current = ['name' => (string) $scope->name, 'count' => $total];

            $back = ['label' => '‹ Todas las categorías', 'url' => $keep->url(route('catalog.index'))];
            foreach (CatalogIndex::ancestorIds($index, $scopeId) as $ancestorId) {
                $ancestor = $index['cats'][$ancestorId];
                if ($ancestor['is_active']) {
                    $back = ['label' => '‹ ' . $ancestor['name'], 'url' => $keep->url(route('catalog.category', $ancestor['slug']))];
                    break;
                }
            }
        }

        return ['current' => $current, 'back' => $back, 'items' => $items];
    }

    private function brandFacet(array $index, CatalogParams $params, array $brandCounts, callable $href): array
    {
        $items = [];
        foreach ($index['brands'] as $id => $brand) {
            $count = $brandCounts[$id] ?? 0;
            $selected = in_array($id, $params->brands, true);
            if ($count <= 0 && !$selected) {
                continue;
            }
            $items[] = [
                'id'        => $id,
                'name'      => $brand['name'],
                'count'     => $count,
                'selected'  => $selected,
                'href'      => $href(function (CatalogParams $p) use ($id) {
                    $p->brands = in_array($id, $p->brands, true)
                        ? array_values(array_diff($p->brands, [$id]))
                        : [...$p->brands, $id];
                }),
                'inputName' => 'marca[]',
                'value'     => $id,
            ];
        }

        usort($items, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: strcmp(self::sortKey($a['name']), self::sortKey($b['name'])));

        return $items;
    }

    private function priceFacet(CatalogParams $params, array $state, string $baseUrl, callable $href): array
    {
        $scopePrices = $state['scopePrices'];

        $ranges = [];
        foreach (PriceBuckets::ranges($scopePrices) as $range) {
            $count = 0;
            foreach ($state['priceList'] as $price) {
                if (PriceBuckets::within($price, $range['min'], $range['max'])) {
                    $count++;
                }
            }
            $active = $params->priceMin === $range['min'] && $params->priceMax === $range['max'];
            if ($count <= 0 && !$active) {
                continue;
            }
            $ranges[] = [
                'label'  => PriceBuckets::label($range['min'], $range['max']),
                'min'    => $range['min'],
                'max'    => $range['max'],
                'count'  => $count,
                'active' => $active,
                'href'   => $href(function (CatalogParams $p) use ($range, $active) {
                    $p->priceMin = $active ? null : $range['min'];
                    $p->priceMax = $active ? null : $range['max'];
                }),
            ];
        }
        if (count($ranges) < 2) {
            $ranges = [];
        }

        return [
            'min'        => $scopePrices ? (int) floor(min($scopePrices)) : 0,
            'max'        => $scopePrices ? (int) ceil(max($scopePrices)) : 0,
            'ranges'     => $ranges,
            'selected'   => ['min' => $params->priceMin, 'max' => $params->priceMax],
            'formAction' => $baseUrl,
            'hidden'     => $params->hiddenFields(),
        ];
    }

    private function availabilityFacet(CatalogParams $params, array $state, callable $href): array
    {
        $definitions = [
            'stock' => ['En stock', $state['stockCount']],
            '24_48h' => [(string) CatalogSettings::get('fast_shipping_label'), $state['fastCount']],
        ];

        $items = [];
        foreach ($definitions as $key => [$label, $count]) {
            $selected = in_array($key, $params->availability, true);
            if ($count <= 0 && !$selected) {
                continue;
            }
            $items[] = [
                'key'       => $key,
                'label'     => $label,
                'count'     => $count,
                'selected'  => $selected,
                'href'      => $href(function (CatalogParams $p) use ($key) {
                    $list = in_array($key, $p->availability, true)
                        ? array_diff($p->availability, [$key])
                        : [...$p->availability, $key];
                    $p->availability = array_values(array_intersect(CatalogParams::AVAILABILITY, $list));
                }),
                'inputName' => 'disp[]',
                'value'     => $key,
            ];
        }

        return $items;
    }

    private function techFacet(array $groups, CatalogParams $params, array $techCounts, callable $href): array
    {
        $out = [];
        foreach ($groups as $gid => $group) {
            $selectedIds = $params->tech[$gid] ?? [];
            $options = [];
            foreach ($group['options'] as $position => $option) {
                $count = $techCounts[$option['id']] ?? 0;
                $selected = in_array($option['id'], $selectedIds, true);
                if ($count <= 0 && !$selected) {
                    continue;
                }
                $options[] = [
                    'id'        => $option['id'],
                    'label'     => $option['label'],
                    'count'     => $count,
                    'selected'  => $selected,
                    'href'      => $href(fn (CatalogParams $p) => self::toggleTech($p, $gid, $option['id'])),
                    'inputName' => "f[{$gid}][]",
                    'value'     => $option['id'],
                    '_pos'      => $position,
                ];
            }
            if (!$options) {
                continue;
            }
            usort($options, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($a['_pos'] <=> $b['_pos']));
            $options = array_map(function ($option) {
                unset($option['_pos']);

                return $option;
            }, $options);

            $out[] = ['id' => $gid, 'name' => $group['name'], 'options' => $options];
        }

        return $out;
    }

    private static function toggleTech(CatalogParams $p, int $groupId, int $optionId): void
    {
        $current = $p->tech[$groupId] ?? [];
        $current = in_array($optionId, $current, true)
            ? array_values(array_diff($current, [$optionId]))
            : [...$current, $optionId];

        if ($current) {
            $p->tech[$groupId] = $current;
        } else {
            unset($p->tech[$groupId]);
        }
    }

    private function chips(array $index, array $groups, CatalogParams $params, callable $href): array
    {
        $chips = [];

        if ($params->q !== '') {
            $chips[] = ['label' => 'Búsqueda: “' . $params->q . '”', 'removeHref' => $href(fn (CatalogParams $p) => $p->q = '')];
        }

        foreach ($params->brands as $id) {
            $chips[] = [
                'label'      => $index['brands'][$id]['name'] ?? 'Marca',
                'removeHref' => $href(fn (CatalogParams $p) => $p->brands = array_values(array_diff($p->brands, [$id]))),
            ];
        }

        if ($params->priceMin !== null || $params->priceMax !== null) {
            $chips[] = [
                'label'      => PriceBuckets::label($params->priceMin, $params->priceMax),
                'removeHref' => $href(function (CatalogParams $p) {
                    $p->priceMin = null;
                    $p->priceMax = null;
                }),
            ];
        }

        foreach ($params->availability as $key) {
            $chips[] = [
                'label'      => $key === 'stock' ? 'En stock' : (string) CatalogSettings::get('fast_shipping_label'),
                'removeHref' => $href(fn (CatalogParams $p) => $p->availability = array_values(array_diff($p->availability, [$key]))),
            ];
        }

        if ($params->freeShipping) {
            $chips[] = ['label' => 'Envío gratis', 'removeHref' => $href(fn (CatalogParams $p) => $p->freeShipping = false)];
        }

        foreach ($params->tech as $gid => $ids) {
            foreach ($ids as $optionId) {
                $label = null;
                foreach ($groups[$gid]['options'] ?? [] as $option) {
                    if ($option['id'] === $optionId) {
                        $label = ($groups[$gid]['name'] ?? '') . ': ' . $option['label'];
                    }
                }
                $chips[] = [
                    'label'      => $label ?? 'Filtro',
                    'removeHref' => $href(fn (CatalogParams $p) => self::toggleTech($p, $gid, $optionId)),
                ];
            }
        }

        foreach ($params->legacyCategories as $id) {
            $chips[] = [
                'label'      => $index['cats'][$id]['name'] ?? 'Categoría',
                'removeHref' => $href(fn (CatalogParams $p) => $p->legacyCategories = array_values(array_diff($p->legacyCategories, [$id]))),
            ];
        }

        return $chips;
    }

    private function sortOptions(CatalogParams $params, callable $href): array
    {
        $labels = [
            'relevancia'  => 'Más relevantes',
            'descuento'   => 'Mayor descuento',
            'precio_asc'  => 'Menor precio',
            'precio_desc' => 'Mayor precio',
            'vendidos'    => 'Más vendidos',
            'nuevos'      => 'Más nuevos',
        ];

        $out = [];
        foreach ($labels as $key => $label) {
            $out[] = [
                'key'      => $key,
                'label'    => $label,
                'href'     => $href(fn (CatalogParams $p) => $p->order = $key),
                'selected' => $params->order === $key,
            ];
        }

        return $out;
    }

    /** Clave de ordenación alfabética sin acentos ni mayúsculas. */
    private static function sortKey(string $value): string
    {
        return mb_strtolower(\Illuminate\Support\Str::ascii($value));
    }
}
