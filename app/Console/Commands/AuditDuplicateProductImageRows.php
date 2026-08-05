<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit: finds ProductImage rows that share the exact same
 * (product_id, image_url) pair — i.e. the same product's gallery showing
 * the identical photo more than once. This is the pre-fix shape left behind
 * by ImageReferenceService::rewriteReference() before it started deleting
 * the losing row instead of updating it into a duplicate (see the
 * "product_image" case). Does not write anything — images:fix-duplicate-
 * product-rows is the gated follow-up command that actually cleans this up.
 */
class AuditDuplicateProductImageRows extends Command
{
    protected $signature   = 'images:audit-duplicate-product-rows';
    protected $description = 'Detecta filas ProductImage duplicadas (mismo producto + misma URL) dejadas por consolidaciones anteriores (solo lectura, no modifica nada).';

    public function handle(): int
    {
        $groups = ProductImage::select('product_id', 'image_url', DB::raw('COUNT(*) as cnt'))
            ->groupBy('product_id', 'image_url')
            ->having('cnt', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No se encontraron filas ProductImage duplicadas dentro de un mismo producto.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($groups as $group) {
            $images = ProductImage::with('product:id,name,sku')
                ->where('product_id', $group->product_id)
                ->where('image_url', $group->image_url)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $keep = $images->first();
            $product = $keep->product;

            $rows[] = [
                'product'   => $product ? "{$product->name} ({$product->sku})" : "producto #{$group->product_id}",
                'image_url' => $group->image_url,
                'count'     => $group->cnt,
                'keep_id'   => $keep->id,
                'delete_ids' => $images->skip(1)->pluck('id')->implode(', '),
            ];
        }

        $this->table(
            ['Producto', 'Image URL', '# Filas', 'Fila a conservar', 'Filas a borrar'],
            collect($rows)->map(fn ($r) => [$r['product'], $r['image_url'], $r['count'], $r['keep_id'], $r['delete_ids']])
        );

        $this->newLine();
        $this->warn(count($rows) . ' grupo(s) de imagen duplicada dentro de un mismo producto detectados.');
        $this->warn('Este comando NO modifica nada. Revisa la tabla y corre images:fix-duplicate-product-rows --apply para limpiarlos.');

        return self::SUCCESS;
    }
}
