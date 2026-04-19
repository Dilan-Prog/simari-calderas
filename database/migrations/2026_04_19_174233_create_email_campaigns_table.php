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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('email_list_id')->nullable();
            $table->string('name', 150);
            $table->string('subject', 255);
            $table->string('preview_text', 255)->nullable();
            $table->text('content_html')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
 
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('email_list_id')->references('id')->on('email_lists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
