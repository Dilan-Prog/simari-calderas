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
        Schema::create('email_list_subscribers', function (Blueprint $table) {
            $table->unsignedBigInteger('email_list_id');
            $table->unsignedBigInteger('subscriber_id');
            $table->primary(['email_list_id', 'subscriber_id']);
            $table->foreign('email_list_id')->references('id')->on('email_lists')->onDelete('cascade');
            $table->foreign('subscriber_id')->references('id')->on('email_subscribers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_list_subscribers');
    }
};
