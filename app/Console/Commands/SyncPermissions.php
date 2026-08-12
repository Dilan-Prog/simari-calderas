<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature   = 'permissions:sync';
    protected $description = 'Sincroniza los permisos de la BD con los módulos definidos en config/modules.php';

    /**
     * Acciones granulares soportadas por módulo.
     */
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'log'];

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
            foreach (self::ACTIONS as $action) {
                $permission = Permission::firstOrCreate(
                    [
                        'module' => $key,
                        'action' => $action,
                    ],
                    [
                        'name' => $this->label($action) . ' ' . $module['name'],
                    ]
                );

                if ($permission->wasRecentlyCreated) {
                    $this->line("  <fg=green>✓ Creado:</> {$module['name']} ({$key}.{$action})");
                    $created++;
                } else {
                    $this->line("  <fg=gray>· Existe:</> {$module['name']} ({$key}.{$action})");
                    $existing++;
                }
            }
        }

        $this->newLine();
        $this->info("✅ Sincronización completa.");
        $this->line("   Creados:   {$created}");
        $this->line("   Existentes: {$existing}");
        $this->line("   Total:     " . ($created + $existing));
    }

    private function label(string $action): string
    {
        return match ($action) {
            'view'   => 'Ver',
            'create' => 'Crear',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
            'log'    => 'Ver bitácora de',
            default  => ucfirst($action),
        };
    }
}
