<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Limpieza de datos (no de esquema): al retirar el sitio estático viejo
 * (App\Http\Controllers\Frontend\HomeController, ver plan "Retirar el sitio
 * estático viejo") se borraron sus rutas, pero el menú de navegación
 * (tablas menus/menu_items) guarda URLs literales sembradas una vez por
 * MenusSeeder — no se recalculan solas. Sin esto, los enlaces del header y
 * footer seguirían apuntando a páginas que ya no existen (404 al hacer clic).
 *
 * No se tocan las filas `menus` (padres) — una entrada de menú sin hijos
 * simplemente no renderiza nada, no es un error.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Filas hoja cuyo url apunta a una ruta ya eliminada.
        DB::table('menu_items')->where(function ($q) {
            $q->where('url', '/nuestra-empresa')
                ->orWhere('url', '/contacto')
                ->orWhere('url', '/servicios-calderas')
                ->orWhere('url', '/desincrustacion-calderas')
                ->orWhere('url', 'like', '/servicios/%')
                ->orWhere('url', 'like', '/productos/%')
                ->orWhere('url', 'like', '/masstercal-rinnai/%');
        })->delete();

        // 2) Encabezados de grupo del mega-menú "Servicios" (menu_items sin
        // url propio, solo agrupan hijos) que se quedaron sin ningún hijo
        // tras el paso 1 — ej. "Calderas", "Tratamiento de Agua". MySQL no
        // permite borrar de una tabla mientras se subconsulta esa misma
        // tabla en el mismo statement (error 1093) — se resuelve en 2 pasos:
        // primero materializar los IDs candidatos, luego borrar por ID.
        $orphanedGroupIds = DB::table('menu_items')
            ->whereNull('url')
            ->whereNull('parent_id')
            ->pluck('id')
            ->filter(function ($id) {
                return !DB::table('menu_items')->where('parent_id', $id)->exists();
            })
            ->values();

        if ($orphanedGroupIds->isNotEmpty()) {
            DB::table('menu_items')->whereIn('id', $orphanedGroupIds)->delete();
        }
    }

    public function down(): void
    {
        // Limpieza de datos, no reversible con certeza (no se conservan los
        // valores borrados) — re-sembrar vía MenusSeeder si hace falta.
    }
};
