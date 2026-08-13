<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 14 del plan CRM: catálogo de etiquetas reutilizable. Se implementa
 * acotado a Deals en esta fase (ver deal_tag más abajo), pero la tabla en
 * sí no referencia nada de Deals para poder reutilizarse a futuro por
 * otros módulos si se necesita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->nullable();
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
