<?php

namespace App\Support;

/** Da formato legible a telefonos para mostrarlos (no cambia lo que se guarda ni lo que se envia). */
class PhoneFormat
{
    private const BIG_LADA = ['55', '33', '81'];

    public static function pretty(?string $phone): string
    {
        $raw = trim((string) $phone);
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === '') {
            return $raw;
        }

        if (strlen($d) === 13 && str_starts_with($d, '521')) {
            return '+52 1 ' . self::national(substr($d, 3));
        }
        if (strlen($d) === 12 && str_starts_with($d, '52')) {
            return '+52 ' . self::national(substr($d, 2));
        }
        if (strlen($d) === 10) {
            return self::national($d);
        }
        if (strlen($d) > 10 && strlen($d) <= 15 && str_starts_with($raw, '+')) {
            $cc = strlen($d) - 10 > 3 ? 3 : strlen($d) - 10;

            return '+' . substr($d, 0, $cc) . ' ' . self::national(substr($d, $cc));
        }

        return $raw;
    }

    /** 10 digitos: 55 1234 5678 (lada de 2) o 449 123 4567 (lada de 3). */
    private static function national(string $d): string
    {
        if (strlen($d) !== 10) {
            return $d;
        }

        return in_array(substr($d, 0, 2), self::BIG_LADA, true)
            ? substr($d, 0, 2) . ' ' . substr($d, 2, 4) . ' ' . substr($d, 6)
            : substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6);
    }
}
