<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos que captura la app móvil (POST /api/mobile/v1/reportes) y que el
 * asistente del panel no tiene como campos propios:
 *
 *  - equipment: {tipo, marca, modelo, numero_serie, capacidad, ubicacion}
 *  - readings:  {presion_operacion, temperatura_chimenea, tds, ph} (número o texto)
 *  - materials: [{descripcion, cantidad, unidad}]
 *  - origin:    de dónde salió el reporte ('app_movil'; null = panel)
 *  - captured_at: cuándo se capturó en el teléfono (puede ser horas antes del envío)
 *  - idempotency_key: header Idempotency-Key de la app; unique para que un
 *    reintento (o dos envíos simultáneos) nunca dupliquen el reporte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->json('equipment')->nullable()->after('recommendations');
            $table->json('readings')->nullable()->after('equipment');
            $table->json('materials')->nullable()->after('readings');
            $table->string('origin', 20)->nullable()->after('materials');
            $table->timestamp('captured_at')->nullable()->after('origin');
            $table->string('idempotency_key', 100)->nullable()->unique()->after('captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_reports', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['equipment', 'readings', 'materials', 'origin', 'captured_at', 'idempotency_key']);
        });
    }
};
