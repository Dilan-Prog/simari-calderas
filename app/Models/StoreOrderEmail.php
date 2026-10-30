<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un correo automático de un pedido de la tienda (ver StoreOrderMailService). */
class StoreOrderEmail extends Model
{
    public const UPDATED_AT = null;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'store_order_id',
        'event',
        'to_email',
        'subject',
        'status',
        'error',
        'sent_by_user_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function eventLabel(): string
    {
        return (string) config("order_emails.events.{$this->event}.label", $this->event);
    }
}
