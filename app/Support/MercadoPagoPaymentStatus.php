<?php

namespace App\Support;

/**
 * Única fuente de verdad para los estatus de MercadoPagoPayment: labels,
 * colores y reglas de transición. El JS del checkout/admin copia META para
 * pintar, pero nunca decide qué transiciones son válidas — eso siempre
 * viene de allowedTransitions() calculado server-side.
 *
 * A diferencia de StoreOrderStatus/ShipmentStatus, aquí SÍ se permite
 * retroceso explícito rejected|cancelled -> pending: así es como se modela
 * un reintento de cobro (una nueva llamada a la API de MP con un
 * mp_payment_id nuevo reinicia el ciclo de vida de ese slot).
 */
class MercadoPagoPaymentStatus
{
    // Solo el "camino feliz" simple — los estatus laterales/terminales
    // (rejected, cancelled, refunded, etc.) no forman parte de un FLOW
    // lineal, se manejan aparte en allowedTransitions().
    public const FLOW = ['pending', 'in_process', 'approved'];

    public const META = [
        'pending' => [
            'label' => 'Pendiente',
            'color' => '#6b7280',
            'bg' => '#f1f2f4',
            'description' => 'Esperando que el cliente complete el pago.',
        ],
        'in_process' => [
            'label' => 'En proceso',
            'color' => '#b45309',
            'bg' => '#fef3c7',
            'description' => 'Mercado Pago está validando el pago.',
        ],
        'authorized' => [
            'label' => 'Autorizado',
            'color' => '#0f6fbd',
            'bg' => '#e8f2fb',
            'description' => 'Autorizado, pendiente de captura.',
        ],
        'approved' => [
            'label' => 'Aprobado',
            'color' => '#0f7a4f',
            'bg' => '#e6f6ee',
            'description' => 'Cobro aprobado y acreditado.',
        ],
        'in_mediation' => [
            'label' => 'En mediación',
            'color' => '#6d28d9',
            'bg' => '#f1ebfd',
            'description' => 'El cliente inició una disputa/contracargo en revisión.',
        ],
        'rejected' => [
            'label' => 'Rechazado',
            'color' => '#c81e1e',
            'bg' => '#fdecec',
            'description' => 'La tarjeta o el banco rechazaron el cobro.',
        ],
        'cancelled' => [
            'label' => 'Cancelado',
            'color' => '#6b7280',
            'bg' => '#f1f2f4',
            'description' => 'Cobro cancelado antes de completarse.',
        ],
        'refunded' => [
            'label' => 'Reembolsado',
            'color' => '#0e7490',
            'bg' => '#e3f4f7',
            'description' => 'El cobro ya aprobado fue reembolsado.',
        ],
        'charged_back' => [
            'label' => 'Contracargo',
            'color' => '#c81e1e',
            'bg' => '#fdecec',
            'description' => 'El banco del cliente revirtió el cobro (contracargo).',
        ],
    ];

    public static function meta(string $status): array
    {
        return self::META[$status] ?? [];
    }

    public static function allowedTransitions(string $current): array
    {
        return match ($current) {
            'pending', 'in_process', 'authorized' => ['in_process', 'authorized', 'approved', 'rejected', 'cancelled', 'in_mediation'],
            'approved' => ['refunded', 'charged_back', 'in_mediation'],
            'rejected', 'cancelled' => ['pending'],
            'in_mediation' => ['approved', 'refunded', 'charged_back'],
            default => [],
        };
    }
}
