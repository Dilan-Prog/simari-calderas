<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texto alternativo (ALT) editable para las imágenes que hasta ahora no lo tenían:
 *  - Productos: portada y imagen Open Graph (la galería ya tiene product_images.alt_text).
 *  - Colecciones: imagen principal y OG.
 *  - Páginas de servicio: portada y OG (la galería ya tiene service_page_images.alt_text).
 *  - Marcas: logotipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('cover_image_alt', 255)->nullable()->after('cover_image_url');
            $table->string('og_image_alt', 255)->nullable()->after('og_image');
        });
        Schema::table('collections', function (Blueprint $table) {
            $table->string('image_alt', 255)->nullable()->after('image_url');
            $table->string('og_image_alt', 255)->nullable()->after('og_image_url');
        });
        Schema::table('service_pages', function (Blueprint $table) {
            $table->string('cover_image_alt', 255)->nullable()->after('cover_image_url');
            $table->string('og_image_alt', 255)->nullable()->after('og_image_url');
        });
        Schema::table('brands', function (Blueprint $table) {
            $table->string('logo_alt', 255)->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['cover_image_alt', 'og_image_alt']));
        Schema::table('collections', fn (Blueprint $t) => $t->dropColumn(['image_alt', 'og_image_alt']));
        Schema::table('service_pages', fn (Blueprint $t) => $t->dropColumn(['cover_image_alt', 'og_image_alt']));
        Schema::table('brands', fn (Blueprint $t) => $t->dropColumn('logo_alt'));
    }
};
