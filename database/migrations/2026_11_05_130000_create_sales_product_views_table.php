<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vistas personalizadas del panel Ventas > Listado de Productos, por usuario.
     * config = {visible: [claves en orden], order: [todas las claves], widths: {clave: px}, row_height: px}
     */
    public function up(): void
    {
        Schema::create('sales_product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 60);
            $table->json('config');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_product_views');
    }
};
