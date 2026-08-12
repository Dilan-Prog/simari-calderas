<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_send_id')->constrained('email_sends')->cascadeOnDelete();
            $table->text('url');
            $table->dateTime('clicked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_link_clicks');
    }
};
