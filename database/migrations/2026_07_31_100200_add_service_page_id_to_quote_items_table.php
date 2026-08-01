<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('service_page_id')->nullable()->after('product_id')
                ->constrained('service_pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_page_id');
        });
    }
};
