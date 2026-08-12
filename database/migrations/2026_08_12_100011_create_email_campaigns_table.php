<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('name');
            $table->foreignId('list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, scheduled, sending, sent, paused
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('ab_variant_of')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->unsignedTinyInteger('ab_split_percent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
