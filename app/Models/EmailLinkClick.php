<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLinkClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_send_id',
        'url',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function send()
    {
        return $this->belongsTo(EmailSend::class, 'email_send_id');
    }
}
