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
        Schema::create('service_technicians', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('role_verified')->default(false);
            $table->primary(['service_id', 'user_id']);
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_technicians');
    }
};
