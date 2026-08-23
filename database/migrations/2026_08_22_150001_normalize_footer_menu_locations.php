<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de datos (no de esquema): la ubicación 'footer' pasa de tener
 * hasta 3 valores fijos (footer-services/footer-products/footer-company,
 * cada uno máximo un Menú, título hardcodeado en el Blade) a un valor
 * genérico único 'footer' — cualquier Menú activo con esa ubicación se
 * vuelve una columna dinámica del footer público, en el orden de
 * `sort_order`. Preserva name/is_active/items de cada Menú existente, solo
 * cambia el valor de `location`. Si ninguna fila usa los valores viejos
 * hoy, no actualiza nada — no falla.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')
            ->whereIn('location', ['footer-services', 'footer-products', 'footer-company'])
            ->update(['location' => 'footer']);
    }

    public function down(): void
    {
        // Migración de datos, no reversible con certeza (no se conserva cuál
        // ubicación específica tenía cada fila antes del cambio).
    }
};
