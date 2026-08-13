<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->string('initials', 4)->nullable()->after('code');
            $table->string('bg_color', 7)->nullable()->after('initials');
            $table->string('text_color', 7)->nullable()->after('bg_color');
            $table->string('meta_line', 120)->nullable()->after('text_color');
        });

        DB::table('carriers')->updateOrInsert(['code' => 'estafeta'], [
            'name' => 'Estafeta',
            'initials' => 'ES',
            'bg_color' => '#fdecec',
            'text_color' => '#c81e1e',
            'meta_line' => 'Terrestre · 55 5270 8300',
            'tracking_url_template' => 'https://rastreo.estafeta.com/RastreoWebInternet/consultaEnvio.do?wayBill=',
            'is_active' => true,
        ]);

        DB::table('carriers')->updateOrInsert(['code' => 'dhl'], [
            'name' => 'DHL Express',
            'initials' => 'DH',
            'bg_color' => '#fef8e3',
            'text_color' => '#8a6d1f',
            'meta_line' => 'Express · 800 765 6345',
            'tracking_url_template' => 'https://www.dhl.com/mx-es/home/tracking.html?tracking-id=',
            'is_active' => true,
        ]);

        DB::table('carriers')->updateOrInsert(['code' => 'fedex'], [
            'name' => 'FedEx',
            'initials' => 'FX',
            'bg_color' => '#f1ebfd',
            'text_color' => '#6d28d9',
            'meta_line' => 'Express · 800 900 1100',
            'tracking_url_template' => 'https://www.fedex.com/fedextrack/?trknbr=',
            'is_active' => true,
        ]);

        DB::table('carriers')->updateOrInsert(['code' => 'paquetex'], [
            'name' => 'Paquetexpress',
            'initials' => 'PX',
            'bg_color' => '#e8f2fb',
            'text_color' => '#0f6fbd',
            'meta_line' => 'Terrestre · 800 378 7222',
            'tracking_url_template' => 'https://www.paquetexpress.com.mx/rastreo?guia=',
            'is_active' => true,
        ]);

        DB::table('carriers')->updateOrInsert(['code' => 'castores'], [
            'name' => 'Transportes Castores',
            'initials' => 'CA',
            'bg_color' => '#f1f2f4',
            'text_color' => '#4b5563',
            'meta_line' => 'Carga pesada · 800 227 8673',
            'tracking_url_template' => 'https://www.castores.com.mx/rastreo?guia=',
            'is_active' => true,
        ]);

        DB::table('carriers')->updateOrInsert(['code' => 'tresguerras'], [
            'name' => 'Tres Guerras',
            'initials' => 'TG',
            'bg_color' => '#e6f6ee',
            'text_color' => '#0f7a4f',
            'meta_line' => 'Fletes · 800 837 4700',
            'tracking_url_template' => 'https://www.tresguerras.com.mx/rastreo?guia=',
            'is_active' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->dropColumn(['initials', 'bg_color', 'text_color', 'meta_line']);
        });
    }
};
