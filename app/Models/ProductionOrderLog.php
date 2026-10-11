<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['production_order_id', 'user_id', 'action', 'from_status', 'to_status', 'progress', 'note'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
