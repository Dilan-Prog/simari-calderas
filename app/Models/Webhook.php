<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Entidad reutilizable "Webhook" (conexión guardada) que un paso
 * call_webhook del canvas de Automatizaciones puede referenciar por
 * webhook_id en vez de repetir url/method/headers/credential_id en cada
 * action_config. Ver WorkflowActionExecutor::callWebhook().
 */
class Webhook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'method',
        'headers',
        'credential_id',
        'created_by',
    ];

    protected $casts = [
        'headers' => 'array',
    ];

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
