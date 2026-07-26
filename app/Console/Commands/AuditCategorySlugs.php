<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Read-only audit: for every category with a parent_id assigned, computes
 * the slug it SHOULD have (its own segment prefixed by every ancestor's own
 * segment, root-first, joined by "/") and flags it when that differs from
 * the currently stored slug. Does not write anything — categories:apply-slug-fixes
 * is the gated follow-up command that actually applies changes, and only
 * after the user has explicitly approved this command's output.
 */
class AuditCategorySlugs extends Command
{
    protected $signature   = 'categories:audit-slugs {--json : Also dump the raw rows as JSON}';
    protected $description = 'Detecta categorías cuyo slug no refleja su jerarquía padre/hijo real (solo lectura, no modifica nada).';

    public function handle(): int
    {
        $all = Category::orderBy('sort_order')->orderBy('name')->get()->keyBy('id');

        $expectedFullSlug = function (Category $cat) use ($all, &$expectedFullSlug) {
            $ownSegment = Str::slug(Str::afterLast($cat->slug, '/'));
            if (!$cat->parent_id || !$all->has($cat->parent_id)) {
                return $ownSegment;
            }
            return $expectedFullSlug($all->get($cat->parent_id)) . '/' . $ownSegment;
        };

        $rows = [];

        foreach ($all as $cat) {
            if (!$cat->parent_id) {
                continue;
            }

            $expected = $expectedFullSlug($cat);
            if ($expected === $cat->slug) {
                continue;
            }

            $parent = $all->get($cat->parent_id);
            $productCount = Products::where('category_id', $cat->id)->count();

            $brandFlag = null;
            $matchingBrand = Brand::where('name', 'like', '%' . $cat->name . '%')->first();
            if ($matchingBrand) {
                $misassigned = Products::where('brand_id', $matchingBrand->id)
                    ->where('category_id', '!=', $cat->id)
                    ->count();
                if ($misassigned > 0) {
                    $brandFlag = "{$misassigned} producto(s) de marca '{$matchingBrand->name}' NO asignados a esta categoría";
                }
            }

            $rows[] = [
                'id'            => $cat->id,
                'name'          => $cat->name,
                'level'         => $cat->level,
                'current_slug'  => $cat->slug,
                'parent_slug'   => $parent?->slug,
                'proposed_slug' => $expected,
                'product_count' => $productCount,
                'brand_flag'    => $brandFlag,
            ];
        }

        if (empty($rows)) {
            $this->info('No se encontraron discrepancias. Todos los slugs reflejan su jerarquía.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nombre', 'Nivel', 'Slug actual', 'Slug del padre', 'Slug propuesto', '# Productos', 'Alerta de marca'],
            collect($rows)->map(fn ($r) => [
                $r['id'], $r['name'], $r['level'], $r['current_slug'],
                $r['parent_slug'], $r['proposed_slug'], $r['product_count'], $r['brand_flag'] ?? '—',
            ])
        );

        $this->newLine();
        $this->warn(count($rows) . ' categoría(s) con slug desalineado detectadas.');
        $this->warn('Este comando NO modifica nada. Revisa la tabla y aprueba explícitamente antes de ejecutar categories:apply-slug-fixes.');

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
