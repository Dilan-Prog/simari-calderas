<?php

namespace App\Services\Catalog;

use App\Models\Category;

/**
 * Punto de entrada del listado (WP-1 lo implementa). Filtra, calcula facetas
 * en una sola pasada sobre CatalogIndex::get() y devuelve el DTO
 * CatalogResult (ver su docblock: es el contrato con las vistas).
 */
class CatalogQuery
{
    /**
     * @param ?Category $scope  categoría de la ruta (null = /catalogo completo)
     * @param string    $baseUrl URL base para armar los href (ruta sin query), ej. route('catalog.category', $slug)
     */
    public function run(CatalogParams $params, ?Category $scope, string $baseUrl, int $perPage = 24): CatalogResult
    {
        throw new \LogicException('CatalogQuery::run lo implementa WP-1.');
    }
}
