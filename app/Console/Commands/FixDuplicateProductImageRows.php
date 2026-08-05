<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cleans up ProductImage rows sharing the exact same (product_id,
 * image_url) pair — the pre-fix leftover shape from
 * ImageReferenceService::rewriteReference() before its "product_image" case
 * started deleting the losing row instead of updating it into a duplicate.
 * Without --apply this dry-runs (same detection as images:audit-duplicate-
 * product-rows). With --apply, for each duplicated group keeps the row with
 * the lowest sort_order (tie-break: lowest id) and deletes the rest, inside
 * a transaction. Never touches physical files — the surviving row still
 * points at the same URL, so nothing becomes orphaned.
 */
class FixDuplicateProductImageRows extends Command
{
    protected $signature   = 'images:fix-duplicate-product-rows {--apply : Actually delete the redundant rows}';
    protected $description = 'Limpia filas ProductImage duplicadas (mismo producto + misma URL) dejadas por consolidaciones anteriores.';

    public function handle(): int
    {
        $groups = ProductImage::select('product_id', 'image_url', DB::raw('COUNT(*) as cnt'))
            ->groupBy('product_id', 'image_url')
            ->having('cnt', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No se encontraron filas ProductImage duplicadas. Nada que limpiar.');
            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $deleted = 0;

        foreach ($groups as $group) {
            $images = ProductImage::where('product_id', $group->product_id)
                ->where('image_url', $group->image_url)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $keep = $images->first();
            $toDelete = $images->skip(1);

            $this->line("Producto #{$group->product_id} / {$group->image_url}: conservar fila {$keep->id}, borrar [" . $toDelete->pluck('id')->implode(', ') . ']');

            if (!$apply) {
                $deleted += $toDelete->count();
                continue;
            }

            DB::transaction(function () use ($toDelete) {
                foreach ($toDelete as $img) {
                    $img->delete();
                }
            });

            $deleted += $toDelete->count();
        }

        $this->newLine();
        $this->info($apply
            ? "{$deleted} fila(s) duplicada(s) eliminadas."
            : "{$deleted} fila(s) se eliminarían (dry-run, nada escrito). Corre con --apply para aplicar.");

        return self::SUCCESS;
    }
}
