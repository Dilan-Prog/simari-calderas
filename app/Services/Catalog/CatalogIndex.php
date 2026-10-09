<?php

namespace App\Services\Catalog;

/**
 * Filas candidatas del catálogo en caché (WP-1 lo implementa).
 *
 * get() devuelve:
 * [
 *   'v'      => string  (CatalogCache::version() con la que se construyó),
 *   'rows'   => [ [ 'id'=>int, 'c'=>?int (category_id), 'b'=>?int (brand_id),
 *                   'p'=>float (base_price MXN sin IVA), 'cp'=>?float (compare_base),
 *                   'd'=>?int (% descuento, solo si compare>base),
 *                   'st'=>bool (en stock), 'f24'=>bool (available y stock>0),
 *                   'fg'=>bool (envío gratis: shippingInfo cost<=0),
 *                   'ls'=>bool (últimas piezas), 'nw'=>bool (nuevo),
 *                   'ts'=>int (created_at unix), 'feat'=>bool, 'u'=>int (unidades vendidas),
 *                   'o'=>int[] (ids de opciones de filtro técnico que cumple) ], ... ],
 *   'cats'   => [ id => ['id','parent_id','name','slug','sort_order','is_active'] ],
 *   'groups' => [ groupId => ['id','category_id','name','options'=>[ ['id','label','tag_normalized'] ]] ],
 *   'brands' => [ id => ['id','name'] ],
 * ]
 * Solo productos publicados (is_active y publish_on_website). Precio/envío/
 * disponibilidad salen de los accessors de Products (fuente única de verdad).
 */
class CatalogIndex
{
    public static function get(): array
    {
        throw new \LogicException('CatalogIndex::get lo implementa WP-1.');
    }
}
