<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature   = 'permissions:sync';
    protected $description = 'Sincroniza los permisos de la BD con los módulos definidos en config/modules.php';

    public function handle(): void
    {
        $modules = config('modules');

        if (empty($modules)) {
            $this->error('No se encontraron módulos en config/modules.php');
            return;
        }

        $this->info('Sincronizando permisos...');
        $this->newLine();

        $created = 0;
        $existing = 0;

        foreach ($modules as $key => $module) {
            $permission = Permission::firstOrCreate(
                [
                    'module' => $key,
                    'action' => 'view',
                ],
                [
                    'name' => 'Ver ' . $module['name'],
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $this->line("  <fg=green>✓ Creado:</> {$module['name']} ({$key})");
                $created++;
            } else {
                $this->line("  <fg=gray>· Existe:</> {$module['name']} ({$key})");
                $existing++;
            }
        }

        $this->newLine();
        $this->info("✅ Sincronización completa.");
        $this->line("   Creados:   {$created}");
        $this->line("   Existentes: {$existing}");
        $this->line("   Total:     " . ($created + $existing));
    }
}
