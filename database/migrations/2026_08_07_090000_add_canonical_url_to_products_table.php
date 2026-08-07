<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // URL canónica manual, para cuando dos o más productos son muy
            // parecidos entre sí (mismo equipo, distinta presentación/SKU) y
            // se quiere que Google indexe uno solo como el "original".
            // Nullable: la mayoría de los productos no la necesitan y usan
            // su propia URL como canonical (ver product/show.blade.php).
            $table->string('canonical_url', 255)->nullable()->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('canonical_url');
        });
    }
};
