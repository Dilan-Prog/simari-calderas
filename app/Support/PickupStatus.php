<?php

namespace App\Support;

/** Estatus, tipos y transiciones de Recolecciones. */
class PickupStatus
{
    public const META = [
        'programada' => ['label' => 'Programada', 'tone' => 'neutral'],
        'en_ruta' => ['label' => 'En ruta', 'tone' => 'warn'],
        'recolectada' => ['label' => 'Recolectada', 'tone' => 'warn'],
        'entregada' => ['label' => 'Entregada', 'tone' => 'ok'],
        'cancelada' => ['label' => 'Cancelada', 'tone' => 'bad'],
        'incidencia' => ['label' => 'Incidencia', 'tone' => 'bad'],
    ];

    public const TYPES = [
        'pedidos' => 'Pedidos',
        'material' => 'Material',
        'otro' => 'Otro',
    ];

    private const TRANSITIONS = [
        'programada' => ['en_ruta', 'recolectada', 'entregada', 'cancelada', 'incidencia'],
        'en_ruta' => ['recolectada', 'entregada', 'cancelada', 'incidencia'],
        'recolectada' => ['entregada', 'incidencia', 'cancelada'],
        'incidencia' => ['programada', 'en_ruta', 'recolectada', 'entregada', 'cancelada'],
        'entregada' => [],
        'cancelada' => [],
    ];

    public static function label(string $status): string
    {
        return self::META[$status]['label'] ?? $status;
    }

    public static function tone(string $status): string
    {
        return self::META[$status]['tone'] ?? 'neutral';
    }

    /** Tono del chip del sistema de diseño ERP (ok|warn|bad|info|neu|acc). */
    public static function chipTone(string $status): string
    {
        return match ($status) {
            'en_ruta' => 'acc',
            'recolectada', 'entregada' => 'ok',
            'cancelada', 'incidencia' => 'bad',
            default => 'neu',
        };
    }

    /** @return string[] */
    public static function allowedTransitions(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [];
    }

    public static function isOpen(string $status): bool
    {
        return in_array($status, ['programada', 'en_ruta', 'recolectada', 'incidencia'], true);
    }
}
