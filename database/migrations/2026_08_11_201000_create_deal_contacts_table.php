<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['deal_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_contacts');
    }
};
