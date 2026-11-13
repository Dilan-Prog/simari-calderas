<?php

namespace App\Console\Commands;

use App\Services\Catalog\CategorySlugs;
use Illuminate\Console\Command;

/**
 * Aplica los fixes de slug jerárquico detectados por categories:audit-slugs.
 * Requiere --confirm (o --dry-run para revisar). Registra el redirect 301 de la URL vieja ANTES de cambiar
 * cada slug, dentro de una transacción, y procesa padres antes que hijos. La lógica vive en
 * App\Services\Catalog\CategorySlugs (la misma que usa el admin de Categorías y la migración de reparación).
 */
class ApplyCategorySlugFixes extends Command
{
    protected $signature   = 'categories:apply-slug-fixes {--dry-run : Show what would change without writing} {--confirm : Required to actually write}';
    protected $description = 'Aplica los fixes de slug jerárquico detectados por categories:audit-slugs, registrando el redirect 301 antes de cambiar cada slug.';

    public function handle(): int
    {
        if (! $this->option('dry-run') && ! $this->option('confirm')) {
            $this->error('Este comando modifica datos. Corre primero con --dry-run para revisar, y usa --confirm solo después de aprobar la tabla de categories:audit-slugs.');

            return self::FAILURE;
        }

        $result = CategorySlugs::repairAll((bool) $this->option('dry-run'));

        foreach ($result['changed'] as $c) {
            $this->line("{$c['name']}: {$c['from']}  ->  {$c['to']}");
        }
        foreach ($result['conflicts'] as $id => $wanted) {
            $this->warn("Conflicto (id {$id}): el slug «{$wanted}» ya lo usa otra categoría; se dejó sin cambiar.");
        }

        $n = count($result['changed']);
        $this->newLine();
        $this->info($this->option('dry-run')
            ? "{$n} cambio(s) detectados (dry-run, nada escrito)."
            : "{$n} categoría(s) actualizadas, con su redirect 301 ya registrado.");

        return self::SUCCESS;
    }
}
