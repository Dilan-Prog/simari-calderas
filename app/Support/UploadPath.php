<?php

namespace App\Support;

/**
 * Central place resolving where uploaded files (product images, service
 * report photos, product documents, media picker uploads) live on disk and
 * how they're served publicly. Backed by UPLOADS_BASE_PATH so it can point
 * outside the deployed app tree in production — Hostinger's git auto-deploy
 * resets public_html to match the pushed commit on every push, which wipes
 * anything under public/ that isn't tracked in git (uploaded files, since
 * they're gitignored). When UPLOADS_BASE_PATH isn't set (local dev), it
 * falls back to public_path() so local behavior is unchanged.
 */
class UploadPath
{
    public static function base(): string
    {
        return env('UPLOADS_BASE_PATH') ?: public_path();
    }

    public static function full(string $relativePath): string
    {
        return static::base() . '/' . ltrim($relativePath, '/');
    }

    public static function url(string $relativePath): string
    {
        return url('media/' . ltrim($relativePath, '/'));
    }
}
