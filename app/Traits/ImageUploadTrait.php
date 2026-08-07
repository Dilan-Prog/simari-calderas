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
            // guessExtension() se basa en el MIME real del contenido (magic
            // bytes), no en lo que el navegador declaró — getClientOriginalExtension()
            // es confiable en el cliente y no debería decidir con qué
            // extensión se guarda el archivo en disco.
            $ext      = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());
            $filename = uniqid() . '.' . $ext;
            $path     = $folder . '/' . $filename;

            try {
                Image::make($file)
                    ->resize($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save(UploadPath::full($path), $quality);

                $paths[] = $this->deduplicateOrCache($path);
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

        if (!$this->isPubliclyRoutableUrl($url)) {
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

            return $this->deduplicateOrCache($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Guardia anti-SSRF: resuelve el host de la URL y rechaza si cualquiera
     * de sus IPs cae en un rango privado/loopback/link-local/reservado
     * (127.0.0.0/8, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16,
     * 169.254.0.0/16, ::1, fc00::/7, etc. — cubierto por los flags nativos
     * de filter_var). Sin esto, "usar URL" podría usarse para que el
     * servidor haga peticiones HTTP a su propia red interna.
     */
    private function isPubliclyRoutableUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $ips = array_merge(
                gethostbynamel($host) ?: [],
                array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')
            );
        }

        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Chequeo de contención tipo realpath(), mismo patrón que ya usa
     * MediaServeController::show() para servir archivos — hoy ningún
     * llamador de deleteImage() pasa input de usuario directo (siempre rutas
     * generadas por uniqid() o leídas de una columna de BD), pero esto evita
     * que un futuro llamador que sí lo haga pueda borrar cualquier archivo
     * del servidor con un path tipo "../../".
     */
    public function deleteImage(string $path): void
    {
        $base = realpath(UploadPath::base());
        $full = realpath(UploadPath::full($path));

        if (!$base || !$full || !str_starts_with($full, $base) || !is_file($full)) {
            return;
        }

        unlink($full);
    }

    /**
     * Se llama justo después de guardar un archivo nuevo en disco (subida o
     * descarga de URL), antes de que el llamador cree su registro en BD.
     * Si el contenido es casi-idéntico a una imagen que ya existe en el
     * catálogo (dentro de un umbral estricto — ver config('gallery.
     * ingestion_hamming_threshold')), borra el archivo recién guardado y
     * devuelve la URL ya existente en su lugar, evitando reinsertar una
     * imagen que ya fue consolidada como duplicado o simplemente ya está en
     * uso. Si no hay coincidencia, cachea el hash del archivo nuevo de una
     * vez (no hace falta esperar a un escaneo manual) y devuelve su propia
     * ruta sin cambios.
     */
    protected function deduplicateOrCache(string $path): string
    {
        $hashService = app(\App\Services\ImagePerceptualHashService::class);
        $diskPath = UploadPath::full($path);

        $existingUrl = $hashService->findNearDuplicateUrl($diskPath);

        if ($existingUrl !== null) {
            @unlink($diskPath);

            return $existingUrl;
        }

        $hashService->getOrRefresh($path, $diskPath);

        return $path;
    }
}
