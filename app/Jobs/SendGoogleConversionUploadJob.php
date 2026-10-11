<?php

namespace App\Jobs;

use App\Models\GoogleConversionUpload;
use App\Services\GoogleConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Un intento de envío de una fila de google_conversion_uploads. Los reintentos
 * con espera exponencial los re-encola GoogleConversionService::process().
 */
class SendGoogleConversionUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $uploadId)
    {
    }

    public function handle(GoogleConversionService $service): void
    {
        $upload = GoogleConversionUpload::find($this->uploadId);
        if ($upload) {
            $service->process($upload);
        }
    }
}
