<?php

namespace App\Support;

class TextLinks
{
    /**
     * Respuestas de FAQ escritas por el admin: mismo formato que el resto de
     * bloques (enlaces `[texto](url)` en color de marca, `**negritas**` y
     * viñetas con "- "). Delegado a LinkText para que la gramática sea una
     * sola; todo lo demás se escapa.
     */
    public static function render(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return (string) LinkText::render($text);
    }
}
