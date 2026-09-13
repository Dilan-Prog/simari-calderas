<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estadísticas agregadas de MARKETING, editables a mano por el staff en
     * la pestaña "Rating y reseñas" — deliberadamente separadas de
     * service_page_reviews (las reseñas individuales reales). Estos campos
     * NUNCA deben alimentar el JSON-LD AggregateRating: ese marcado debe
     * derivarse solo del promedio/conteo real de reseñas visibles, para no
     * violar las políticas de datos estructurados de Google (no se puede
     * anunciar "128 calificados" en schema.org si solo hay 6 reseñas
     * verificables). Ver frontend.shop.service-page.show para la regla real.
     */
    public function up(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->decimal('rating_average_displayed', 3, 2)->nullable()->after('faqs');
            $table->unsignedInteger('rating_total_rated')->nullable()->after('rating_average_displayed');
            $table->json('rating_distribution')->nullable()->after('rating_total_rated');
            $table->decimal('rating_recommend_percent', 5, 2)->nullable()->after('rating_distribution');
            $table->decimal('rating_punctuality_average', 3, 2)->nullable()->after('rating_recommend_percent');
            $table->unsignedInteger('rating_recurring_clients')->nullable()->after('rating_punctuality_average');
            $table->unsignedSmallInteger('rating_since_year')->nullable()->after('rating_recurring_clients');
        });
    }

    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropColumn([
                'rating_average_displayed',
                'rating_total_rated',
                'rating_distribution',
                'rating_recommend_percent',
                'rating_punctuality_average',
                'rating_recurring_clients',
                'rating_since_year',
            ]);
        });
    }
};
