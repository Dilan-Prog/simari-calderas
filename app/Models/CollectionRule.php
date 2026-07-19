<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionRule extends Model
{
    protected $fillable = ['collection_id', 'field', 'operator', 'value'];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
