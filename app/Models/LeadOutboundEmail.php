<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutboundEmail extends Model
{
    protected $fillable = [
        'message_id', 'lead_id', 'task_id', 'from_email', 'to_email', 'recipients',
        'subject', 'body', 'attachments', 'sent_at',
    ];

    protected $casts = ['recipients' => 'array', 'attachments' => 'array', 'sent_at' => 'datetime'];

    /**
     * El flujo de n8n saca el texto quitando las etiquetas del HTML del correo y deja
     * entidades como &lt; &gt; &nbsp; sin convertir. Se normalizan al leer, así se
     * corrigen también los correos ya guardados. Blade vuelve a escapar al mostrar.
     */
    public function getBodyAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace("\u{00A0}", ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
