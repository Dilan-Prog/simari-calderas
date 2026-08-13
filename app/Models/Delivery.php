<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'carrier';
    }

    protected $table = 'carriers';
    const UPDATED_AT = null;
    protected $fillable = [
    'name',
    'code',
    'initials',
    'bg_color',
    'text_color',
    'meta_line',
    'tracking_url_template',
    'phone',
    'website',
    'is_active',
];
}
