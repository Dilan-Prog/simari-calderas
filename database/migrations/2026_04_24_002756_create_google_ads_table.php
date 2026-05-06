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
        Schema::create('google_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('gclid')->index();
            $table->string('conversion_name');
            $table->decimal('conversion_value', 10, 2)->nullable();
            $table->string('currency_code', 3)->default('MXN');
            $table->timestamp('conversion_time');
            $table->string('order_id')->nullable()->unique();
            $table->enum('status', ['pending', 'stored', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_conversions');
    }
};
