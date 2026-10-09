<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Enlaces en línea dentro de textos escritos por el admin, con la sintaxis
 * `[texto](destino)`. El texto se guarda tal cual en los campos existentes
 * (sin migración); solo al renderizar se convierte en <a>.
 *
 * Destinos permitidos: ruta relativa del sitio (`/algo`, nunca `//` ni `/\`)
 * o `http(s)://`. Cualquier otro esquema (javascript:, data:, ...) se deja
 * como texto plano escapado.
 */
class LinkText
{
    // Etiqueta: sin corchetes. Destino: sin espacios ni ")".
    private const PATTERN = '/\[([^\[\]]+)\]\(((?:\/(?![\/\x5C])|https?:\/\/)[^\s)]*)\)/i';

    /**
     * HTML seguro (imprimir con {!! !!}): escapa todo primero y luego
     * convierte los enlaces válidos. Sin enlaces, equivale a e($text).
     */
    public static function render(?string $text): HtmlString|string
    {
        if ($text === null || $text === '') {
            return new HtmlString('');
        }

        $escaped = e($text);

        $html = preg_replace_callback(self::PATTERN, function (array $m) {
            // $m ya viene escapado (e()), por lo que es seguro en HTML/atributo.
            $external = (bool) preg_match('#^https?://#i', $m[2]);
            $attrs = $external ? ' target="_blank" rel="noopener"' : '';

            return '<a href="' . $m[2] . '"' . $attrs . '>' . $m[1] . '</a>';
        }, $escaped);

        return new HtmlString($html ?? $escaped);
    }

    /**
     * Quita el marcado y deja solo las etiquetas. Texto sin escapar: usar
     * con {{ }} (aria-label, title, alt, meta) o dentro de otro <a>/<button>.
     */
    public static function plain(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return preg_replace_callback(self::PATTERN, fn (array $m) => $m[1], $text) ?? $text;
    }
}
