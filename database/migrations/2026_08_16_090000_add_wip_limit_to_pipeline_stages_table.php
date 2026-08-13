<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 14 del plan CRM: límite opcional de negocios simultáneos por etapa,
 * usado por la alerta "Límite WIP excedido" del rediseño de Negocios.
 * `NULL` = sin límite (comportamiento actual, sin cambios).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->unsignedInteger('wip_limit')->nullable()->after('probability');
        });
    }

    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('wip_limit');
        });
    }
};
