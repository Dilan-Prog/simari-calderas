<?php

namespace App\Services\Erp;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Navegación del ERP: módulos visibles según permisos, tiles del Launchpad,
 * KPIs vivos y búsqueda global (módulos + documentos por folio).
 *
 * Fuentes: config/erp_nav.php (módulos nuevos), config/modules.php (permisos),
 * config/erp_documents.php (tipos de documento con folio).
 */
class ErpNavigation
{
    /** Módulos de config/erp_nav.php visibles para el usuario y con ruta ya implementada. */
    public function items(User $user): array
    {
        return collect(config('erp_nav.items', []))
            ->filter(fn ($i) => Route::has($i['route']) && $this->can($user, $i['permission']))
            ->map(fn ($i) => $i + ['url' => route($i['route'])])
            ->values()
            ->all();
    }

    /** Agrupados por área, con la etiqueta del área. */
    public function tilesFor(User $user): array
    {
        $areas = config('erp_nav.areas', []);
        $grouped = [];
        foreach ($this->items($user) as $item) {
            $grouped[$item['area']][] = $item;
        }

        $out = [];
        foreach ($areas as $key => $label) {
            if (!empty($grouped[$key])) {
                $out[] = ['key' => $key, 'label' => $label, 'items' => $grouped[$key]];
            }
        }

        return $out;
    }

    /**
     * KPIs vivos del launchpad. Cada módulo puede aportar el suyo registrando una
     * clase con método estático kpi(): ?array{value:string|int,label:string,tone:string}
     * en la clave 'kpi' de su entrada de config/erp_nav.php.
     *
     * @return array<string, array{value:mixed,label:string,tone:string}>  indexado por permission
     */
    public function kpis(User $user): array
    {
        $out = [];
        foreach ($this->items($user) as $item) {
            $class = $item['kpi'] ?? null;
            if (!$class || !class_exists($class) || !method_exists($class, 'kpi')) {
                continue;
            }
            try {
                $kpi = $class::kpi();
            } catch (\Throwable $e) {
                $kpi = null;
            }
            if ($kpi) {
                $out[$item['permission']] = $kpi + ['tone' => 'neutral'];
            }
        }

        return $out;
    }

    public function searchModules(User $user, string $q): array
    {
        $needle = mb_strtolower($q);
        $all = array_merge($this->items($user), $this->legacyItems($user));

        return collect($all)
            ->filter(fn ($i) => $needle === '' || str_contains(mb_strtolower($i['label']), $needle))
            ->map(fn ($i) => ['label' => $i['label'], 'url' => $i['url']])
            ->take(12)
            ->values()
            ->all();
    }

    /** Documentos cuyo folio contiene $q (máx. 5 por tipo), solo de tipos con ruta y permiso. */
    public function searchDocuments(User $user, string $q): array
    {
        $like = '%' . addcslashes($q, '\\%_') . '%';
        $out = [];

        foreach (config('erp_documents', []) as $key => $def) {
            if (!class_exists($def['model']) || !$def['route'] || !Route::has($def['route'])) {
                continue;
            }
            $model = new $def['model'];
            if (!Schema::hasTable($model->getTable()) || !Schema::hasColumn($model->getTable(), $def['number'])) {
                continue;
            }
            foreach ($def['model']::where($def['number'], 'like', $like)->orderByDesc('id')->limit(5)->get() as $m) {
                $out[] = [
                    'type'   => $def['label'],
                    'number' => (string) $m->{$def['number']},
                    'status' => $def['status'] ? ($m->{$def['status']} ?? null) : null,
                    'url'    => route($def['route'], $m->getKey()),
                ];
            }
        }

        return array_slice($out, 0, 15);
    }

    /** Módulos existentes (config/modules.php) con ruta válida, para que Ctrl+K también salte a ellos. */
    private function legacyItems(User $user): array
    {
        $newKeys = collect(config('erp_nav.items', []))->pluck('permission')->all();

        return collect(config('modules', []))
            ->reject(fn ($m, $key) => in_array($key, $newKeys, true))
            ->filter(fn ($m, $key) => Route::has($m['route']) && $this->can($user, $key))
            ->map(fn ($m) => ['label' => $m['name'], 'url' => route($m['route'])])
            ->values()
            ->all();
    }

    private function can(User $user, string $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission($permission);
    }
}
