<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_sections', function (Blueprint $table) {
            $table->string('page', 20)->default('home')->index()->after('type');
        });

        // Secciones iniciales de la página de producto: replican los carruseles
        // que estaban hardcodeados en show.blade.php, para que el deploy no
        // cambie visualmente la página hasta que el admin la edite.
        if (DB::table('home_sections')->where('page', 'product')->doesntExist()) {
            $now = now();

            DB::table('home_sections')->insert([
                [
                    'type'       => 'product_carousel',
                    'page'       => 'product',
                    'title'      => 'También te puede interesar',
                    'config'     => json_encode(['source' => 'related_category', 'limit' => 12]),
                    'sort_order' => 0,
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'type'       => 'product_carousel',
                    'page'       => 'product',
                    'title'      => 'Más vendidos en {categoria}',
                    'config'     => json_encode(['source' => 'related_category', 'limit' => 12]),
                    'sort_order' => 1,
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page', 'product')->delete();

        Schema::table('home_sections', function (Blueprint $table) {
            $table->dropColumn('page');
        });
    }
};
