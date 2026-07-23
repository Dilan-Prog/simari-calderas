<?php

namespace App\Http\Controllers;

use App\Support\UploadPath;
use Symfony\Component\HttpFoundation\Response;

/**
 * Publicly serves files stored under UploadPath::base() (products,
 * service-reports, product-documents, uploads). These are public assets —
 * no auth check — this route exists only so the files can live outside
 * public_html (see UploadPath) while still being reachable by URL.
 */
class MediaServeController extends Controller
{
    private const ALLOWED_FOLDERS = ['products', 'service-reports', 'product-documents', 'uploads'];

    public function show(string $path): Response
    {
        $folder = strtok($path, '/');

        if (!in_array($folder, self::ALLOWED_FOLDERS, true)) {
            abort(404);
        }

        $base = realpath(UploadPath::base());
        $full = realpath(UploadPath::full($path));

        if (!$base || !$full || !str_starts_with($full, $base) || !is_file($full)) {
            abort(404);
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
