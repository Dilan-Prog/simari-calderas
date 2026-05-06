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
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('action', 50);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('performed_by_user_id')->nullable();
            $table->timestamp('created_at');
 
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('performed_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
