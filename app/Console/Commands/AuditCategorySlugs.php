<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Products;
use App\Services\Catalog\CategorySlugs;
use Illuminate\Console\Command;

/**
 * Auditoría de solo lectura: para cada categoría con padre calcula el slug que DEBERÍA tener (la ruta completa
 * con todos sus ancestros, "padre/hija") y marca las que difieren del guardado. No escribe nada;
 * categories:apply-slug-fixes aplica los cambios (también los aplica la migración de reparación de slugs).
 */
class AuditCategorySlugs extends Command
{
    protected $signature   = 'categories:audit-slugs {--json : Also dump the raw rows as JSON}';
    protected $description = 'Detecta categorías cuyo slug no refleja su jerarquía padre/hijo real (solo lectura, no modifica nada).';

    public function handle(): int
    {
        $all = Category::orderBy('sort_order')->orderBy('name')->get()->keyBy('id');
        $plan = CategorySlugs::plan();

        if (empty($plan)) {
            $this->info('No se encontraron discrepancias. Todos los slugs reflejan su jerarquía.');

            return self::SUCCESS;
        }

        $rows = collect($plan)->map(function ($c) use ($all) {
            $cat = $all->get($c['id']);
            $parent = $cat?->parent_id ? $all->get($cat->parent_id) : null;

            return [
                'id'            => $c['id'],
                'name'          => $c['name'],
                'level'         => $cat?->level,
                'current_slug'  => $c['from'],
                'parent_slug'   => $parent?->slug,
                'proposed_slug' => $c['to'],
                'product_count' => Products::where('category_id', $c['id'])->count(),
            ];
        })->all();

        $this->table(
            ['ID', 'Nombre', 'Nivel', 'Slug actual', 'Slug del padre', 'Slug propuesto', '# Productos'],
            collect($rows)->map(fn ($r) => [$r['id'], $r['name'], $r['level'], $r['current_slug'], $r['parent_slug'] ?? '—', $r['proposed_slug'], $r['product_count']])
        );

        $this->newLine();
        $this->warn(count($rows) . ' categoría(s) con slug desalineado detectadas.');
        $this->warn('Este comando NO modifica nada. Revisa la tabla y aplica con categories:apply-slug-fixes --confirm.');

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
