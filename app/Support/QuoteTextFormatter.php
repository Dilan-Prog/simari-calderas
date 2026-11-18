<?php

namespace App\Support;

/**
 * Convierte el texto libre que los vendedores capturan en una cotización
 * (descripción de partida, notas, términos) en bloques HTML seguros para el
 * PDF (DomPDF).
 *
 * Por qué existe: los vendedores pegan textos largos, a veces copiados de
 * ChatGPT en Markdown (tablas con "|", negritas con "**", viñetas "•"). El
 * PDF los imprimía como texto plano dentro de UNA sola fila de tabla: se
 * veían los símbolos y, como DomPDF no parte una fila entre páginas, una
 * descripción más larga que una hoja dejaba páginas en blanco y se cortaba.
 *
 * blocks() devuelve una lista de bloques pequeños (párrafo, viñeta, renglón
 * "Concepto: detalle", subtítulo). El PDF pinta cada bloque como una fila o
 * un <div> propio, así el texto puede continuar en la siguiente página.
 *
 * Todo el texto se escapa con e() ANTES de aplicar formato: el único HTML
 * que sale de aquí es <strong>, <em> y <br>.
 */
class QuoteTextFormatter
{
    /** A partir de aquí la descripción se manda a "Alcance detallado". */
    public const LONG_CHARS = 1200;
    public const LONG_LINES = 25;

    /**
     * @return array<int, array{type: string, html: string, marker?: string}>
     *   type: p (párrafo) | li (viñeta) | ol (numerada; marker = "1.") |
     *         kv (concepto: detalle) | h (subtítulo)
     */
    public static function blocks(?string $text): array
    {
        $text = self::normalize($text);
        if ($text === '') {
            return [];
        }

        $lines = explode("\n", $text);
        $blocks = [];
        $para = [];

        $flush = function () use (&$para, &$blocks) {
            if ($para) {
                $blocks[] = ['type' => 'p', 'html' => implode('<br>', array_map([self::class, 'inline'], $para))];
                $para = [];
            }
        };

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = trim($lines[$i]);

            if ($line === '') {
                $flush();
                continue;
            }

            // ── Tabla Markdown: | celda | celda | ─────────────────────────
            if (self::isTableRow($line)) {
                $flush();
                if (self::isTableSeparator($line)) {
                    continue;
                }
                // Fila de encabezados (la siguiente línea es |---|---|): se omite.
                $next = trim($lines[$i + 1] ?? '');
                if ($next !== '' && self::isTableRow($next) && self::isTableSeparator($next)) {
                    continue;
                }
                $cells = self::tableCells($line);
                if (!$cells) {
                    continue;
                }
                if (count($cells) === 1) {
                    $blocks[] = ['type' => 'p', 'html' => self::inline($cells[0])];
                } else {
                    $label = self::stripMarks(array_shift($cells));
                    $blocks[] = [
                        'type' => 'kv',
                        'html' => '<strong>' . e(rtrim($label, ':')) . ':</strong> ' . implode(' · ', array_map([self::class, 'inline'], $cells)),
                    ];
                }
                continue;
            }

            // ── Subtítulos Markdown (#, ##, ###) ─────────────────────────
            if (preg_match('/^#{1,6}\s+(.+)$/u', $line, $m)) {
                $flush();
                $blocks[] = ['type' => 'h', 'html' => self::inline(self::stripMarks($m[1]))];
                continue;
            }

            // ── Viñetas: -, *, •, ·, – ───────────────────────────────────
            if (preg_match('/^(?:[-*•·–]|\x{2022})\s+(.+)$/u', $line, $m)) {
                $flush();
                foreach (self::splitInlineBullets($m[1]) as $piece) {
                    $blocks[] = ['type' => 'li', 'html' => self::labelize(self::inline($piece))];
                }
                continue;
            }

            // ── Numeradas: 1. / 1) / a) ──────────────────────────────────
            if (preg_match('/^(\d{1,3}|[a-zA-Z])([.)])\s+(.+)$/u', $line, $m)) {
                $flush();
                $blocks[] = ['type' => 'ol', 'marker' => $m[1] . $m[2], 'html' => self::labelize(self::inline($m[3]))];
                continue;
            }

            // ── Varias viñetas "•" en una misma línea (p. ej. términos) ──
            $inline = self::splitInlineBullets($line);
            if (count($inline) > 1) {
                $flush();
                foreach ($inline as $piece) {
                    $blocks[] = ['type' => 'li', 'html' => self::labelize(self::inline($piece))];
                }
                continue;
            }

            $para[] = $line;
        }
        $flush();

        return $blocks;
    }

    /** ¿La descripción es tan larga que conviene mandarla a "Alcance detallado"? */
    public static function isLong(?string $text): bool
    {
        $text = self::normalize($text);

        return mb_strlen($text) > self::LONG_CHARS
            || substr_count($text, "\n") + 1 > self::LONG_LINES;
    }

    /** Primer texto útil, sin formato, recortado: resumen para la fila de la partida. */
    public static function summary(?string $text, int $chars = 220): string
    {
        foreach (self::blocks($text) as $block) {
            $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<br>', ' ', $block['html'])), ENT_QUOTES, 'UTF-8')));
            if ($plain !== '') {
                return mb_strlen($plain) > $chars ? rtrim(mb_substr($plain, 0, $chars)) . '…' : $plain;
            }
        }

        return '';
    }

    // ─────────────────────────────────────────────────────────────────────

    private static function normalize(?string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], (string) $text);

        // Filas de tabla pegadas en un solo renglón: "| a | b | | c | d |"
        // (al copiar de algunos sitios se pierden los saltos de línea).
        $text = preg_replace('/\|\s+\|(?=\s*(\*\*|[^\s|-]))/u', "|\n|", $text);

        return trim($text);
    }

    private static function isTableRow(string $line): bool
    {
        return mb_strlen($line) > 1 && str_starts_with($line, '|') && str_ends_with($line, '|');
    }

    private static function isTableSeparator(string $line): bool
    {
        return (bool) preg_match('/^\|?(\s*:?-{2,}:?\s*\|)+\s*$/u', $line);
    }

    /** @return string[] */
    private static function tableCells(string $line): array
    {
        $cells = array_map('trim', explode('|', trim($line, '|')));

        return array_values(array_filter($cells, fn ($c) => $c !== ''));
    }

    /** @return string[] */
    private static function splitInlineBullets(string $line): array
    {
        if (substr_count($line, '•') < 2 && !(str_starts_with(trim($line), '•') && substr_count($line, '•') >= 1)) {
            return [$line];
        }

        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*•\s*/u', $line)), fn ($p) => $p !== ''));

        return $parts ?: [$line];
    }

    /**
     * "Garantía: texto…" -> "<strong>Garantía:</strong> texto…" cuando la
     * viñeta empieza con un título corto y aún no trae negritas.
     */
    private static function labelize(string $html): string
    {
        if (str_contains($html, '<strong>')) {
            return $html;
        }

        return preg_replace('/^([^:<]{2,45}):\s+(?=\S)/u', '<strong>$1:</strong> ', $html, 1);
    }

    private static function stripMarks(string $text): string
    {
        return trim(preg_replace('/\*\*|__|`/u', '', $text));
    }

    /** Escapa y aplica formato en línea seguro: **negrita**, *cursiva*. */
    public static function inline(string $text): string
    {
        $html = e(trim($text));
        $html = preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.+?)__/us', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<![*\w])\*(?!\s)([^*]+?)(?<!\s)\*(?![*\w])/u', '<em>$1</em>', $html);
        // Marcas sueltas que quedaron sin pareja.
        $html = str_replace(['**', '__'], '', $html);
        $html = preg_replace('/(^|\s)\*(\s)/u', '$1$2', $html);

        return $html;
    }
}
