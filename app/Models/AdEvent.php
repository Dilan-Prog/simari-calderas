<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdEvent extends Model
{
    // La tabla no tiene created_at/updated_at en absoluto (solo occurred_at,
    // asignado por el servidor) -- sin esto, cualquier ::create() truena
    // porque Eloquent intenta escribir columnas de timestamps inexistentes.
    public $timestamps = false;

    // Fuente única de verdad de los tipos de evento válidos -- usada tanto
    // por la validación server-side (Rule::in) como por cualquier UI admin
    // que necesite listarlos, para que nunca se desincronicen.
    const EVENT_TYPES = [
        'page_view',
        'whatsapp_click',
        'add_to_cart',
        'cart_abandoned',
        'quote_start',
        'checkout_start',
    ];

    protected $fillable = [
        'ad_visit_id', 'event_type', 'url', 'product_id', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function adVisit()
    {
        return $this->belongsTo(AdVisit::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
