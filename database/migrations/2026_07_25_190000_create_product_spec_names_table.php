<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo permanente de nombres de especificación (ej. "Voltaje",
        // "BTU") para autocompletar el campo "Nombre del campo" del
        // repeater de especificaciones — persiste aunque ningún producto
        // use ese nombre actualmente (a diferencia de products.tags, que
        // se deriva en vivo y desaparece si nadie lo usa).
        Schema::create('product_spec_names', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_spec_names');
    }
};
