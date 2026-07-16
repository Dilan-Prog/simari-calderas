<?php

namespace App\Services;

use App\Models\Products;

class ProductSkuGenerator
{
    /**
     * Next available SKU, format ALMC-000N, based on the last product's SKU.
     */
    public static function next(): string
    {
        $lastProduct = Products::orderBy('id', 'desc')->first();
        $nextNumber = $lastProduct ? (intval(substr($lastProduct->sku, 5)) + 1) : 1;

        return 'ALMC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * $count consecutive SKUs starting at the next available one, without
     * touching the database. Used to fill example rows in the import
     * template so they never collide with real products.
     */
    public static function nextBatch(int $count): array
    {
        $lastProduct = Products::orderBy('id', 'desc')->first();
        $start = $lastProduct ? (intval(substr($lastProduct->sku, 5)) + 1) : 1;

        return array_map(
            fn ($n) => 'ALMC-' . str_pad($n, 4, '0', STR_PAD_LEFT),
            range($start, $start + $count - 1)
        );
    }
}
