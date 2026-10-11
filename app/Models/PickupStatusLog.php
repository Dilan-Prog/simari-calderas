<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['pickup_id', 'from_status', 'to_status', 'note', 'changed_by_user_id'];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
