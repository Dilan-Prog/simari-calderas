<?php

namespace App\Traits;

use App\Support\CategoryPaths;

/**
 * Columna opcional "Categorías Adicionales" de ProductsImport y
 * ProductsUpdateImport. Requiere RemembersRowNumber (getRowNumber()).
 */
trait ResolvesAdditionalCategories
{
    /**
     * Avisos NO bloqueantes: valores que no coinciden con ninguna categoría
     * (o con varias, si el nombre se repite en distintas ramas). La fila se
     * importa igual, sin esas categorías. [['row' => n, 'sku' => x, 'value' => y], ...]
     */
    public array $additionalCategoryWarnings = [];

    private ?CategoryPaths $additionalCategoryPaths = null;

    /**
     * Celda "A | B | C" -> ids. Cada valor puede ser ruta completa
     * ("Padre > Hijo"), slug o nombre exacto (sin distinguir mayúsculas ni
     * acentos). Los no reconocidos se agregan a $additionalCategoryWarnings.
     *
     * @return int[]
     */
    private function resolveAdditionalCategories(mixed $cell, string $sku): array
    {
        $this->additionalCategoryPaths ??= new CategoryPaths();

        $ids = [];
        foreach (CategoryPaths::splitCell(is_scalar($cell) ? (string) $cell : '') as $value) {
            $id = $this->additionalCategoryPaths->resolve($value);
            if ($id) {
                $ids[] = $id;
            } else {
                $this->additionalCategoryWarnings[] = [
                    'row'   => $this->getRowNumber(),
                    'sku'   => $sku,
                    'value' => $value,
                ];
            }
        }

        return array_values(array_unique($ids));
    }
}
