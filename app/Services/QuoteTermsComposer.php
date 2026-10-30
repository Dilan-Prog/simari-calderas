<?php

namespace App\Services;

use App\Models\QuoteTerm;

/**
 * Compone el texto final de terminos y condiciones de una cotizacion.
 *
 * Formato: un bloque por termino seleccionado (en orden sort_order), separados por una linea en blanco:
 *   "• Titulo: cuerpo"   (si el cuerpo ya empieza con el titulo, se usa solo el cuerpo con la vineta)
 * Si hay texto personalizado va al final, tras una linea en blanco, tal cual (recortado).
 */
class QuoteTermsComposer
{
    public function compose(array $termIds, ?string $custom = null): string
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $termIds))));
        $blocks = [];

        if ($ids) {
            foreach (QuoteTerm::whereIn('id', $ids)->ordered()->get() as $term) {
                $body = trim($term->body);
                $title = trim($term->title);
                $repeats = $title !== '' && mb_stripos($body, $title) === 0;
                $blocks[] = '• ' . ($repeats || $title === '' ? $body : $title . ': ' . $body);
            }
        }

        $text = implode("\n\n", $blocks);
        $custom = trim((string) $custom);
        if ($custom !== '') {
            $text = $text === '' ? $custom : $text . "\n\n" . $custom;
        }

        return $text;
    }

    /** IDs normalizados (enteros unicos) y existentes, para guardar en terms_selection. */
    public function normalizeIds(array $termIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $termIds))));

        return $ids ? QuoteTerm::whereIn('id', $ids)->ordered()->pluck('id')->all() : [];
    }
}
