<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Parámetros del catálogo ya validados/normalizados (WP-1 implementa los
 * métodos; esto es solo el CONTRATO congelado).
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
        throw new \LogicException('CatalogParams::fromRequest lo implementa WP-1.');
    }

    /** Query-string normalizado (sin defaults), listo para http_build_query. */
    public function toQuery(): array
    {
        throw new \LogicException('CatalogParams::toQuery lo implementa WP-1.');
    }

    /** Cuántos filtros hay activos (marcas, precio, disp, envío, técnicos, q, legado). */
    public function activeCount(): int
    {
        throw new \LogicException('CatalogParams::activeCount lo implementa WP-1.');
    }
}
