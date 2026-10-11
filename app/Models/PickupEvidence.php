<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupEvidence extends Model
{
    protected $table = 'pickup_evidences';

    protected $fillable = ['pickup_id', 'path', 'caption', 'uploaded_by_user_id'];

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(Pickup::class);
    }

    public function url(): ?string
    {
        return UploadPath::url($this->path);
    }
}
