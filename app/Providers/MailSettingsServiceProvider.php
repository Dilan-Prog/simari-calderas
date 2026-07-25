<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Aplica la configuración SMTP guardada en el módulo admin de
 * Integraciones (tabla settings, grupo 'integraciones') sobre la
 * configuración de mail. El .env queda como respaldo: si no hay host
 * guardado en BD, no se toca nada.
 */
class MailSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $host = Setting::get('mail.host');

            if (blank($host)) {
                return;
            }

            $encryption = Setting::get('mail.encryption', 'ssl');

            $password = Setting::get('mail.password');
            if ($password) {
                try {
                    $password = Crypt::decryptString($password);
                } catch (DecryptException) {
                    $password = null;
                }
            }

            config([
                'mail.default'               => 'smtp',
                'mail.mailers.smtp.host'     => $host,
                'mail.mailers.smtp.port'     => (int) (Setting::get('mail.port') ?: 587),
                'mail.mailers.smtp.username' => Setting::get('mail.username'),
                'mail.mailers.smtp.password' => $password,
                // Laravel/Symfony Mailer: 'smtps' = SSL implícito (puerto 465);
                // 'encryption: tls' = STARTTLS (puerto 587); scheme smtp = sin cifrado.
                'mail.mailers.smtp.scheme'     => $encryption === 'ssl' ? 'smtps' : ($encryption === 'none' ? 'smtp' : null),
                'mail.mailers.smtp.encryption' => $encryption === 'tls' ? 'tls' : null,
            ]);

            if ($from = Setting::get('mail.from_address')) {
                config(['mail.from.address' => $from]);
            }

            if ($fromName = Setting::get('mail.from_name')) {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Throwable) {
            // BD no disponible (instalación, migraciones, CI): se usa el .env.
        }
    }
}
