<?php

namespace App\Console\Commands;

use App\Services\EmailSequenceService;
use Illuminate\Console\Command;

class AdvanceEmailSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-sequences:tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Avanza las secuencias de email pendientes de envío';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app(EmailSequenceService::class)->tick();

        return self::SUCCESS;
    }
}
