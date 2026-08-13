<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hilo de conversación de WhatsApp con un contacto. Puede vivir dentro de un
 * Pipeline channel='whatsapp' (Embudo de Venta, Fase 12-13) y opcionalmente
 * ligar a un Deal real (Fase 13, botón "Crear negocio") — mover la
 * conversación de etapa NUNCA mueve el Deal vinculado (decisión confirmada
 * del plan: son dos tableros independientes que solo comparten contacto).
 */
class WhatsappConversation extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'account_id',
        'pipeline_id',
        'pipeline_stage_id',
        'deal_id',
        'assigned_user_id',
        'contact_phone',
        'status',
        'started_at',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'last_message_at'  => 'datetime',
        'unread_count'     => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    /**
     * Último mensaje ENTRANTE (del contacto) — es el que cuenta para la
     * ventana de 24h de WhatsApp, no cualquier mensaje (WhatsApp mide desde
     * la última vez que el usuario escribió, no desde el último envío del
     * agente).
     */
    public function lastInboundMessage()
    {
        return $this->hasOne(WhatsappMessage::class, 'conversation_id')
            ->ofMany(['sent_at' => 'max'], function ($query) {
                $query->where('sender_type', 'contact');
            });
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Conversaciones abiertas sin actividad reciente — "sin responder" en el
     * sentido operativo (nadie ha tocado el hilo en $hours horas), usado
     * para alertas/filtros del Embudo de Venta. No confundir con la ventana
     * de 24h de mensajería libre de Meta, que se calcula en
     * WhatsappService::isWithin24hWindow() a partir del último mensaje del
     * contacto específicamente.
     */
    public function scopeStale($query, int $hours = 24)
    {
        return $query->open()
            ->where(function ($q) use ($hours) {
                $q->where('last_message_at', '<', now()->subHours($hours))
                  ->orWhereNull('last_message_at');
            });
    }
}
