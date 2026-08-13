<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    const SENDER_CONTACT = 'contact';
    const SENDER_AGENT   = 'agent';
    const SENDER_SYSTEM  = 'system';

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'message_type',
        'content',
        'media_url',
        'external_message_id',
        'is_template',
        'template_name',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'is_template'   => 'boolean',
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'read_at'       => 'datetime',
        'created_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        // La tabla original solo tiene created_at (sin updated_at), y el
        // modelo tiene $timestamps=false porque Eloquent asume el par
        // completo — se rellena a mano para no perder el registro de fecha.
        static::creating(function (WhatsappMessage $message) {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function conversation()
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }
}
