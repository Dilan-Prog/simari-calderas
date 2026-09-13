<?php

namespace App\Support;

/**
 * Convierte el JSON de estilo tipográfico que el editor en vivo guarda por
 * bloque (`config.<campo>_style`) en la etiqueta HTML y el atributo
 * `style="..."` reales, validando cada valor -- nunca se confía en el JSON
 * guardado tal cual dentro de un atributo HTML.
 */
class TextStyle
{
    public const ALLOWED_TAGS = ['h1', 'h2', 'h3', 'h4', 'p'];

    private const ALLOWED_ALIGN = ['left', 'center', 'right'];

    private const MIN_FONT_SIZE = 10;
    private const MAX_FONT_SIZE = 72;

    /**
     * Familias soportadas: 'Predeterminada' (vacío/null, hereda de
     * variables.css) o 'Inter Tight' -- ver service-page/show.blade.php
     * para dónde se inyecta condicionalmente su <link> de Google Fonts
     * (nunca se carga en el público si ningún bloque la usa).
     */
    private const FONT_STACKS = [
        'Inter Tight' => "'Inter Tight', var(--font-family), sans-serif",
    ];

    /**
     * Etiqueta HTML a usar para un encabezado, restringida a las
     * permitidas por ese bloque ($allowed) y con fallback a $default si el
     * valor guardado no es válido o no fue elegido.
     */
    public static function tag(?array $style, string $default, array $allowed = self::ALLOWED_TAGS): string
    {
        $tag = $style['heading_tag'] ?? null;

        return in_array($tag, $allowed, true) ? $tag : $default;
    }

    /**
     * Atributo `style="..."` (con el espacio inicial incluido) listo para
     * insertarse en un tag, o cadena vacía si no hay nada válido que
     * aplicar.
     */
    public static function attr(?array $style): string
    {
        if (!$style) {
            return '';
        }

        $decl = [];

        if (in_array($style['text_align'] ?? null, self::ALLOWED_ALIGN, true)) {
            $decl[] = 'text-align:' . $style['text_align'];
        }

        if (!empty($style['text_color']) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $style['text_color'])) {
            $decl[] = 'color:' . $style['text_color'];
        }

        if (!empty($style['font_size']) && is_numeric($style['font_size'])) {
            $size = max(self::MIN_FONT_SIZE, min(self::MAX_FONT_SIZE, (int) $style['font_size']));
            $decl[] = 'font-size:' . $size . 'px';
        }

        $family = self::FONT_STACKS[$style['font_family'] ?? ''] ?? null;
        if ($family) {
            $decl[] = 'font-family:' . $family;
        }

        return $decl ? ' style="' . e(implode(';', $decl)) . '"' : '';
    }

    /**
     * true si algún bloque de $sections usa una familia que requiere cargar
     * su propia hoja de Google Fonts en el público (ver uso en
     * service-page/show.blade.php).
     */
    public static function needsFontFamily(iterable $sections, string $family): bool
    {
        foreach ($sections as $section) {
            foreach ((array) ($section->config ?? []) as $key => $value) {
                if (str_ends_with((string) $key, '_style') && is_array($value) && ($value['font_family'] ?? null) === $family) {
                    return true;
                }
            }
        }

        return false;
    }
}
