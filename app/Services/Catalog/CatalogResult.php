<?php

namespace App\Services\Catalog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * CONTRATO CONGELADO entre el servicio (CatalogQuery, WP-1) y las vistas
 * (WP-2). Las vistas SOLO leen este DTO: cada faceta, chip y opción de orden
 * ya trae su `href` (URL del estado resultante de alternarla, sin `page`), así
 * que las vistas nunca arman URLs. NO cambiar esta forma sin avisar a ambos.
 *
 * ── paginator ──────────────────────────────────────────────────────────────
 * LengthAwarePaginator<Products> con `perPage` 24, ya con eager loading
 * (brand, category, images) y `withQueryString()`.
 *
 * ── total ──────────────────────────────────────────────────────────────────
 * int: resultados con TODOS los filtros activos (== paginator->total()).
 *
 * ── facets ─────────────────────────────────────────────────────────────────
 * [
 *   'categories' => [
 *      'current' => ['name' => string, 'count' => int] | null,   // null en /catalogo
 *      'back'    => ['label' => string, 'url' => string] | null, // "‹ Padre" / "‹ Todas las categorías"
 *      'items'   => [ ['id'=>int,'name'=>string,'url'=>string,'count'=>int], ... ],
 *   ],
 *   'brands' => [ ['id'=>int,'name'=>string,'count'=>int,'selected'=>bool,'href'=>string,
 *                  'inputName'=>'marca[]','value'=>int], ... ],            // ya ordenadas por count desc
 *   'price' => [
 *      'min' => int, 'max' => int,                       // límites del alcance de categoría (MXN sin IVA)
 *      'ranges' => [ ['label'=>string,'min'=>?int,'max'=>?int,'count'=>int,'active'=>bool,'href'=>string], ... ],
 *      'selected' => ['min' => ?int, 'max' => ?int],
 *      'formAction' => string,                           // URL base sin precio_min/precio_max/page
 *      'hidden' => [ 'nombre' => 'valor', ... ],         // resto de parámetros activos (para el <form> Desde/Hasta)
 *   ],
 *   'availability' => [ ['key'=>'stock'|'24_48h','label'=>string,'count'=>int,'selected'=>bool,'href'=>string,
 *                        'inputName'=>'disp[]','value'=>string], ... ],
 *   'free_shipping' => ['label'=>string,'count'=>int,'selected'=>bool,'href'=>string,
 *                       'inputName'=>'envio_gratis','value'=>'1'],
 *   'tech' => [ ['id'=>int,'name'=>string,
 *                'options'=>[ ['id'=>int,'label'=>string,'count'=>int,'selected'=>bool,'href'=>string,
 *                              'inputName'=>"f[<groupId>][]",'value'=>int], ... ] ], ... ],
 *   'maxVisible' => int,                                  // catalog.filter_max_visible (6)
 * ]
 * Reglas ya aplicadas por el servicio: opciones con count 0 NO vienen (salvo
 * las seleccionadas), grupos sin opciones visibles no vienen, el orden dentro
 * de cada grupo ya es el final de pantalla (más conteo primero; las
 * seleccionadas fuera del top siguen presentes). Las vistas muestran las
 * primeras `maxVisible` y el resto detrás de "Mostrar más".
 *
 * ── chips ──────────────────────────────────────────────────────────────────
 * [ ['label'=>string,'removeHref'=>string], ... ]  (marca, precio, disponibilidad,
 * envío gratis, opción técnica, búsqueda q)
 * clearHref: string  — URL con todos los filtros quitados (conserva orden).
 *
 * ── sortOptions ────────────────────────────────────────────────────────────
 * [ ['key'=>'relevancia'|'descuento'|'precio_asc'|'precio_desc'|'vendidos'|'nuevos',
 *    'label'=>string,'href'=>string,'selected'=>bool], ... ]
 *
 * ── meta ───────────────────────────────────────────────────────────────────
 * [
 *   'page' => int, 'pages' => int,
 *   'activeCount' => int,            // filtros activos (para "Filtros (N)")
 *   'noindex' => bool,               // variantes filtradas/ordenadas/q/page fuera de rango
 *   'canonicalUrl' => string,        // URL limpia (o ?page=N autorreferenciada)
 *   'stateUrl' => string,            // URL del estado normalizado (para history)
 *   'liveMessage' => string,         // "167 resultados" / "1 resultado" / "Sin resultados"
 * ]
 *
 * ── params ─────────────────────────────────────────────────────────────────
 * CatalogParams ya normalizados (por si una vista necesita el valor de `q`).
 */
class CatalogResult
{
    public function __construct(
        public LengthAwarePaginator $paginator,
        public int $total,
        public array $facets,
        public array $chips,
        public string $clearHref,
        public array $sortOptions,
        public array $meta,
        public CatalogParams $params,
    ) {
    }
}
