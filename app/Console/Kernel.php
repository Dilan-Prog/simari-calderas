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
        $schedule->command('queue:work --stop-when-empty')->everyMinute();
        $schedule->command('workflows:tick')->everyMinute();
        $schedule->command('email-sequences:tick')->everyMinute();
        $schedule->command('email-campaigns:send-scheduled')->everyMinute();
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
