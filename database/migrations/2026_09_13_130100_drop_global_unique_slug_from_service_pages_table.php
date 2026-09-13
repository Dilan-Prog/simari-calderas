<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La migración base (2026_07_31_100000) dejó `slug` con unicidad
     * GLOBAL — eso impediría reutilizar el mismo slug hoja (ej.
     * "diagnostico") bajo distintas categorías (/servicios/calderas/
     * diagnostico y /servicios/calentadores/diagnostico), que es justo el
     * punto de la arquitectura de 3 niveles. La unicidad ahora se valida en
     * el controlador, con alcance (scoped) a parent_id.
     */
    public function up(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
        Schema::table('service_pages', function (Blueprint $table) {
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });
        Schema::table('service_pages', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
