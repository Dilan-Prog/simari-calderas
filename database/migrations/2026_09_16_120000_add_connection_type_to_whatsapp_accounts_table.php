<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para una segunda vía de conexión de WhatsApp ("baileys_qr",
 * microservicio Node.js aparte) junto a la ya existente "meta_cloud_api" —
 * ver app/Services/WhatsappBaileysService.php. `session_id` identifica la
 * sesión ante el microservicio (único, nullable porque las cuentas
 * meta_cloud_api no lo usan); `session_status` refleja el último estado
 * reportado (qr_pending|connected|disconnected). Ninguno de los dos es
 * secreto, así que no se cifran (a diferencia de access_token/app_secret).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->string('connection_type')->default('meta_cloud_api')->after('provider');
            $table->string('session_id')->nullable()->unique()->after('connection_type');
            $table->string('session_status')->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['connection_type', 'session_id', 'session_status']);
        });
    }
};
