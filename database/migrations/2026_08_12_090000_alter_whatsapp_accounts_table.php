<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adapta el esqueleto huérfano `whatsapp_accounts` (sin modelo/controller
 * detrás) a lo que Meta Cloud API realmente requiere para enviar/recibir
 * mensajes. Se altera la tabla existente, no se recrea (Fase 11 del plan
 * CRM). `api_key` se conserva por compatibilidad de datos pero deja de
 * usarse — el token real vive cifrado en `encrypted_access_token`, mismo
 * patrón que `Credential::storePayload()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->string('phone_number_id', 100)->nullable()->after('phone_number');
            $table->string('whatsapp_business_account_id', 100)->nullable()->after('phone_number_id');
            $table->string('webhook_verify_token', 100)->nullable()->after('whatsapp_business_account_id');
            $table->text('encrypted_access_token')->nullable()->after('webhook_verify_token');
        });

        // `provider` ya existía como string nullable; en vez de alterar la
        // columna (requeriría doctrine/dbal, no instalado en el proyecto),
        // se backfillea el valor real a las filas existentes y el modelo
        // fija 'meta_cloud_api' como default para las nuevas.
        DB::table('whatsapp_accounts')->whereNull('provider')->update(['provider' => 'meta_cloud_api']);
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number_id',
                'whatsapp_business_account_id',
                'webhook_verify_token',
                'encrypted_access_token',
            ]);
        });
    }
};
