<?php

namespace App\Console\Commands;

use App\Http\Resources\Mobile\ServiceReportDetailResource;
use App\Models\ServiceReport;
use App\Support\UploadPath;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

/**
 * Diagnóstico de SOLO LECTURA para la app móvil en producción:
 *   php artisan mobile:check
 *
 * Revisa migraciones, APP_URL/https, URL firmada del PDF (generar y validar),
 * límites de PHP para subir fotos, extensiones, carpetas con permiso de
 * escritura, rate limits y caché de config/rutas. No crea ni modifica datos.
 *
 * Ojo: los valores de PHP que ve este comando son los de la CLI. El servidor
 * web puede usar otro php.ini; para esos, ver phpinfo en el panel del hosting.
 */
class MobileCheck extends Command
{
    protected $signature = 'mobile:check';

    protected $description = 'Revisa que producción esté lista para la app móvil (solo lectura)';

    private int $fails = 0;

    private int $warns = 0;

    private const MIGRATIONS = [
        '2026_11_22_200000_add_mobile_capture_fields_to_service_reports_table',
        '2026_11_22_200100_add_startup_service_report_type',
        '2026_11_22_300000_add_idempotency_key_to_services_table',
    ];

    public function handle(): int
    {
        $this->line('<options=bold>Diagnóstico de la app móvil</> (' . app()->environment() . ')');

        $this->section('Aplicación');
        $appUrl = (string) config('app.url');
        $this->check(str_starts_with($appUrl, 'https://'), "APP_URL = {$appUrl}", 'APP_URL debe empezar con https://');
        $this->check(! config('app.debug') || ! app()->environment('production'), 'APP_DEBUG desactivado en producción', 'APP_DEBUG=true en producción: desactívalo', true);
        $root = url('/');
        $this->check(str_starts_with($root, 'https://'), "URLs generadas con https ({$root})", 'Las URLs se generan con http: revisa APP_ENV=production y APP_URL https');
        $proxies = (string) config('app.trusted_proxies', '');
        $this->info2('TRUSTED_PROXIES = ' . ($proxies === '' ? '(vacío)' : $proxies) . ' — usa "*" si hay Cloudflare o proxy delante');

        $this->section('Migraciones');
        if (Schema::hasTable('migrations')) {
            $ran = DB::table('migrations')->whereIn('migration', self::MIGRATIONS)->pluck('migration')->all();
            foreach (self::MIGRATIONS as $m) {
                $this->check(in_array($m, $ran, true), $m, "Falta correr: {$m}");
            }
        } else {
            $this->check(false, '', 'No existe la tabla migrations');
        }
        $this->check(Schema::hasColumn('service_reports', 'idempotency_key'), 'service_reports.idempotency_key', 'Falta la columna service_reports.idempotency_key');
        $this->check(Schema::hasColumn('services', 'idempotency_key'), 'services.idempotency_key', 'Falta la columna services.idempotency_key');
        try {
            // Tipo REAL en MySQL (Doctrine reporta los ENUM como "string").
            $type = DB::getDriverName() === 'mysql'
                ? strtolower((string) DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'service_reports')
                    ->where('COLUMN_NAME', 'service_type')
                    ->value('COLUMN_TYPE'))
                : strtolower((string) Schema::getColumnType('service_reports', 'service_type'));
            $this->check(! str_contains($type, 'enum'), "service_reports.service_type es {$type}", 'service_type sigue siendo ENUM (falta la migración de "Arranque")');
        } catch (\Throwable $e) {
            $this->warn2('No se pudo leer el tipo de service_reports.service_type: ' . $e->getMessage());
        }

        $this->section('URL firmada del PDF');
        $this->check(Route::has('mobile.v1.reportes.pdf'), 'Ruta mobile.v1.reportes.pdf registrada', 'No existe la ruta del PDF (¿route:cache viejo?)');
        $reportId = Schema::hasTable('service_reports') ? ServiceReport::query()->value('id') : null;
        if ($reportId && Route::has('mobile.v1.reportes.pdf')) {
            $pdfUrl = ServiceReportDetailResource::pdfUrl((int) $reportId);
            $this->check(str_starts_with($pdfUrl, rtrim($appUrl, '/') . '/'), 'pdf_url usa el dominio de APP_URL', "pdf_url no usa APP_URL: {$pdfUrl}");
            // Simula la petición como llegaría detrás de un proxy (http, otro host).
            $parts = parse_url($pdfUrl);
            $req = Request::create('http://servidor-interno.local' . $parts['path'] . '?' . ($parts['query'] ?? ''));
            $this->check(URL::hasValidSignature($req, false), 'La firma valida aunque el proxy cambie esquema/host', 'La firma relativa NO valida');
            $this->info2('Prueba en el navegador (válida 30 min): ' . $pdfUrl);
        } else {
            $this->warn2('No hay reportes para probar la URL firmada');
        }

        $this->section('PHP (CLI) para subir fotos');
        $this->checkSize('upload_max_filesize', 10);
        $this->checkSize('post_max_size', 40);
        $this->check((int) ini_get('max_file_uploads') >= 25, 'max_file_uploads = ' . ini_get('max_file_uploads'), 'max_file_uploads debe ser ≥ 25 (actual ' . ini_get('max_file_uploads') . ')');
        $this->checkSize('memory_limit', 256, true);
        $exec = (int) ini_get('max_execution_time');
        $this->check($exec === 0 || $exec >= 120, "max_execution_time = {$exec}", "max_execution_time recomendado ≥ 120 (actual {$exec})", true);
        $this->info2('PHP ' . PHP_VERSION . ' · ini: ' . (php_ini_loaded_file() ?: '(ninguno)'));

        $this->section('Extensiones');
        foreach (['gd', 'zip', 'mbstring', 'fileinfo'] as $ext) {
            $this->check(extension_loaded($ext), "{$ext} cargada", "Falta la extensión {$ext}");
        }
        if (function_exists('gd_info')) {
            $gd = gd_info();
            $this->check(! empty($gd['JPEG Support']), 'GD con JPEG', 'GD sin soporte JPEG');
            $this->check(! empty($gd['PNG Support']), 'GD con PNG (firma)', 'GD sin soporte PNG');
            $this->check(! empty($gd['WebP Support']), 'GD con WebP', 'GD sin WebP (solo afecta fotos .webp)', true);
        }

        $this->section('Carpetas');
        $uploads = rtrim(UploadPath::base(), '/');
        $this->check(is_writable($uploads), "Subidas: {$uploads} (escritura)", "Sin permiso de escritura en {$uploads}");
        $sr = $uploads . '/service-reports';
        if (is_dir($sr)) {
            $this->check(is_writable($sr), "{$sr} (escritura)", "Sin permiso de escritura en {$sr}");
        } else {
            $this->info2("{$sr} aún no existe (se crea con la primera foto)");
        }
        foreach ([storage_path(), storage_path('logs'), storage_path('framework/cache'), base_path('bootstrap/cache')] as $dir) {
            $this->check(is_writable($dir), "{$dir} (escritura)", "Sin permiso de escritura en {$dir}");
        }
        $this->info2('Las fotos se sirven por ' . rtrim($appUrl, '/') . '/media/… (no requiere storage:link)');

        $this->section('API y tokens');
        $this->check(Schema::hasTable('personal_access_tokens'), 'Tabla personal_access_tokens', 'Falta la tabla personal_access_tokens (Sanctum)');
        foreach (['mobile', 'mobile-login'] as $limiter) {
            $this->check(RateLimiter::limiter($limiter) !== null, "Rate limit '{$limiter}' registrado", "No está registrado el rate limit '{$limiter}'");
        }
        $this->info2('Config en caché: ' . (app()->configurationIsCached() ? 'sí' : 'no') . ' · Rutas en caché: ' . (app()->routesAreCached() ? 'sí' : 'no'));
        $this->info2('La app nativa usa token Bearer: no necesita CORS ni cookies.');

        $this->newLine();
        if ($this->fails) {
            $this->error("Resultado: {$this->fails} error(es), {$this->warns} aviso(s).");

            return self::FAILURE;
        }
        $this->info("Resultado: todo listo ({$this->warns} aviso(s)).");

        return self::SUCCESS;
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line("<options=bold>{$title}</>");
    }

    private function check(bool $ok, string $okText, string $failText, bool $warnOnly = false): void
    {
        if ($ok) {
            $this->line("  <fg=green>✔</> {$okText}");

            return;
        }
        if ($warnOnly) {
            $this->warn2($failText);

            return;
        }
        $this->fails++;
        $this->line("  <fg=red>✘ {$failText}</>");
    }

    private function warn2(string $text): void
    {
        $this->warns++;
        $this->line("  <fg=yellow>! {$text}</>");
    }

    private function info2(string $text): void
    {
        $this->line("  <fg=gray>· {$text}</>");
    }

    private function checkSize(string $key, int $minMb, bool $warnOnly = false): void
    {
        $raw = (string) ini_get($key);
        $bytes = $this->toBytes($raw);
        $ok = $bytes < 0 || $bytes >= $minMb * 1024 * 1024;
        $this->check($ok, "{$key} = {$raw}", "{$key} debe ser ≥ {$minMb}M (actual {$raw})", $warnOnly);
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $n = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
