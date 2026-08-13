<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el soporte mínimo para mensajes de plantilla aprobada, obligatorios
 * fuera de la ventana de 24h de WhatsApp (Fase 11 del plan CRM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('message_type');
            $table->string('template_name', 150)->nullable()->after('is_template');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['is_template', 'template_name']);
        });
    }
};
