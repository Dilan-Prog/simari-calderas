<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloques dinámicos por producto. Reutiliza HomeSection como entidad de
     * bloque (mismos tipos/partials/config que Home, Colecciones y
     * Servicios) y agrega:
     *   - page = 'product_template': plantilla reutilizable (ligada) que se
     *     asigna a N productos.
     *   - page = 'product_custom': sección propia de UN producto.
     *   - page = 'product' (legado): sección GLOBAL de la página de
     *     producto; se deja de renderizar (ver config/shop.php).
     *   - zone: 'stack' (pila a todo el ancho bajo el producto) o 'sidebar'
     *     (columna lateral, arriba de "Medios de pago").
     *   - heading_link: enlace del encabezado {type,id,url,new_tab}.
     */
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('title');
            $table->string('zone', 10)->default('stack')->after('page');
            $table->json('heading_link')->nullable()->after('config');
        });

        Schema::create('product_section_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('home_section_id')->constrained('home_sections')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'home_section_id']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_section_assignments');

        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn(['name', 'zone', 'heading_link']);
        });
    }
};
