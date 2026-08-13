<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el tipo de canal de un Pipeline: 'deals' (Negocios, comportamiento
 * actual) o 'whatsapp' (Embudo de Venta de conversaciones de WhatsApp, Fase
 * 12). Los pipelines existentes quedan todos en 'deals' por el default, sin
 * cambio de comportamiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->string('channel')->default('deals')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
