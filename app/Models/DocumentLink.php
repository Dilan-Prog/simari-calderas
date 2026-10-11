<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Arista del "Flujo del documento": un documento (origen) dio lugar a otro
 * (destino). Ver App\Services\Erp\DocumentFlow.
 */
class DocumentLink extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_type', 'from_id', 'to_type', 'to_id', 'relation'];
}
