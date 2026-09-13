<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arquitectura de 3 niveles bajo /servicios/ (hub → categoría → servicio),
     * reutilizando ServicePage para los 3 niveles en vez de crear modelos
     * nuevos — así el editor en vivo, los bloques, SEO, rating, etc. ya
     * construidos funcionan igual sin importar el nivel.
     *
     * page_type: 'hub' | 'category' | 'service'. parent_id es auto-
     * referenciado (null = nivel 1, o servicio "plano" legacy servido por
     * /servicio/{slug}).
     *
     * La unicidad de slug por nivel se valida en el controlador (scoped a
     * parent_id) — no se usa un índice único compuesto porque MySQL trata
     * cada NULL como distinto en un unique index, lo que no protegería el
     * nivel 1 (parent_id NULL) contra slugs duplicados.
     */
    public function up(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->string('page_type', 20)->default('service')->after('slug');
            $table->foreignId('parent_id')->nullable()->after('page_type')
                ->constrained('service_pages')->nullOnDelete();
            $table->index(['parent_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['page_type', 'parent_id']);
        });
    }
};
