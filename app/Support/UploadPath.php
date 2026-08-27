<?php

namespace App\Support;

/**
 * Central place resolving where uploaded files (product images, service
 * report photos, product documents, media picker uploads) live on disk and
 * how they're served publicly. Backed by UPLOADS_BASE_PATH (via
 * config('uploads.base_path'), see config/uploads.php) so it can point
 * outside the deployed app tree in production — Hostinger's git auto-deploy
 * resets public_html to match the pushed commit on every push, which wipes
 * anything under public/ that isn't tracked in git (uploaded files, since
 * they're gitignored). When UPLOADS_BASE_PATH isn't set (local dev), it
 * falls back to public_path() so local behavior is unchanged.
 *
 * FIX (bug real encontrado): antes leía env('UPLOADS_BASE_PATH') directo
 * aquí — fuera de un archivo de config, eso deja de funcionar en cuanto el
 * servidor corre `php artisan config:cache` (Laravel dice explícitamente
 * que env() no debe usarse fuera de config/*.php por esto). Con config
 * cacheado, esta llamada devolvía null AUNQUE el .env tuviera el valor
 * correcto — cayendo en silencio a public_path(), justo la carpeta que el
 * deploy de Hostinger sí borra. Root cause de que documentos/imágenes
 * subidos recientemente dieran 404 al descargarlos.
 */
class UploadPath
{
    public static function base(): string
    {
        return config('uploads.base_path') ?: public_path();
    }

    /**
     * FIX (bug real encontrado): antes de la migración de almacenamiento
     * (commit 7b2f026, "Move uploaded images/documents outside public_html
     * to survive deploys") los archivos subidos vivían bajo public_path().
     * Esa migración cambió dónde se BUSCAN los archivos hacia
     * UploadPath::base(), pero nunca copió los archivos ya subidos a la
     * nueva ubicación -- así que cualquier producto/reporte de servicio
     * creado antes de ese cambio apunta a un archivo que solo existe en la
     * ubicación vieja. Si no está en la ubicación actual, se revisa ahí
     * como fallback de compatibilidad antes de rendirse.
     */
    public static function full(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $primary = static::base() . '/' . $relativePath;

        if (!is_file($primary) && static::base() !== public_path()) {
            $legacy = public_path($relativePath);
            if (is_file($legacy)) {
                return $legacy;
            }
        }

        return $primary;
    }

    public static function url(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        if (str_starts_with($relativePath, 'http')) {
            return $relativePath;
        }

        return url('media/' . ltrim($relativePath, '/'));
    }
}
