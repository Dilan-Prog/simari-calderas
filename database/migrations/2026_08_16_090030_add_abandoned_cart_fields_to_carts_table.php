<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('customer_id');
            $table->timestamp('checkout_started_at')->nullable()->after('last_activity_at');
            $table->string('contact_name', 150)->nullable()->after('checkout_started_at');
            $table->string('contact_email', 150)->nullable()->after('contact_name');
            $table->string('contact_phone', 30)->nullable()->after('contact_email');
            $table->foreignId('converted_to_store_order_id')->nullable()->after('contact_phone')
                ->constrained('store_orders')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable()->after('converted_to_store_order_id');

            $table->index('last_activity_at');
            $table->index('checkout_started_at');
        });

        // Backfill last_activity_at desde la última actividad conocida en cart_items.
        // Carritos sin items quedan con last_activity_at NULL: no hay nada que recuperar ahí.
        DB::statement(<<<SQL
            UPDATE carts c
            JOIN (SELECT cart_id, MAX(updated_at) AS last_item_activity FROM cart_items GROUP BY cart_id) ci
              ON ci.cart_id = c.id
            SET c.last_activity_at = ci.last_item_activity
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['converted_to_store_order_id']);
            $table->dropColumn([
                'last_activity_at',
                'checkout_started_at',
                'contact_name',
                'contact_email',
                'contact_phone',
                'converted_to_store_order_id',
                'dismissed_at',
            ]);
        });
    }
};
