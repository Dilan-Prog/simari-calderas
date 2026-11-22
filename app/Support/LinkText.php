<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Formato en línea de los textos escritos por el admin (bloques del editor en
 * vivo, FAQs…). El texto se guarda tal cual; solo al renderizar se convierte:
 *
 *  - Enlaces `[texto](destino)` → <a class="eq-inline-link"> (color de marca).
 *    Destinos permitidos: ruta relativa del sitio (`/algo`, nunca `//` ni `/\`)
 *    o `http(s)://`. Cualquier otro esquema (javascript:, data:, ...) se deja
 *    como texto plano escapado.
 *  - Negritas `**texto**` → <strong>.
 *  - Viñetas: renglones que empiezan con "- " o "• " → lista. Se genera con
 *    <span> en bloque (no <ul>) para que sea HTML válido aunque el campo se
 *    imprima dentro de un <p>.
 */
class LinkText
{
    // Etiqueta: sin corchetes. Destino: sin espacios ni ")".
    private const PATTERN = '/\[([^\[\]]+)\]\(((?:\/(?![\/\x5C])|https?:\/\/)[^\s)]*)\)/i';

    // **texto** (sin saltos de línea; el texto no empieza ni termina con espacio).
    private const BOLD = '/\*\*(?!\s)([^\n]+?)(?<!\s)\*\*/u';

    // Renglón de viñeta: "- texto" o "• texto".
    public const BULLET = '/^\s*[-•]\s+(.*)$/u';

    /**
     * HTML seguro (imprimir con {!! !!}): escapa todo primero y luego aplica
     * enlaces, negritas y viñetas. Texto sin marcado equivale a e($text).
     */
    public static function render(?string $text): HtmlString|string
    {
        if ($text === null || $text === '') {
            return new HtmlString('');
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);

        // Sin viñetas el comportamiento de los saltos de línea no cambia.
        $hasBullets = false;
        foreach ($lines as $line) {
            if (preg_match(self::BULLET, $line)) {
                $hasBullets = true;
                break;
            }
        }
        if (! $hasBullets) {
            return new HtmlString(self::inline($text));
        }

        $html = '';
        $inList = false;
        $prevText = false;
        foreach ($lines as $line) {
            if (preg_match(self::BULLET, $line, $m)) {
                if (! $inList) {
                    $html .= '<span class="eq-list">';
                    $inList = true;
                }
                $html .= '<span class="eq-li">' . self::inline($m[1]) . '</span>';
                $prevText = false;
                continue;
            }
            if ($inList) {
                $html .= '</span>';
                $inList = false;
            } elseif ($prevText) {
                $html .= '<br>';
            }
            $html .= self::inline($line);
            $prevText = true;
        }
        if ($inList) {
            $html .= '</span>';
        }

        return new HtmlString($html);
    }

    /** Escapa y aplica enlaces + negritas a un fragmento (sin manejo de renglones). */
    private static function inline(string $text): string
    {
        $escaped = e($text);

        $html = preg_replace_callback(self::PATTERN, function (array $m) {
            // $m ya viene escapado (e()), por lo que es seguro en HTML/atributo.
            $external = (bool) preg_match('#^https?://#i', $m[2]);
            $attrs = $external ? ' target="_blank" rel="noopener"' : '';

            return '<a class="eq-inline-link" href="' . $m[2] . '"' . $attrs . '>' . $m[1] . '</a>';
        }, $escaped) ?? $escaped;

        return preg_replace(self::BOLD, '<strong>$1</strong>', $html) ?? $html;
    }

    /**
     * Quita el marcado y deja solo el texto. Texto sin escapar: usar
     * con {{ }} (aria-label, title, alt, meta) o dentro de otro <a>/<button>.
     */
    public static function plain(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = preg_replace_callback(self::PATTERN, fn (array $m) => $m[1], $text) ?? $text;
        $text = preg_replace(self::BOLD, '$1', $text) ?? $text;

        return preg_replace('/^\s*[-•]\s+/mu', '', $text) ?? $text;
    }
}
