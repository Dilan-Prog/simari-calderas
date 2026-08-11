<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido generado por el checkout de la tienda pública (carrito ->
 * finalizar-pedido). Tabla independiente de la "orders" huérfana legado
 * (2026_04_19_*, sin modelo/controller/ruta) — no relacionadas.
 */
class StoreOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'order_number',
        'customer_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'payment_method_id',
        'subtotal',
        'shipping_total',
        'discount_total',
        'tax_total',
        'total',
        'currency',
        'status',
        'terms_accepted_at',
        'notes',
        'requires_invoice',
        'rfc',
        'uso_cfdi',
        'razon_social',
        'regimen_fiscal',
        'cp_fiscal',
        'tax_certificate_path',
    ];

    protected $casts = [
        'terms_accepted_at' => 'datetime',
        'requires_invoice'  => 'boolean',
    ];

    protected static function logEntityType(): string
    {
        return 'store_order';
    }

    // RFC y la ruta del archivo fiscal no se guardan en la bitácora — mismo
    // criterio que Customer.password_hash / ServiceReport.signature_data.
    protected static function logExcept(): array
    {
        return ['rfc', 'tax_certificate_path'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Folio "PW-YYYY-XXXX" ("Pedido Web") — mismo patrón que
    // SalesOrder::generateOrderNumber() (PED-YYYY-XXXX), prefijo distinto
    // para no confundir pedidos web con pedidos capturados por el admin.
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->max('order_number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'PW-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
