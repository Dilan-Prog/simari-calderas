<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Vista personalizada (columnas visibles, orden, anchos y alto de fila) del
 * panel Ventas > Listado de Productos. Una por nombre y usuario.
 */
class SalesProductView extends Model
{
    protected $fillable = ['user_id', 'name', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
