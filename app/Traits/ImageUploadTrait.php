<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait ImageUploadTrait
{
    public function uploadImages(array $files, string $folder = 'products'): array
    {
        $paths = [];

        foreach ($files as $file) {
            $ext      = strtolower($file->getClientOriginalExtension());
            $filename = uniqid() . '.' . $ext;
            $path     = $folder . '/' . $filename;

            $encoded = Image::make($file)
                ->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode($ext, 85);

            Storage::disk('public')->put($path, $encoded);

            $paths[] = $path;
        }

        return $paths;
    }

    public function deleteImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
