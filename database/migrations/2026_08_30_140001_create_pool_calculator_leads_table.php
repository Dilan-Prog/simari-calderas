<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_calculator_leads', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 10)->unique();

            // Nullable: un visitante orgánico sin gclid/wbraid/gbraid igual
            // puede usar la calculadora y generar un lead válido, solo sin
            // atribución de anuncio que reportar después.
            $table->uuid('visitor_uuid')->nullable();
            $table->foreign('visitor_uuid')->references('visitor_uuid')->on('ad_visits')->nullOnDelete();

            // Qué instancia de la sección lo generó (una Colección/ServicePage
            // podría tener más de una calculadora en el futuro).
            $table->foreignId('home_section_id')->nullable()->constrained('home_sections')->nullOnDelete();

            // Snapshot completo de los datos capturados + el resultado ya
            // calculado (área/m3/BTU/modelo recomendado/rango de costo) —
            // así el admin puede auditar el lead sin tener que re-derivarlo.
            $table->json('payload');

            $table->string('status', 20)->default('nuevo');

            // Vínculo manual que hace ventas cuando cierra la venta que llegó
            // con este Ref — es lo que permite, después, tirar del hilo
            // visitor_uuid -> ad_visits.gclid para reportar la conversión.
            $table->foreignId('matched_quote_id')->nullable()->constrained('quotes')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_calculator_leads');
    }
};
