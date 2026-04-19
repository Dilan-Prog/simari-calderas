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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status', 30);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();
 
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('changed_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_status_history');
    }
};
