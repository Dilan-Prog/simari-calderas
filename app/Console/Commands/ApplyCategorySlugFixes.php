<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Redirect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Applies the hierarchical slug fixes identified by categories:audit-slugs.
 * Requires --confirm — meant to run only after the user has explicitly
 * approved that command's output for their real dataset. Always records the
 * 301 redirect for the old URL BEFORE changing the slug, inside a
 * transaction, so a change is never made without its redirect already in
 * place.
 */
class ApplyCategorySlugFixes extends Command
{
    protected $signature   = 'categories:apply-slug-fixes {--dry-run : Show what would change without writing} {--confirm : Required to actually write}';
    protected $description = 'Aplica los fixes de slug jerárquico detectados por categories:audit-slugs, registrando el redirect 301 antes de cambiar cada slug.';

    public function handle(): int
    {
        if (!$this->option('dry-run') && !$this->option('confirm')) {
            $this->error('Este comando modifica datos. Corre primero con --dry-run para revisar, y usa --confirm solo después de aprobar la tabla de categories:audit-slugs.');
            return self::FAILURE;
        }

        $all = Category::orderBy('sort_order')->orderBy('name')->get()->keyBy('id');

        $expectedFullSlug = function (Category $cat) use ($all, &$expectedFullSlug) {
            $ownSegment = Str::slug(Str::afterLast($cat->slug, '/'));
            if (!$cat->parent_id || !$all->has($cat->parent_id)) {
                return $ownSegment;
            }
            return $expectedFullSlug($all->get($cat->parent_id)) . '/' . $ownSegment;
        };

        $changed = 0;

        // Parents before children: fixing a level-2 category first means its
        // level-3 children get cascaded+saved automatically by Category's
        // saved() hook, so by the time this loop reaches them their slug
        // already matches — avoids the same category being redirected twice.
        foreach ($all->sortBy(fn ($c) => $c->level) as $cat) {
            if (!$cat->parent_id) {
                continue;
            }

            $expected = $expectedFullSlug($cat);
            if ($expected === $cat->slug) {
                continue;
            }

            $oldPath = '/catalogo/' . $cat->slug;
            $newPath = '/catalogo/' . $expected;

            $this->line("{$cat->name}: {$cat->slug}  ->  {$expected}");

            if ($this->option('dry-run')) {
                $changed++;
                continue;
            }

            DB::transaction(function () use ($cat, $expected, $oldPath, $newPath) {
                Redirect::record($oldPath, $newPath);
                $cat->slug = $expected;
                $cat->save();
            });

            $changed++;
        }

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "{$changed} cambio(s) detectados (dry-run, nada escrito)."
            : "{$changed} categoría(s) actualizadas, con su redirect 301 ya registrado.");

        return self::SUCCESS;
    }
}
