<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDocument extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'type',
        'path',
        'original_name',
        'created_at',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'ficha' => 'Ficha Técnica',
            'manual' => 'Manual de Instalación',
            'catalogo' => 'Catálogo del Producto',
            'certificacion' => 'Certificaciones',
            'garantia' => 'Garantía',
            default => 'Documento Adicional',
        };
    }
}
