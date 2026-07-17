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
        // The 'model' column has existed on some environments (this local
        // dev DB included) since before migrations were the source of
        // truth for this table — it was added by hand and never had a
        // matching migration. Production never got it, which is exactly
        // why an import/save that sets it throws
        // "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'model'".
        // Guarded so this stays a no-op wherever the column already exists.
        if (!Schema::hasColumn('products', 'model')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('model', 100)->nullable()->after('sku');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'model')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('model');
            });
        }
    }
};
