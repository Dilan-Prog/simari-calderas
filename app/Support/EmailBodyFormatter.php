<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Presentación de correos de la bandeja de leads. El texto guardado NO se modifica: solo se
 * decide qué parte se muestra y qué parte va plegada («Ver historial del correo»).
 *
 *  - main:   lo que escribió la persona en este correo (sin historial citado ni avisos legales).
 *  - hidden: historial citado (De:/Enviado:, «El … escribió:», líneas con >) y aviso legal.
 *
 * Funciona con texto plano (el que manda n8n). Si no se reconoce nada, todo queda en main.
 */
class EmailBodyFormatter
{
    /** @return array{main:string,hidden:string} */
    public static function split(?string $text): array
    {
        $text = self::normalize((string) $text);
        if ($text === '') {
            return ['main' => '', 'hidden' => ''];
        }

        $lines = explode("\n", $text);
        $cut = self::quoteStart($lines);

        $main = $cut === null ? $lines : array_slice($lines, 0, $cut);
        $hidden = $cut === null ? [] : array_slice($lines, $cut);

        // Aviso legal al final de lo escrito: se pliega junto con el historial.
        $legal = self::legalStart($main);
        if ($legal !== null) {
            $hidden = array_merge(array_slice($main, $legal), $hidden);
            $main = array_slice($main, 0, $legal);
        }

        $mainText = self::tidy(implode("\n", $main));
        $hiddenText = self::tidy(implode("\n", $hidden));

        // Si al recortar no queda nada legible (p. ej. solo era un reenvío), se muestra todo.
        if (! preg_match('/[\p{L}\p{N}]/u', $mainText)) {
            return ['main' => self::tidy(implode("\n", $lines)), 'hidden' => ''];
        }

        return ['main' => $mainText, 'hidden' => $hiddenText];
    }

    /** Texto -> HTML seguro (escapado) con URLs convertidas en enlaces. Se muestra con white-space: pre-wrap. */
    public static function html(?string $text): HtmlString
    {
        $escaped = e((string) $text);

        $linked = preg_replace_callback(
            '~\bhttps?://[^\s<>"\']+~i',
            function (array $m) {
                $url = $m[0];
                $tail = '';
                // La puntuación final no es parte del enlace.
                while ($url !== '' && preg_match('/[.,;:!?)\]]$/', $url) && ! str_ends_with($url, '&amp;')) {
                    $tail = substr($url, -1) . $tail;
                    $url = substr($url, 0, -1);
                }

                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer nofollow">' . $url . '</a>' . $tail;
            },
            $escaped
        );

        return new HtmlString($linked ?? $escaped);
    }

    /** Una sola línea para vistas previas (lista de leads): sin historial, sin avisos, sin marcadores de imagen. */
    public static function preview(?string $text): string
    {
        $main = self::split($text)['main'];

        return trim((string) preg_replace('/\s+/u', ' ', $main));
    }

    // ── Interno ─────────────────────────────────────────────────────────

    private static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);

        // Marcadores de imagen que dejan las firmas: [image001.png], [cid:...], [Someimage]...
        $text = preg_replace('/\[(?:cid:[^\]]*|some\s?image|imagen?\s?\d*|image\d*[^\]]*|[^\]\s]+\.(?:png|jpe?g|gif|bmp|webp|svg))\]/iu', '', $text) ?? $text;

        return trim($text);
    }

    private static function tidy(string $text): string
    {
        $text = preg_replace('/[ \t]+\n/', "\n", $text) ?? $text;   // espacios al final de línea
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;    // máximo una línea en blanco seguida
        $text = preg_replace('/^[\s_\-=*]+$/mu', '', $text) ?? $text; // líneas separadoras sueltas
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** Índice de la primera línea del historial citado, o null. */
    private static function quoteStart(array $lines): ?int
    {
        $n = count($lines);
        $header = '/^\s*(?:de|from)\s*:\s*\S/iu';
        $meta = '/^\s*(?:enviado(?:\s+el)?|sent|fecha|date|para|to|asunto|subject|cc)\s*:/iu';

        for ($i = 0; $i < $n; $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            // «-----Mensaje original-----» / «-----Original Message-----»
            if (preg_match('/^[-_ ]*(?:mensaje original|original message|mensaje reenviado|forwarded message)[-_ ]*$/iu', $line)) {
                return $i;
            }

            // Cabecera de Outlook: De: … + dos de Enviado/Para/Asunto en las líneas siguientes.
            if (preg_match($header, $line)) {
                $hits = 0;
                for ($j = $i + 1; $j <= min($i + 6, $n - 1); $j++) {
                    if (preg_match($meta, $lines[$j])) {
                        $hits++;
                    }
                }
                if ($hits >= 2) {
                    // Si justo antes hay una línea separadora (_____), el corte empieza en ella.
                    $k = $i;
                    while ($k > 0 && (trim($lines[$k - 1]) === '' || preg_match('/^[\s_\-=]{5,}$/u', $lines[$k - 1]))) {
                        $k--;
                    }

                    return $k;
                }
            }

            // Gmail / Apple Mail: «El mar, 10 nov 2026 a las 23:40, X <x@y> escribió:» (puede partirse en 2 líneas).
            $joined = $line . ' ' . trim($lines[$i + 1] ?? '');
            if (preg_match('/^(?:el|on)\s.{8,200}?(?:escribi[oó]|wrote)\s*:\s*$/iu', $line)
                || preg_match('/^(?:el|on)\s.{8,200}?(?:escribi[oó]|wrote)\s*:\s*$/iu', $joined)) {
                return $i;
            }

            // Bloque citado con «>» hasta el final.
            if (str_starts_with($line, '>')) {
                $rest = array_slice($lines, $i);
                $allQuoted = true;
                foreach ($rest as $r) {
                    if (trim($r) !== '' && ! str_starts_with(trim($r), '>')) {
                        $allQuoted = false;
                        break;
                    }
                }
                if ($allQuoted) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Índice de la línea donde empieza un aviso legal / de confidencialidad, o null. */
    private static function legalStart(array $lines): ?int
    {
        $pattern = '/^["“«\s]*(?:'
            . 'si usted no es (?:el|la)\s+(?:destinatario|receptor|persona)'
            . '|este (?:mensaje|correo|e-?mail)[^.\n]{0,120}(?:confidencial|privad|exclusiv)'
            . '|aviso\s+(?:de\s+)?(?:confidencialidad|legal|de privacidad)'
            . '|la informaci[oó]n contenida en (?:este|el presente)'
            . '|disclaimer'
            . '|confidentiality notice'
            . '|this (?:e-?mail|message)[^.\n]{0,120}confidential'
            . '|if you are not the intended recipient'
            . ')/iu';

        foreach ($lines as $i => $line) {
            // Nunca se considera aviso lo que está en la primera línea con texto: es el mensaje en sí.
            if ($i > 0 && preg_match($pattern, trim($line))) {
                $before = implode('', array_slice($lines, 0, $i));
                if (preg_match('/[\p{L}\p{N}]/u', $before)) {
                    return $i;
                }
            }
        }

        return null;
    }
}
