<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number', 'supplier_id', 'created_by_user_id',
        'status', 'order_date', 'expected_delivery_date',
        'internal_reference', 'notes', 'currency',
        'subtotal', 'discount_total', 'tax_rate',
        'tax_total', 'total',
    ];

    protected $casts = [
        'order_date'             => 'date',
        'expected_delivery_date' => 'date',
        'subtotal'               => 'decimal:2',
        'discount_total'         => 'decimal:2',
        'tax_rate'               => 'decimal:2',
        'tax_total'              => 'decimal:2',
        'total'                  => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    // Auto-generate PO number: PO-YYYY-XXXX
    public static function generatePoNumber(): string
    {
        $year  = date('Y');
        $last  = static::whereYear('created_at', $year)->max('po_number');
        $seq   = $last ? (intval(substr($last, -4)) + 1) : 1;
        return 'PO-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // Auto-generate internal reference: PO-DDMMYYYY-XXXX (sequence resets daily)
    public static function generateInternalReference(): string
    {
        $date  = now()->format('dmY');
        $last  = static::where('internal_reference', 'like', "PO-{$date}-%")->max('internal_reference');
        $seq   = $last ? (intval(substr($last, -4)) + 1) : 1;
        return 'PO-' . $date . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
