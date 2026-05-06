<?php

namespace App\Jobs;

use App\Models\GoogleConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendGoogleConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly GoogleConversion $conversion) {}

    public function handle(): void
    {
        // No Google Ads API call — only marks conversion as stored in DB
        $this->conversion->update(['status' => 'stored']);
    }
}
