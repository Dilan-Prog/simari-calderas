<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    protected $table = 'carriers';
    const UPDATED_AT = null;
    protected $fillable = [
    'name',
    'code',
    'tracking_url_template',
    'phone',
    'website',
    'is_active',
];
}
