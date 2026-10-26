<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Orden de compra que el CLIENTE sube (PDF) desde el portal /customer. El archivo vive en el disco
 * privado `local` (nunca público) y solo se sirve tras verificar el dueño o el permiso de staff.
 * No confundir con App\Models\PurchaseOrder (compras de Equiterm a proveedores).
 */
class CustomerPurchaseOrder extends Model
{
    public const DISK = 'local';

    protected $table = 'customer_purchase_orders';

    protected $fillable = [
        'customer_id', 'po_number', 'po_date', 'quote_id', 'notes',
        'file_path', 'original_name', 'mime', 'size', 'uploaded_by_member_id',
    ];

    protected $casts = [
        'po_date' => 'date',
        'size'    => 'integer',
    ];

    protected static function booted(): void
    {
        // Al borrar la fila (también por cascada manual) no se deja el PDF huérfano.
        static::deleted(function (self $po) {
            try {
                Storage::disk(self::DISK)->delete($po->file_path);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function sizeLabel(): string
    {
        $kb = $this->size / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1) . ' MB' : max(1, (int) round($kb)) . ' KB';
    }
}
