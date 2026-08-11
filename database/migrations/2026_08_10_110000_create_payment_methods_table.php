<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTA IMPORTANTE (descubierto durante el desarrollo de este módulo,
     * no en el estado "verificado" original): ya existía una migración
     * huérfana previa, `2026_04_19_172755_create_payment_methods_table`,
     * que creó `payment_methods` con un esquema totalmente distinto
     * (name/code/provider/description/is_active/config_json) y sin
     * ningún modelo/controller/vista que la referenciara en todo el repo
     * — dead code. El plan original de esta migración era
     * dropIfExists()+create(), pero para cuando se ejecutó, el módulo de
     * Carrito/Checkout (construido en paralelo por otro agente) ya había
     * migrado `orders` con una FK viva `orders_payment_method_id_foreign`
     * → `payment_methods.id`. Dropear la tabla ya no es seguro (rompe esa
     * FK), así que esta migración ALTERA la tabla existente en vez de
     * recrearla: quita las columnas del esquema viejo no usado
     * (code/provider/description/config_json + sus índices únicos) y
     * agrega las columnas del esquema real de este módulo, preservando
     * `id` (y por tanto la FK entrante) intacto. Sin datos ni filas en
     * `payment_methods` ni `orders` al momento de escribir esto — no hay
     * pérdida de información real.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropUnique('payment_methods_name_unique');
            $table->dropUnique('payment_methods_code_unique');
            $table->dropColumn(['code', 'provider', 'description', 'config_json']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('type', 30)->default('transferencia')->after('id');
            $table->string('bank_name', 100)->nullable()->after('is_active');
            $table->string('clabe', 18)->nullable()->after('bank_name');
            $table->string('account_number', 30)->nullable()->after('clabe');
            $table->string('beneficiary_name', 150)->nullable()->after('account_number');
            $table->json('details')->nullable()->after('beneficiary_name');
            $table->unsignedInteger('sort_order')->default(0)->after('details');
        });
    }

    /**
     * Best-effort: revierte a una forma compatible con la migración
     * original (mismas columnas), pero SIN las restricciones únicas de
     * name/code originales — recrearlas aquí podría fallar si para
     * entonces ya existen filas de este módulo con nombres repetidos, y
     * esta tabla es dead code de cualquier forma en ese escenario de
     * rollback.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['type', 'bank_name', 'clabe', 'account_number', 'beneficiary_name', 'details', 'sort_order']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
            $table->string('provider', 100)->nullable()->after('code');
            $table->text('description')->nullable()->after('provider');
            $table->json('config_json')->nullable()->after('is_active');
        });
    }
};
