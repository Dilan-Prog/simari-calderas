<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Garantía del producto (texto libre, ej. "12 meses contra defectos
            // de fábrica"). Si está vacía, la ficha pública no muestra la
            // sección "Garantía y servicio".
            $table->text('warranty_text')->nullable()->after('lead_time_text');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('warranty_text');
        });
    }
};
