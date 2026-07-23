<?php

namespace App\Traits;

use App\Support\UploadPath;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image;

trait ImageUploadTrait
{
    public function uploadImages(array $files, string $folder = 'products', int $width = 1200, int $quality = 85): array
    {
        $paths = [];
        $dir   = UploadPath::full($folder);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($files as $file) {
            $ext      = strtolower($file->getClientOriginalExtension());
            $filename = uniqid() . '.' . $ext;
            $path     = $folder . '/' . $filename;

            try {
                Image::make($file)
                    ->resize($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save(UploadPath::full($path), $quality);

                $paths[] = $path;
            } catch (\Throwable $e) {
                // A file GD/Intervention can't decode (corrupted upload, or a
                // format like HEIC this server's GD build doesn't support)
                // used to throw uncaught and crash the whole batch, discarding
                // every image already processed in the same request. Skip just
                // the bad file instead — callers can compare count($files) to
                // the returned array to detect and report partial failures.
                continue;
            }
        }

        return $paths;
    }

    /**
     * Descarga una imagen desde una URL externa y la guarda localmente con
     * el mismo procesamiento (resize + calidad) que uploadImages(). Usado
     * tanto por el flujo "usar URL" del formulario de producto como por la
     * columna "Imagen URL" del import masivo. Devuelve null si la URL no
     * responde, no es una imagen válida, o excede el tamaño permitido —
     * el llamador decide cómo reportar ese fallo (no lanza excepción).
     */
    public function downloadImageFromUrl(string $url, string $folder = 'products', int $width = 1200, int $quality = 85): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        try {
            $response = Http::timeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type');
            if ($contentType && !str_starts_with($contentType, 'image/')) {
                return null;
            }

            $body = $response->body();
            // 8MB cap on the downloaded payload before we even try to decode it.
            if (strlen($body) > 8 * 1024 * 1024) {
                return null;
            }

            $dir = UploadPath::full($folder);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = uniqid() . '.jpg';
            $path     = $folder . '/' . $filename;

            Image::make($body)
                ->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->save(UploadPath::full($path), $quality);

            return $path;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function deleteImage(string $path): void
    {
        $fullPath = UploadPath::full($path);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
