<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_uuid')->unique();

            // First-touch: se escriben UNA sola vez al crear la fila, nunca
            // se sobreescriben en visitas posteriores del mismo visitor_uuid.
            $table->string('first_gclid')->nullable();
            $table->string('first_wbraid')->nullable();
            $table->string('first_utm_source')->nullable();
            $table->string('first_utm_medium')->nullable();
            $table->string('first_utm_campaign')->nullable();
            $table->string('first_utm_content')->nullable();
            $table->string('first_utm_term')->nullable();
            $table->string('first_utm_matchtype')->nullable();
            $table->string('first_landing_url', 2048)->nullable();
            $table->timestamp('first_seen_at');

            // Last-touch: se sobreescriben cada vez que una visita posterior
            // trae parámetros de ad nuevos en la URL de llegada.
            $table->string('gclid')->nullable()->index();
            $table->string('wbraid')->nullable()->index();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_matchtype')->nullable();
            $table->string('landing_url', 2048)->nullable();
            $table->timestamp('last_seen_at');

            // Ventana de 90 días de Google para subir conversiones offline,
            // contada desde el ÚLTIMO touch (se extiende con cada visita
            // nueva con parámetros de ad).
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_visits');
    }
};
