<?php

namespace App\Traits;

use Intervention\Image\Facades\Image;

trait ImageUploadTrait
{
    public function uploadImages(array $files, string $folder = 'products', int $width = 1200, int $quality = 85): array
    {
        $paths = [];
        $dir   = public_path($folder);

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
                    ->save(public_path($path), $quality);

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

    public function deleteImage(string $path): void
    {
        $fullPath = public_path($path);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
