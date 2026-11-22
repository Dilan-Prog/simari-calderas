<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Rutas completas de categorías ("Padre › Hijo › Nieto") calculadas con UNA
 * sola consulta (sin N+1) — las usan el selector de "Categorías adicionales"
 * del admin, el badge "+N" del listado, el Excel de productos y la
 * resolución de la columna "Categorías Adicionales" al importar.
 */
class CategoryPaths
{
    public const SEPARATOR = ' › ';

    /** @var array<int,array{id:int,name:string,slug:?string,parent_id:?int,is_active:bool}>|null */
    private ?array $rows = null;

    /** @var array<int,string> */
    private array $paths = [];

    public function __construct(?iterable $categories = null)
    {
        if ($categories !== null) {
            $this->load($categories);
        }
    }

    private function rows(): array
    {
        if ($this->rows === null) {
            $this->load(Category::query()->get(['id', 'name', 'slug', 'parent_id', 'is_active']));
        }

        return $this->rows;
    }

    private function load(iterable $categories): void
    {
        $this->rows = [];
        foreach ($categories as $c) {
            $this->rows[(int) $c->id] = [
                'id'        => (int) $c->id,
                'name'      => (string) $c->name,
                'slug'      => $c->slug,
                'parent_id' => $c->parent_id ? (int) $c->parent_id : null,
                'is_active' => (bool) ($c->is_active ?? true),
            ];
        }
    }

    /** Ruta completa de una categoría, con el separador dado. */
    public function path(int $id, string $separator = self::SEPARATOR): string
    {
        $rows = $this->rows();
        if (!isset($rows[$id])) {
            return '';
        }

        if (!isset($this->paths[$id])) {
            $parts = [];
            $seen = [];
            $node = $rows[$id];
            // $seen protege contra un ciclo accidental parent_id -> sí misma.
            while ($node && !isset($seen[$node['id']])) {
                $seen[$node['id']] = true;
                array_unshift($parts, $node['name']);
                $node = $node['parent_id'] ? ($rows[$node['parent_id']] ?? null) : null;
            }
            $this->paths[$id] = $parts;
        }

        return implode($separator, $this->paths[$id]);
    }

    /**
     * Opciones para el selector con búsqueda, ordenadas por ruta.
     *
     * @return array<int,array{id:int,label:string,active:bool}>
     */
    public function options(): array
    {
        $options = [];
        foreach ($this->rows() as $id => $row) {
            $options[] = ['id' => $id, 'label' => $this->path($id), 'active' => $row['is_active']];
        }
        usort($options, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));

        return $options;
    }

    /** Texto normalizado para comparar: minúsculas, sin acentos ni espacios extra. */
    public static function normalize(?string $value): string
    {
        $value = Str::lower(Str::ascii(trim((string) $value)));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    /**
     * Resuelve un texto escrito a mano (Excel) a un id de categoría. Acepta,
     * en este orden: ruta completa ("Padre > Hijo" o "Padre › Hijo"), slug
     * completo o último segmento del slug, y nombre exacto. Todo sin
     * distinguir mayúsculas ni acentos. Un nombre que se repite en varias
     * ramas es ambiguo y devuelve null (hay que escribir la ruta).
     */
    public function resolve(?string $value): ?int
    {
        $needle = self::normalizePath($value);
        if ($needle === '') {
            return null;
        }

        $index = $this->index();
        foreach (['path', 'slug', 'slugtail', 'name'] as $kind) {
            $matches = array_values(array_unique($index[$kind][$needle] ?? []));
            if (count($matches) === 1) {
                return $matches[0];
            }
            if (count($matches) > 1) {
                return null;
            }
        }

        return null;
    }

    /** @var array<string,array<string,int[]>>|null tipo => [texto normalizado => ids] */
    private ?array $index = null;

    private function index(): array
    {
        if ($this->index === null) {
            $this->index = ['path' => [], 'slug' => [], 'slugtail' => [], 'name' => []];
            foreach ($this->rows() as $id => $row) {
                $this->index['path'][self::normalizePath($this->path($id, ' > '))][] = $id;
                $slug = self::normalize($row['slug']);
                if ($slug !== '') {
                    $this->index['slug'][$slug][] = $id;
                    if (str_contains($slug, '/')) {
                        $this->index['slugtail'][Str::afterLast($slug, '/')][] = $id;
                    }
                }
                $this->index['name'][self::normalize($row['name'])][] = $id;
            }
        }

        return $this->index;
    }

    /** normalize() + separadores de ruta (› o >) unificados a " > ". */
    private static function normalizePath(?string $value): string
    {
        $value = self::normalize(str_replace('›', '>', (string) $value));

        return preg_replace('/\s*>\s*/', ' > ', $value) ?? '';
    }

    /**
     * Separa una celda "A | B | C" (también acepta ; o saltos de línea) en
     * valores individuales sin vacíos.
     *
     * @return string[]
     */
    public static function splitCell(?string $cell): array
    {
        $parts = preg_split('/\s*(?:\||;|\r\n|\n|\r)\s*/u', trim((string) $cell)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }
}
