<?php

namespace App\Support;

/**
 * Única fuente de verdad para los 7 estatus de StoreOrder: labels, colores,
 * catálogo de motivos de cancelación y reglas de transición. El JS del
 * modal de cambio de estatus copia META/MOTIVOS/FLOW para pintar, pero
 * nunca decide qué transiciones son válidas — eso siempre viene de
 * allowedTransitions() calculado server-side.
 */
class StoreOrderStatus
{
    // Ruta feliz, orden importa (se usa para calcular adelante/atrás).
    public const FLOW = [
        'pendiente_pago',
        'pagado',
        'en_preparacion',
        'enviado',
        'entregado',
    ];

    public const META = [
        'pendiente_pago' => [
            'label'       => 'Pendiente de pago',
            'color'       => '#6b7280',
            'bg'          => '#f1f2f4',
            'description' => 'Orden creada en el checkout; el pago aún no se confirma.',
        ],
        'pagado' => [
            'label'       => 'Pagado',
            'color'       => '#0f6fbd',
            'bg'          => '#e8f2fb',
            'description' => 'Pago verificado. Lista para preparar en almacén.',
        ],
        'en_preparacion' => [
            'label'       => 'En preparación',
            'color'       => '#b45309',
            'bg'          => '#fef3c7',
            'description' => 'Almacén surtiendo y empacando las partidas.',
        ],
        'enviado' => [
            'label'       => 'Enviado',
            'color'       => '#6d28d9',
            'bg'          => '#f1ebfd',
            'description' => 'Entregada a paquetería. Se enlazará con el módulo de Envíos.',
        ],
        'entregado' => [
            'label'       => 'Entregado',
            'color'       => '#0f7a4f',
            'bg'          => '#e6f6ee',
            'description' => 'Confirmada la recepción por el cliente. Ciclo cerrado.',
        ],
        'cancelado' => [
            'label'       => 'Cancelado',
            'color'       => '#c81e1e',
            'bg'          => '#fdecec',
            'description' => 'Cancelada por el cliente o por el equipo. Rama terminal.',
        ],
        'reembolsado' => [
            'label'       => 'Reembolsado',
            'color'       => '#0e7490',
            'bg'          => '#e3f4f7',
            'description' => 'Pago devuelto al cliente después de cancelar.',
        ],
    ];

    public const MOTIVOS = [
        'sin_pago'  => 'Pago no recibido en el plazo',
        'cliente'   => 'Cancelada a petición del cliente',
        'stock'     => 'Sin existencias / producto descontinuado',
        'datos'     => 'Datos de envío o facturación incorrectos',
        'duplicada' => 'Orden duplicada',
        'fraude'    => 'Sospecha de fraude',
    ];

    public static function meta(string $status): array
    {
        return self::META[$status] ?? [];
    }

    public static function allowedTransitions(string $current): array
    {
        if ($current === 'cancelado') {
            return ['reembolsado'];
        }

        if ($current === 'reembolsado') {
            return [];
        }

        $transitions = array_values(array_diff(self::FLOW, [$current]));

        // Ya no se puede cancelar una vez enviada.
        $currentIndex = array_search($current, self::FLOW, true);
        $shippedIndex = array_search('enviado', self::FLOW, true);
        if ($currentIndex !== false && $currentIndex < $shippedIndex) {
            $transitions[] = 'cancelado';
        }

        return $transitions;
    }

    public static function isBackward(string $from, string $to): bool
    {
        $fromIndex = array_search($from, self::FLOW, true);
        $toIndex   = array_search($to, self::FLOW, true);

        if ($fromIndex === false || $toIndex === false) {
            return false;
        }

        return $toIndex < $fromIndex;
    }
}
