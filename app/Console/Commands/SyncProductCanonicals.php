<?php

namespace App\Console\Commands;

use App\Support\CanonicalUrls;
use Illuminate\Console\Command;

class SyncProductCanonicals extends Command
{
    protected $signature = 'products:sync-canonicals {--dry-run : Solo muestra qué cambiaría, sin guardar}';

    protected $description = 'Corrige las URLs canónicas de productos que quedaron apuntando a un slug viejo de su producto canónico';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $result = CanonicalUrls::repairAll($dry);

        foreach (array_slice($result['rows'], 0, 15) as $row) {
            $this->line("#{$row['id']} [{$row['kind']}] {$row['from']}  →  {$row['to']}");
        }
        if (count($result['rows']) > 15) {
            $this->line('… y ' . (count($result['rows']) - 15) . ' más');
        }

        $this->info(($dry ? '[dry-run] ' : '') . "Ligados a su canónico: {$result['linked']} · Por redirección de slug: {$result['redirected']}");

        return self::SUCCESS;
    }
}
