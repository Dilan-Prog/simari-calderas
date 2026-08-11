<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de métodos de pago del admin (transferencia, efectivo contra
 * entrega, otro). Consumido por el módulo de Carrito/Checkout de la tienda
 * pública (construido en paralelo por otro agente) vía `payment_method_id`
 * en la orden — este modelo no depende de esa tabla.
 *
 * `details` queda libre (JSON) para que tipos futuros (ej. pasarela de
 * pago con tarjeta) guarden sus propios campos sin migración nueva.
 */
class PaymentMethod extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'name',
        'description',
        'is_active',
        'bank_name',
        'clabe',
        'account_number',
        'beneficiary_name',
        'details',
        'channels',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'details' => 'array',
        'channels' => 'array',
    ];

    protected static function logEntityType(): string
    {
        return 'payment_method';
    }
}
