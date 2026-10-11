<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Ayudas de presentación del ERP (estilo "Object Page / Worklist").
 * Usadas por los componentes Blade x-erp.*.
 */
class ErpUi
{
    /** Tonos válidos de chip: ok | warn | bad | info | neu | acc */
    public const TONES = ['ok', 'warn', 'bad', 'info', 'neu', 'acc'];

    /** Estatus (en español/inglés, minúsculas) → tono. Lo no listado cae en 'neu'. */
    private const MAP = [
        // éxito / terminado
        'ok' => ['pagada', 'pagado', 'aplicado', 'conciliado', 'aceptada', 'aceptado', 'aceptado', 'entregado', 'entregada', 'recibido', 'recibida',
            'completada', 'completado', 'terminada', 'cerrado', 'cerrada', 'cubierto', 'cubierta', 'enviada', 'convertido', 'recolectada',
            'vigente', 'activo', 'activa', 'signed', 'paid', 'completed', 'delivered', 'received', 'closed', 'accepted', 'sent', 'approved', 'applied'],
        // atención próxima / parcial
        'warn' => ['parcial', 'por vencer', 'pausado', 'pausada', 'en_transito', 'en transito', 'pendiente de pago', 'faltantes', 'con faltantes',
            'incidencia', 'calificado', 'contactado', 'omitida', 'sin_atribucion', 'sin atribucion', 'partial', 'paused'],
        // problema
        'bad' => ['vencida', 'vencido', 'cancelada', 'cancelado', 'fallida', 'fallido', 'rechazada', 'rechazado', 'descartado', 'overdue', 'cancelled',
            'failed', 'rejected'],
        // en proceso
        'info' => ['en_ejecucion', 'en ejecucion', 'en ejecución', 'en_proceso', 'en proceso', 'en_curso', 'en curso', 'en_compra', 'en compra',
            'liberada', 'programada', 'en_ruta', 'en ruta', 'en_conteo', 'en conteo', 'calculado', 'calculada', 'nuevo', 'abierto', 'in_progress'],
        // acento: siguiente acción
        'acc' => ['planeado', 'planeada', 'levantamiento', 'por cobrar', 'pending_action'],
        // neutro: aún no inicia
        'neu' => ['borrador', 'pendiente', 'draft', 'pending', 'sin iniciar'],
    ];

    public static function tone(?string $status): string
    {
        $s = mb_strtolower(trim((string) $status));
        foreach (self::MAP as $tone => $list) {
            if (in_array($s, $list, true)) {
                return $tone;
            }
        }

        return 'neu';
    }

    /** "en_ejecucion" → "En ejecucion" (solo capitaliza y quita guiones bajos). */
    public static function label(?string $status): string
    {
        $s = trim(str_replace('_', ' ', (string) $status));

        return $s === '' ? '—' : mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    /**
     * Semáforo de una fecha límite.
     *   closed → neu; vencida → bad; ≤ 7 días → warn; resto → ok; sin fecha → neu.
     *
     * @return array{tone:string,text:string,overdue:bool}
     */
    public static function due(mixed $date, bool $closed = false): array
    {
        if (!$date) {
            return ['tone' => 'neu', 'text' => '—', 'overdue' => false];
        }
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);
        $text = $d->locale('es')->translatedFormat('d M Y');
        if ($closed) {
            return ['tone' => 'neu', 'text' => $text, 'overdue' => false];
        }
        $days = now()->startOfDay()->diffInDays($d->copy()->startOfDay(), false);
        if ($days < 0) {
            return ['tone' => 'bad', 'text' => $text, 'overdue' => true];
        }

        return ['tone' => $days <= 7 ? 'warn' : 'ok', 'text' => $text, 'overdue' => false];
    }

    public static function money(mixed $amount, string $currency = 'MXN'): string
    {
        return '$' . number_format((float) $amount, 2) . ($currency !== 'MXN' ? ' ' . $currency : '');
    }
}
