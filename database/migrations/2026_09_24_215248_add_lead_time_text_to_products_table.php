<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Texto libre (ej. "5-7 días hábiles") que solo aplica cuando
            // availability='on_order' -- se muestra junto al badge "Sobre
            // pedido" en la ficha del producto para que el cliente sepa
            // cuánto tardaría la entrega.
            $table->string('lead_time_text', 150)->nullable()->after('availability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('lead_time_text');
        });
    }
};
