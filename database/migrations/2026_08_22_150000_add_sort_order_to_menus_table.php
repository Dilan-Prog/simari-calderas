<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controla en qué orden aparecen las columnas del footer cuando varios
 * Menús comparten la ubicación genérica 'footer' (ver
 * 2026_08_22_150001_normalize_footer_menu_locations.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
