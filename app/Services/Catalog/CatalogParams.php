<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Parámetros del catálogo ya validados/normalizados.
 *
 * URL: q, marca[], precio_min, precio_max, disp[] (stock|24_48h),
 * envio_gratis=1, f[<grupoId>][]=<opcionId>, orden
 * (relevancia|descuento|precio_asc|precio_desc|vendidos|nuevos), page.
 * `categoria[]` se acepta como legado (intersección extra) pero NO se emite.
 * Valores inválidos se ignoran en silencio. Los href que genera NUNCA llevan
 * valores por defecto (orden=relevancia, page=1).
 */
class CatalogParams
{
    public const ORDERS = ['relevancia', 'descuento', 'precio_asc', 'precio_desc', 'vendidos', 'nuevos'];
    public const AVAILABILITY = ['stock', '24_48h'];
    public const MAX_Q = 80;
    public const MIN_Q = 2;
    public const MAX_BRANDS = 30;
    public const MAX_PRICE = 10000000;
    public const MAX_GROUPS = 10;

    public string $q = '';
    /** @var int[] */
    public array $brands = [];
    public ?int $priceMin = null;
    public ?int $priceMax = null;
    /** @var string[] subconjunto de ['stock','24_48h'] */
    public array $availability = [];
    public bool $freeShipping = false;
    /** @var array<int, int[]> grupoId => [opcionId, ...] */
    public array $tech = [];
    public string $order = 'relevancia';
    public int $page = 1;
    /** @var int[] legado categoria[] */
    public array $legacyCategories = [];

    /**
     * Valida y normaliza. $category es la categoría de la ruta (null en
     * /catalogo): sirve para descartar grupos técnicos que no le aplican.
     */
    public static function fromRequest(Request $request, ?Category $category = null): self
    {
        $p = new self();
        $index = CatalogIndex::get();

        // q: texto, máx. 80 caracteres, mínimo 2 (si no, se ignora).
        $q = $request->input('q');
        if (is_string($q) && mb_check_encoding($q, 'UTF-8')) {
            $q = trim((string) preg_replace('/[\s\p{C}]+/u', ' ', $q));
            $q = trim(mb_substr($q, 0, self::MAX_Q));
            if (mb_strlen($q) >= self::MIN_Q) {
                $p->q = $q;
            }
        }

        // marca[]: enteros que existan como marca activa.
        $p->brands = array_values(array_intersect(
            self::intList($request->input('marca'), self::MAX_BRANDS),
            array_keys($index['brands'])
        ));

        // Precio (MXN sin IVA, enteros 0..10.000.000); min > max => se intercambian.
        $min = self::price($request->input('precio_min'));
        $max = self::price($request->input('precio_max'));
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }
        $p->priceMin = $min === 0 ? null : $min; // desde 0 no filtra nada
        $p->priceMax = $max;

        // disp[]: subconjunto de la whitelist, en orden canónico.
        $disp = $request->input('disp');
        $disp = is_array($disp) ? $disp : (is_string($disp) ? [$disp] : []);
        $p->availability = array_values(array_intersect(
            self::AVAILABILITY,
            array_filter($disp, 'is_string')
        ));

        // envio_gratis=1
        $free = $request->input('envio_gratis');
        $p->freeShipping = is_scalar($free) && in_array(strtolower((string) $free), ['1', 'true', 'on'], true);

        // f[grupoId][]: solo grupos vigentes para la categoría de la ruta.
        $f = $request->input('f');
        if ($category !== null && is_array($f)) {
            $groups = CatalogIndex::scopeGroups($index, (int) $category->id);
            foreach ($f as $groupId => $optionIds) {
                if (count($p->tech) >= self::MAX_GROUPS) {
                    break;
                }
                if (!is_int($groupId) || !isset($groups[$groupId])) {
                    continue;
                }
                $valid = array_column($groups[$groupId]['options'], 'id');
                $ids = array_values(array_intersect(self::intList($optionIds, self::MAX_BRANDS), $valid));
                if ($ids) {
                    $p->tech[$groupId] = $ids;
                }
            }
            ksort($p->tech);
        }

        // orden: whitelist con fallback.
        $order = $request->input('orden');
        $p->order = is_string($order) && in_array($order, self::ORDERS, true) ? $order : 'relevancia';

        // page >= 1
        $page = $request->input('page');
        if ((is_int($page) || is_string($page)) && preg_match('/^\d{1,6}$/', (string) $page)) {
            $p->page = max(1, (int) $page);
        }

        // categoria[] legado: ids de categorías existentes.
        $p->legacyCategories = array_values(array_intersect(
            self::intList($request->input('categoria'), self::MAX_BRANDS),
            array_keys($index['cats'])
        ));

        return $p;
    }

    /** Query-string normalizado (sin defaults), listo para serializar. */
    public function toQuery(bool $withPage = true): array
    {
        $query = [];

        if ($this->q !== '') {
            $query['q'] = $this->q;
        }
        if ($this->brands) {
            $query['marca'] = self::sorted($this->brands);
        }
        if ($this->priceMin !== null) {
            $query['precio_min'] = $this->priceMin;
        }
        if ($this->priceMax !== null) {
            $query['precio_max'] = $this->priceMax;
        }
        if ($this->availability) {
            $query['disp'] = array_values(array_intersect(self::AVAILABILITY, $this->availability));
        }
        if ($this->freeShipping) {
            $query['envio_gratis'] = 1;
        }
        if ($this->tech) {
            $tech = [];
            foreach ($this->tech as $groupId => $ids) {
                if ($ids) {
                    $tech[(int) $groupId] = self::sorted($ids);
                }
            }
            ksort($tech);
            if ($tech) {
                $query['f'] = $tech;
            }
        }
        if ($this->order !== 'relevancia') {
            $query['orden'] = $this->order;
        }
        if ($this->legacyCategories) {
            $query['categoria'] = self::sorted($this->legacyCategories);
        }
        if ($withPage && $this->page > 1) {
            $query['page'] = $this->page;
        }

        return $query;
    }

    /** Cuántos filtros hay activos (marcas, precio, disp, envío, técnicos, q, legado). */
    public function activeCount(): int
    {
        $count = count($this->brands)
            + ($this->priceMin !== null || $this->priceMax !== null ? 1 : 0)
            + count($this->availability)
            + ($this->freeShipping ? 1 : 0)
            + ($this->q !== '' ? 1 : 0)
            + count($this->legacyCategories);

        foreach ($this->tech as $ids) {
            $count += count($ids);
        }

        return $count;
    }

    /** URL completa: $baseUrl + query normalizado ($withPage solo para canonical/estado). */
    public function url(string $baseUrl, bool $withPage = false): string
    {
        $qs = self::buildQueryString($this->toQuery($withPage));

        return $qs === '' ? $baseUrl : $baseUrl . '?' . $qs;
    }

    /**
     * Parámetros activos como campos <input type="hidden"> planos
     * (nombre => valor), p. ej. 'marca[0]' => '5', 'f[3][0]' => '12', para el
     * <form> GET de Desde/Hasta. Por defecto excluye precio y page.
     *
     * @return array<string, string>
     */
    public function hiddenFields(array $except = ['precio_min', 'precio_max', 'page']): array
    {
        $fields = [];
        foreach ($this->toQuery() as $key => $value) {
            if (in_array($key, $except, true)) {
                continue;
            }
            if (!is_array($value)) {
                $fields[$key] = (string) $value;
                continue;
            }
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    foreach (array_values($v) as $i => $leaf) {
                        $fields["{$key}[{$k}][{$i}]"] = (string) $leaf;
                    }
                } else {
                    $fields["{$key}[{$k}]"] = (string) $v;
                }
            }
        }

        return $fields;
    }

    /**
     * Serializa con la forma legible de PHP: marca[]=1&marca[]=2&f[3][]=7 (los
     * corchetes sin codificar; valores con rawurlencode).
     */
    public static function buildQueryString(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if (!is_array($value)) {
                $parts[] = $key . '=' . rawurlencode((string) $value);
                continue;
            }
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $leaf) {
                        $parts[] = "{$key}[{$k}][]=" . rawurlencode((string) $leaf);
                    }
                } else {
                    $parts[] = $key . '[]=' . rawurlencode((string) $v);
                }
            }
        }

        return implode('&', $parts);
    }

    /** @return int[] enteros >= 1, únicos y ordenados; ignora todo lo demás. */
    private static function intList(mixed $value, int $max): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $out = [];
        foreach (is_array($value) ? $value : [$value] as $item) {
            if (is_int($item)) {
                $n = $item;
            } elseif (is_string($item) && preg_match('/^\d{1,9}$/', $item)) {
                $n = (int) $item;
            } else {
                continue;
            }
            if ($n < 1) {
                continue;
            }
            $out[$n] = $n;
            if (count($out) >= $max) {
                break;
            }
        }

        return self::sorted(array_values($out));
    }

    private static function price(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $value = trim((string) $value);
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
            return null;
        }
        $n = (int) floor((float) $value);

        return $n <= self::MAX_PRICE ? $n : null;
    }

    private static function sorted(array $list): array
    {
        $list = array_values(array_unique($list));
        sort($list);

        return $list;
    }
}
