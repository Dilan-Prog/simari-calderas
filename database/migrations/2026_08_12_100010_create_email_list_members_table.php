<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->dateTime('added_at');
            $table->timestamps();
            $table->unique(['list_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_list_members');
    }
};
