<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Aplica las credenciales de Mercado Pago guardadas en el módulo admin de
 * Integraciones (tabla settings, grupo 'integraciones') sobre
 * services.mercadopago.{rol}.*. El .env queda como respaldo: si no hay valor
 * guardado en BD para alguna clave, esa clave en particular no se toca
 * (calco de MailSettingsServiceProvider).
 *
 * Soporta N "roles" (config('services.mercadopago.roles'), hoy 'api' y
 * 'checkout_pro') -- cada uno es una Aplicación distinta del panel de
 * desarrolladores de Mercado Pago, con su propio Public Key/Access Token, y
 * dentro de cada rol, 2 juegos de credenciales (sandbox/live) —
 * mercadopago.{rol}.mode decide cuál de los 2 se aplica de verdad al
 * checkout para ESE rol específico. El admin puede tener sandbox y live
 * guardados a la vez por rol (para ir probando mientras prepara producción)
 * sin que se pisen entre sí.
 */
class MercadoPagoSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            foreach (config('services.mercadopago.roles', []) as $role) {
                $this->applyRole($role);
            }
        } catch (\Throwable) {
            // BD no disponible (instalación, migraciones, CI): se usa el .env.
        }
    }

    private function applyRole(string $role): void
    {
        $mode = Setting::get("mercadopago.{$role}.mode", 'sandbox');
        $mode = in_array($mode, ['sandbox', 'live'], true) ? $mode : 'sandbox';

        config(["services.mercadopago.{$role}.mode" => $mode]);

        $accessToken = Setting::get("mercadopago.{$role}.{$mode}_access_token");
        if ($accessToken) {
            try {
                $accessToken = Crypt::decryptString($accessToken);
            } catch (DecryptException) {
                $accessToken = null;
            }
        }

        if (filled($accessToken)) {
            config(["services.mercadopago.{$role}.access_token" => $accessToken]);
        }

        $publicKey = Setting::get("mercadopago.{$role}.{$mode}_public_key");
        if (filled($publicKey)) {
            config(["services.mercadopago.{$role}.public_key" => $publicKey]);
        }

        // A diferencia del mode/access_token/public_key, el webhook secret
        // SÍ es por rol (Mercado Pago lo asigna por Aplicación, no por
        // juego sandbox/live dentro de la misma aplicación).
        $webhookSecret = Setting::get("mercadopago.{$role}.webhook_secret");
        if ($webhookSecret) {
            try {
                $webhookSecret = Crypt::decryptString($webhookSecret);
            } catch (DecryptException) {
                $webhookSecret = null;
            }
        }

        if (filled($webhookSecret)) {
            config(["services.mercadopago.{$role}.webhook_secret" => $webhookSecret]);
        }
    }
}
