<?php

namespace App\Services;

use App\Models\ImageHash;
use Intervention\Image\ImageManager;
use Jenssegers\ImageHash\ImageHash as Hasher;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

/**
 * dHash (difference hash) de 64 bits por archivo, con caché en la tabla
 * image_hashes (clave = image_url) para no re-hashear un archivo sin
 * cambios en cada escaneo. Distancia de Hamming vía tabla de popcount de
 * 16 entradas, no GMP/enteros de 64 bits, para portabilidad.
 */
class ImagePerceptualHashService
{
    protected const POPCOUNT = [0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4];

    public function hash(string $diskPath): ?string
    {
        if (!file_exists($diskPath)) {
            return null;
        }

        try {
            $driver = new ImageManager(['driver' => 'gd']);
            $hasher = new Hasher(new DifferenceHash(), $driver);

            // toHex() usa GMP cuando está disponible y recorta bytes cero a
            // la izquierda, dando strings de largo variable — normalizamos
            // siempre a 16 caracteres para que la columna phash y las
            // comparaciones de Hamming sean consistentes.
            return str_pad($hasher->hash($diskPath)->toHex(), 16, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function hammingDistance(string $hash1, string $hash2): int
    {
        $hash1 = str_pad($hash1, 16, '0', STR_PAD_LEFT);
        $hash2 = str_pad($hash2, 16, '0', STR_PAD_LEFT);

        $distance = 0;

        for ($i = 0; $i < 16; $i++) {
            $xor = hexdec($hash1[$i]) ^ hexdec($hash2[$i]);
            $distance += self::POPCOUNT[$xor];
        }

        return $distance;
    }

    public function isMatch(string $hash1, string $hash2): bool
    {
        return $this->hammingDistance($hash1, $hash2) <= (int) config('gallery.duplicate_hamming_threshold', 10);
    }

    /**
     * Devuelve el hash cacheado si el archivo no cambió (mismo tamaño y
     * mtime), o lo recalcula y actualiza la caché en caso contrario.
     */
    public function getOrRefresh(string $imageUrl, string $diskPath): ?string
    {
        if (!file_exists($diskPath)) {
            return null;
        }

        $size = filesize($diskPath);
        $mtime = filemtime($diskPath);

        $cached = ImageHash::where('image_url', $imageUrl)->first();

        if ($cached && $cached->file_size === $size && $cached->file_mtime === $mtime) {
            return $cached->phash;
        }

        $phash = $this->hash($diskPath);

        if (!$phash) {
            return null;
        }

        ImageHash::updateOrCreate(
            ['image_url' => $imageUrl],
            ['phash' => $phash, 'file_size' => $size, 'file_mtime' => $mtime]
        );

        return $phash;
    }
}
