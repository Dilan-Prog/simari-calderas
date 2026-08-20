<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'html_body',
        'type',
        'created_by',
        'blocks_json',
        'builder_mode',
        'is_system',
        'system_key',
    ];

    protected $casts = [
        'blocks_json' => 'array',
        'is_system'   => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
