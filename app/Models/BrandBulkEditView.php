<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandBulkEditView extends Model
{
    protected $fillable = ['user_id', 'name', 'columns'];

    protected $casts = [
        'columns' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
