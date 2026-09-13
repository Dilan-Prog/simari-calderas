<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reseñas individuales reales, curadas a mano por el staff (igual
        // criterio que las FAQs de ServicePage) — son la ÚNICA fuente para el
        // JSON-LD AggregateRating/Review. Las estadísticas de marketing
        // (promedio mostrado, total calificados, distribución, etc.) viven
        // aparte, como columnas en service_pages (ver migración siguiente),
        // precisamente para no mezclar copy editable con datos verificables.
        Schema::create('service_page_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_page_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_role')->nullable();
            $table->string('customer_company')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->date('review_date')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 240);
            $table->json('categories')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->json('photo_urls')->nullable();
            $table->text('business_response')->nullable();
            $table->date('business_response_date')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_page_reviews');
    }
};
