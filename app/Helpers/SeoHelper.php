<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Genera el canonical URL completo
     */
    public static function canonical(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Limpia y trunca el title (máx 60 caracteres)
     */
    public static function title(string $title, string $suffix = 'Industria Simari'): string
    {
        $full = $title . ' - ' . $suffix;
        return mb_substr(trim($full), 0, 60);
    }

    /**
     * Limpia y trunca la meta description (máx 160 caracteres)
     */
    public static function description(string $description): string
    {
        return mb_substr(trim($description), 0, 160);
    }

    /**
     * Genera el og:image completo
     */
    public static function ogImage(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Genera el array SEO completo para una página
     */
    public static function generate(array $data): array
    {
        return [
            'title'          => self::title($data['title'] ?? ''),
            'description'    => self::description($data['description'] ?? ''),
            'canonical'      => self::canonical($data['path'] ?? '/'),
            'og_title'       => self::title($data['og_title'] ?? $data['title'] ?? ''),
            'og_description' => self::description($data['og_description'] ?? $data['description'] ?? ''),
            'og_url'         => self::canonical($data['path'] ?? '/'),
            'og_image'       => self::ogImage($data['og_image'] ?? 'images/og-default.jpg'),
            'schema'         => $data['schema'] ?? null,
        ];
    }
}