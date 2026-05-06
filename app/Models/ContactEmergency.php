<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactEmergency extends Model
{
    use HasFactory;

    protected $table = 'contact_emergency';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'relationship',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
