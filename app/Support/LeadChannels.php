<?php

namespace App\Support;

use App\Models\Lead;

/**
 * Por que medios se puede contactar a un lead: WhatsApp, telefono y correo. La bandeja los muestra como
 * iconos (encendidos si existen) y una insignia "3/3" cuando estan los tres.
 */
class LeadChannels
{
    /** @return array{whatsapp:bool,phone:bool,email:bool,count:int} */
    public static function for(Lead $lead): array
    {
        $whatsapp = $lead->source === 'whatsapp'
            || (bool) $lead->whatsapp_conversation_id
            || filled($lead->getAttribute('contact_whatsapp'));
        $phone = filled($lead->contact_phone) || filled($lead->phone_normalized);
        $email = filled($lead->contact_email);

        return ['whatsapp' => $whatsapp, 'phone' => $phone, 'email' => $email, 'count' => (int) $whatsapp + (int) $phone + (int) $email];
    }

    /** Texto para el tooltip: "Tiene WhatsApp, teléfono y correo". */
    public static function label(array $c): string
    {
        $have = array_values(array_filter([
            $c['whatsapp'] ? 'WhatsApp' : null,
            $c['phone'] ? 'teléfono' : null,
            $c['email'] ? 'correo' : null,
        ]));

        if (! $have) {
            return 'Sin medios de contacto';
        }
        if (count($have) === 1) {
            return 'Solo tiene ' . $have[0];
        }

        return 'Tiene ' . implode(' y ', [implode(', ', array_slice($have, 0, -1)), end($have)]);
    }
}
