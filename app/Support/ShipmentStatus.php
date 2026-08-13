<?php

namespace App\Support;

/**
 * Única fuente de verdad para los estatus de Shipment: labels, colores,
 * catálogo de motivos de incidencia y reglas de transición. El JS del
 * modal de cambio de estatus copia META/MOTIVOS/FLOW para pintar, pero
 * nunca decide qué transiciones son válidas — eso siempre viene de
 * allowedTransitions() calculado server-side.
 *
 * A diferencia de StoreOrderStatus, las transiciones aquí son SIEMPRE hacia
 * adelante por diseño: un envío nunca regresa a un estatus anterior de FLOW,
 * y la única salida lateral es a 'incidencia' (retraso, domicilio
 * incorrecto, daño o devolución), desde donde solo se puede retomar el flujo
 * en 'en_transito' o avanzar. Por eso, intencionalmente, esta clase NO tiene
 * un método isBackward() — no es un descuido, es que el concepto no aplica.
 */
class ShipmentStatus
{
    // Ruta feliz, orden importa (se usa para calcular las transiciones hacia adelante).
    public const FLOW = ['preparando', 'enviado', 'en_transito', 'entregado'];

    public const META = [
        'preparando' => [
            'label' => 'Preparando',
            'color' => '#b45309',
            'bg' => '#fef3c7',
            'description' => 'Guía generada; el paquete aún está en almacén.',
        ],
        'enviado' => [
            'label' => 'Enviado',
            'color' => '#0f6fbd',
            'bg' => '#e8f2fb',
            'description' => 'Recolectado por la paquetería.',
        ],
        'en_transito' => [
            'label' => 'En tránsito',
            'color' => '#6d28d9',
            'bg' => '#f1ebfd',
            'description' => 'En ruta hacia el domicilio del cliente.',
        ],
        'entregado' => [
            'label' => 'Entregado',
            'color' => '#0f7a4f',
            'bg' => '#e6f6ee',
            'description' => 'Recibido y confirmado en destino.',
        ],
        'incidencia' => [
            'label' => 'Incidencia',
            'color' => '#c81e1e',
            'bg' => '#fdecec',
            'description' => 'Retraso, domicilio incorrecto, daño o devolución.',
        ],
    ];

    public const MOTIVOS = [
        'retraso' => 'Retraso de la paquetería',
        'domicilio' => 'Domicilio incorrecto o sin acceso',
        'ausente' => 'Nadie recibió en destino',
        'dano' => 'Paquete dañado',
        'perdida' => 'Extravío',
        'devolucion' => 'Devolución al remitente',
    ];

    public static function meta(string $status): array
    {
        return self::META[$status] ?? [];
    }

    public static function allowedTransitions(string $current): array
    {
        if ($current === 'incidencia') {
            $from = array_search('en_transito', self::FLOW, true);
            return array_slice(self::FLOW, $from);
        }

        $idx = array_search($current, self::FLOW, true);
        if ($idx === false) {
            return [];
        }

        $opts = array_slice(self::FLOW, $idx + 1);
        $opts[] = 'incidencia';

        return $opts;
    }
}
