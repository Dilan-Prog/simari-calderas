<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Envíos B2B: un Shipment deja de ser exclusivo de StoreOrder. Se agrega un
 * "shippable" manual (store_order | sales_order | quote) y los datos de
 * destinatario/direcciones. El flujo de tienda no cambia (store_order_id se
 * sigue llenando para esos envíos).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shipments MODIFY store_order_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('shipments', fn (Blueprint $t) => $t->unsignedBigInteger('store_order_id')->nullable()->change());
        }

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('shippable_type', 20)->nullable()->after('store_order_id');
            $table->unsignedBigInteger('shippable_id')->nullable()->after('shippable_type');
            $table->unsignedBigInteger('customer_id')->nullable()->after('shippable_id');
            $table->string('order_number_ref', 60)->nullable()->after('customer_id');
            $table->string('recipient_name', 160)->nullable()->after('order_number_ref');
            $table->string('recipient_company', 160)->nullable()->after('recipient_name');
            $table->text('origin_address')->nullable()->after('recipient_company');
            $table->text('delivery_address')->nullable()->after('origin_address');
            $table->string('recipient_phone', 40)->nullable()->after('delivery_address');
            $table->string('recipient_email', 160)->nullable()->after('recipient_phone');
            $table->unsignedBigInteger('quote_id')->nullable()->after('recipient_email');
            $table->dateTime('shipped_at')->nullable()->after('quote_id');

            $table->index(['shippable_type', 'shippable_id']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['shippable_type', 'shippable_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn([
                'shippable_type', 'shippable_id', 'customer_id', 'order_number_ref', 'recipient_name',
                'recipient_company', 'origin_address', 'delivery_address', 'recipient_phone',
                'recipient_email', 'quote_id', 'shipped_at',
            ]);
        });
    }
};
