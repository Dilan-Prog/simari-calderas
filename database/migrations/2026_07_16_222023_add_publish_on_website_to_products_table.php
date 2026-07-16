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
            // Independiente de is_active: is_active controla el estado
            // general del producto (inventario/uso interno), este campo
            // controla su visibilidad en el futuro catálogo público del
            // sitio web. Cuando ese catálogo exista, mostrará un producto
            // solo si publish_on_website = 1 AND is_active = 1.
            $table->boolean('publish_on_website')->default(false)->after('is_recommended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('publish_on_website');
        });
    }
};
