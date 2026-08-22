<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // CRM: procesa la cola de jobs. Aquí se agregarán más adelante los
        // comandos de tick de automatizaciones (Fase 2) y de secuencias de
        // email (Fase 3).
        //
        // Fase 10, punto 6 [BAJA - solo documentar]: `queue:work --stop-when-empty`
        // corre cada minuto en vez de mantener un worker persistente (Supervisor).
        // Trade-off aceptado por ahora: es más simple de operar (no requiere
        // Supervisor/systemd en el hosting actual) y basta para el volumen actual
        // de jobs, pero introduce hasta ~60s de latencia entre que un job se
        // encola y se procesa, y paga el costo de arrancar el proceso PHP cada
        // vez. Valdría la pena migrar a un worker persistente (`queue:work` bajo
        // Supervisor) si el volumen de jobs crece lo suficiente para que la
        // latencia de hasta un minuto sea inaceptable, o si el costo de arranque
        // por minuto se vuelve medible en el uso de CPU del servidor.
        //
        // Fase 10, punto 1 [ALTA]: los 4 comandos llevan `withoutOverlapping()`
        // para que, si una corrida tarda más de un minuto, el scheduler no lance
        // una segunda instancia encima de la primera (riesgo real: procesar la
        // misma inscripción de workflow dos veces o duplicar envíos de email).
        // El lock lo provee `CacheEventMutex`, que usa `Cache::lock()` (atómico)
        // cuando el store configurado implementa `LockProvider`, y cae a
        // `Cache::add()` si no. Verificado contra el store configurado en este
        // proyecto (`CACHE_DRIVER=file` en `.env`): `Illuminate\Cache\FileStore`
        // sí implementa `LockProvider` (usa locks de archivo reales vía
        // `LockableFile`/`flock`), así que el lock de solapamiento es atómico
        // con la configuración actual, a diferencia de otros drivers como
        // `array` que no lo son. Si en algún entorno se cambia `CACHE_DRIVER` a
        // uno que no implemente `LockProvider`, este mecanismo degrada a un
        // `add()` no atómico — revisar esta nota si eso llega a pasar.
        //
        // `workflows:tick`, `email-sequences:tick` y `email-campaigns:send-scheduled`
        // llevan además `runInBackground()`: hoy corren secuencialmente dentro del
        // mismo tick del scheduler, así que si uno tarda, retrasa a los demás
        // (aunque cada uno ya está protegido individualmente contra solaparse
        // consigo mismo). Se dejan en background para que no se bloqueen entre
        // sí. `queue:work --stop-when-empty` se deja corriendo en primer plano
        // (comportamiento sin cambios) porque su propósito es justamente ocupar
        // el minuto procesando la cola.
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();
        $schedule->command('workflows:tick')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('email-sequences:tick')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('email-campaigns:send-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        // Fase 15: trigger "conversación de WhatsApp sin responder > N horas".
        // Mismo patrón que workflows:tick (withoutOverlapping desde el día uno,
        // runInBackground para no bloquear a los demás ticks del minuto).
        //
        // Fase 16: reemplazado por el comando genérico
        // automatable-modules:tick (abajo), que cubre whatsapp_conversation
        // como una entrada más del registro central. Se deja comentado en
        // vez de borrado, y NO se registra en paralelo con el genérico, para
        // no disparar el mismo trigger "stale" dos veces sobre las mismas
        // conversaciones (doble inscripción). Retirar definitivamente una
        // vez confirmado por tests de regresión que el genérico reproduce
        // el comportamiento exacto de este comando (ver Fase 20).
        // $schedule->command('whatsapp-conversations:tick')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground();

        // Fase 16: comando de scheduler genérico que reemplaza al de arriba
        // -- itera config/automatable_modules.php y dispara el trigger
        // "stale" de cada módulo con supports_stale=true (hoy solo
        // whatsapp_conversation; Fase 20 agrega más módulos al registro sin
        // tocar este comando).
        $schedule->command('automatable-modules:tick')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Triggers de Negocio (Deal): "estancado en etapa N días" y "fecha
        // de cierre esperada próxima". Mismo patrón que los demás comandos
        // de tick (withoutOverlapping() + runInBackground() para no
        // bloquear al resto del minuto).
        $schedule->command('deals:tick-stagnant')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('deals:tick-upcoming-close')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Purga visitas de anuncio (gclid/UTM) cuya ventana de 90 días para
        // subir conversiones offline a Google Ads ya expiró. Diario basta --
        // a diferencia de los ticks de arriba, no depende de reaccionar
        // dentro del mismo minuto a nada.
        $schedule->command('ad-tracking:purge-expired')
            ->daily()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
