<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas de servicio para integraciones externas (N8N, etc.), separadas
     * de `users` a propósito: `users` modela empleados reales (RFC, CURP,
     * seguro social, NOT NULL) y no tiene sentido forzar un registro de
     * "persona" para un cliente API. `ApiClient` usa Sanctum\HasApiTokens
     * igual que User -- personal_access_tokens es polimórfico (tokenable_type/id),
     * así que auth:sanctum resuelve $request->user() a un ApiClient sin tocar
     * config/auth.php ni el guard 'sanctum'.
     */
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
