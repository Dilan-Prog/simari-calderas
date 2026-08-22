<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una fila por visitor_uuid (identidad persistida en localStorage del
 * navegador). first_* nunca se sobreescribe una vez creada la fila; los
 * campos sin prefijo (gclid, utm_*, etc.) son last-touch y sí se actualizan
 * en cada visita posterior que traiga parámetros de ad nuevos. Ver
 * AdTrackingController::storeVisit().
 */
class AdVisit extends Model
{
    // La tabla no tiene created_at/updated_at -- first_seen_at/last_seen_at
    // ya cumplen ese rol -- sin esto, ::create()/->save() truena.
    public $timestamps = false;

    protected $fillable = [
        'visitor_uuid',
        'first_gclid', 'first_wbraid',
        'first_utm_source', 'first_utm_medium', 'first_utm_campaign',
        'first_utm_content', 'first_utm_term', 'first_utm_matchtype',
        'first_landing_url', 'first_seen_at',
        'gclid', 'wbraid',
        'utm_source', 'utm_medium', 'utm_campaign',
        'utm_content', 'utm_term', 'utm_matchtype',
        'landing_url', 'last_seen_at', 'expires_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'expires_at'    => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(AdEvent::class);
    }

    // El identificador de clic real a mostrar (gclid gana sobre wbraid si
    // por alguna razón ambos están presentes en la misma visita).
    public function getPrimaryClickIdAttribute(): ?string
    {
        return $this->gclid ?: $this->wbraid;
    }

    // Etiqueta de tipo de clic + fuente real (nunca "Google Ads" fijo --
    // utm_source puede ser bing/facebook/etc, no solo Google).
    public function getIdentifierKindAttribute(): string
    {
        $source = $this->utm_source ?: 'directo';

        if ($this->gclid) {
            return 'GCLID · ' . $source;
        }

        if ($this->wbraid) {
            return 'WBRAID (web-to-app) · ' . $source;
        }

        return 'Sin identificador de clic';
    }
}
