<?php

namespace App\Support;

class TextLinks
{
    /**
     * Convierte la sintaxis `[texto](url)` escrita por el admin (en
     * respuestas de FAQ) en un <a> real, escapando todo lo demás para que
     * el resto del texto nunca se interprete como HTML. Solo se permiten
     * URLs relativas o http(s) — cualquier otro esquema (ej. javascript:)
     * se deja como texto plano.
     */
    public static function render(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $escaped = e($text);

        return preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $m) {
                $url = html_entity_decode($m[2]);

                if (!preg_match('#^(https?://|/)#i', $url)) {
                    return $m[0];
                }

                return '<a href="' . e($url) . '">' . $m[1] . '</a>';
            },
            $escaped
        );
    }
}
