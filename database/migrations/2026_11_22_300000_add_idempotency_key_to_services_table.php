<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotencia de POST /api/mobile/v1/servicios: la app móvil reintenta desde su
 * bandeja de salida con el mismo header Idempotency-Key; la clave única evita
 * que un reintento (o dos envíos simultáneos) creen el servicio dos veces.
 * No se reutiliza draft_token porque el panel lo borra al programar el servicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('draft_token');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
