<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionSection extends Model
{
    protected $fillable = ['collection_id', 'type', 'config', 'title', 'sort_order', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Mismo mecanismo que ServiceSection::resolveText(): en el contexto de
     * una Collection reemplaza {coleccion} por su nombre.
     */
    public function resolveText(?string $text, $context = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        if ($context instanceof Products) {
            return $context->resolveVariables($text);
        }

        $replacements = match (true) {
            $context instanceof Collection  => ['{coleccion}' => $context->name ?? ''],
            $context instanceof ServicePage => ['{servicio}' => $context->name ?? ''],
            default => [],
        };

        $resolved = strtr($text, $replacements);
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }
}
