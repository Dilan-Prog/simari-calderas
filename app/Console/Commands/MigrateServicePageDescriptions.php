<?php

namespace App\Console\Commands;

use App\Models\ServicePage;
use Illuminate\Console\Command;

class MigrateServicePageDescriptions extends Command
{
    protected $signature = 'service-pages:migrate-descriptions {--dry-run}';

    protected $description = 'Migra el textarea plano "description" de cada ServicePage a una sección content_tabs (rediseño 2026-09). Idempotente: no duplica si ya existe una sección content_tabs.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $servicePages = ServicePage::whereNotNull('description')
            ->where('description', '!=', '')
            ->with('sections')
            ->get();

        $toMigrate = $servicePages->filter(
            fn (ServicePage $sp) => !$sp->sections->contains(fn ($s) => $s->type === 'content_tabs')
        );

        $this->info("{$toMigrate->count()} de {$servicePages->count()} servicios con descripción serán migrados.");

        if ($dryRun) {
            $toMigrate->each(fn (ServicePage $sp) => $this->line(" - #{$sp->id} {$sp->name}"));
            $this->info('Dry run: no se guardó ningún cambio.');

            return self::SUCCESS;
        }

        $toMigrate->each(function (ServicePage $sp) {
            $sp->sections()->create([
                'type'   => 'content_tabs',
                'title'  => null,
                'config' => [
                    'tabs' => [[
                        'label'    => 'Descripción',
                        'subtitle' => '',
                        'body'     => $sp->description,
                        'bullets'  => [],
                        'image_id' => null,
                    ]],
                ],
                'sort_order' => $sp->sections->count(),
                'is_active'  => true,
            ]);
            $this->line("Migrado #{$sp->id} {$sp->name}");
        });

        $this->info('Listo. La columna "description" original no se borró — queda como respaldo de solo lectura.');

        return self::SUCCESS;
    }
}
