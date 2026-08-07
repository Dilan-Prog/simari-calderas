<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_bulk_edit_views', function (Blueprint $table) {
            $table->json('widths')->nullable()->after('columns');
        });
    }

    public function down(): void
    {
        Schema::table('brand_bulk_edit_views', function (Blueprint $table) {
            $table->dropColumn('widths');
        });
    }
};
