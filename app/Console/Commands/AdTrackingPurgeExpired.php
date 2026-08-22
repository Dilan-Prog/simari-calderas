<?php

namespace App\Console\Commands;

use App\Models\AdVisit;
use Illuminate\Console\Command;

/**
 * Borra las visitas de anuncio (ad_visits) cuya ventana de 90 días para subir
 * conversiones offline a Google Ads ya expiró. ad_events asociados se borran
 * solos vía cascadeOnDelete de la FK -- no hace falta borrarlos aquí.
 */
class AdTrackingPurgeExpired extends Command
{
    protected $signature = 'ad-tracking:purge-expired';

    protected $description = 'Elimina visitas de anuncio (gclid/UTM) cuya ventana de 90 días para subir conversiones offline ya expiró';

    public function handle(): void
    {
        $count = AdVisit::where('expires_at', '<', now())->count();

        if ($count === 0) {
            $this->info('No hay visitas expiradas por purgar.');

            return;
        }

        AdVisit::where('expires_at', '<', now())->delete();

        $this->info("Se purgaron {$count} visitas de anuncio expiradas (y sus eventos asociados).");
    }
}
