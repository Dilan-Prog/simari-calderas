<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('tipo_persona', ['fisica', 'moral'])->nullable()->after('rfc');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->enum('tipo_persona', ['fisica', 'moral'])->nullable()->after('rfc');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('tipo_persona');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('tipo_persona');
        });
    }
};
