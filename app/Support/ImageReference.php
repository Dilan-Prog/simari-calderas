<?php

namespace App\Support;

final class ImageReference
{
    public function __construct(
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly string $fieldPath,
        public readonly string $imageUrl,
        public readonly string $label,
        public readonly string $sublabel,
    ) {
    }
}
