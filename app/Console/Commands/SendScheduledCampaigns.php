<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email-campaigns:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía las campañas de email programadas cuya fecha de envío ya venció';

    public function handle(EmailCampaignService $emailCampaignService): int
    {
        $campaigns = EmailCampaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $emailCampaignService->send($campaign);
            $this->line("Enviada: {$campaign->name} (#{$campaign->id})");
        }

        return self::SUCCESS;
    }
}
