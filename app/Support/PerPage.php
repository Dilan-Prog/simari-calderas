<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Tamaño de página para listados ERP (selector «Mostrar [10 ▾] por página»).
 *
 *   $rows = PerPage::paginate($query, $request);          // ?per_page=10|20|30|50|100|200|500|all
 *   $n    = PerPage::resolve($request);                   // int, o null = «Todos»
 *
 * «Todos» sigue devolviendo un LengthAwarePaginator (una sola página con todo, hasta ALL_CAP filas)
 * para que las vistas sigan usando firstItem()/total()/links()/withQueryString() sin cambios.
 */
class PerPage
{
    public const PARAM = 'per_page';

    public const ALL = 'all';

    /** Opciones del selector (valor => etiqueta). */
    public const OPTIONS = [10 => '10', 20 => '20', 30 => '30', 50 => '50', 100 => '100', 200 => '200', 500 => '500', self::ALL => 'Todos'];

    /** Tope de seguridad para «Todos»: si hay más filas, se pagina en bloques de este tamaño. */
    public const ALL_CAP = 5000;

    public const DEFAULT = 10;

    /** Tamaño solicitado; null = «Todos». Valores desconocidos → $default. */
    public static function resolve(Request $request, int $default = self::DEFAULT): ?int
    {
        $raw = strtolower(trim((string) $request->query(self::PARAM, '')));
        if ($raw === self::ALL) {
            return null;
        }
        if (ctype_digit($raw) && array_key_exists((int) $raw, self::OPTIONS)) {
            return (int) $raw;
        }

        return $default;
    }

    /** Valor actual tal como va en la URL / en el <select> ('10' … '500' | 'all'). */
    public static function current(Request $request, int $default = self::DEFAULT): string
    {
        $n = self::resolve($request, $default);

        return $n === null ? self::ALL : (string) $n;
    }

    /**
     * Pagina la consulta según ?per_page y conserva la query string en los enlaces.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function paginate($query, Request $request, int $default = self::DEFAULT): LengthAwarePaginator
    {
        $n = self::resolve($request, $default);

        if ($n === null) {
            // «Todos»: una página con todas las filas (mínimo 1 para no caer en el perPage del modelo).
            $paginator = $query->paginate(fn ($total) => max(1, min((int) $total, self::ALL_CAP)));
        } else {
            $paginator = $query->paginate($n);
        }

        return $paginator->withQueryString();
    }

    /**
     * Aplana parámetros (incluye arreglos) a pares nombre => valor para <input type="hidden">.
     *
     * @return array<string, string>
     */
    public static function hiddenFields(array $params, string $prefix = ''): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $out += self::hiddenFields($value, $name);
            } elseif ($value !== null) {
                $out[$name] = (string) $value;
            }
        }

        return $out;
    }
}
