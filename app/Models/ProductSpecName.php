<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpecName extends Model
{
    protected $fillable = ['name'];

    /**
     * Registra cada nombre de especificación no vacío en el catálogo
     * permanente, evitando duplicados por diferencia de mayúsculas/
     * minúsculas (reutiliza el casing ya guardado si existe una variante).
     */
    public static function registerMany(array $keys): void
    {
        foreach ($keys as $key) {
            $name = trim((string) $key);

            if ($name === '') {
                continue;
            }

            $exists = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();

            if (!$exists) {
                static::create(['name' => $name]);
            }
        }
    }
}
