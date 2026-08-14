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
        Schema::table('email_sends', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
            $table->string('guest_email')->nullable()->after('customer_id');
            $table->string('guest_name')->nullable()->after('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_sends', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_name']);
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
