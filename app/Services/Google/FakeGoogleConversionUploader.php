<?php

namespace App\Services\Google;

use App\Contracts\GoogleConversionUploader;
use App\Models\GoogleConversionUpload;

/** Uploader en memoria para pruebas: nunca toca la red. */
class FakeGoogleConversionUploader implements GoogleConversionUploader
{
    /** @var array<int, array> */
    public array $uploaded = [];

    /** Resultados encolados; si está vacío, todo sale bien. @var array<int, array{ok: bool, error: ?string}> */
    public array $queue = [];

    public bool $configured = true;

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function failTimes(int $times, string $error = 'Error simulado'): self
    {
        for ($i = 0; $i < $times; $i++) {
            $this->queue[] = ['ok' => false, 'error' => $error];
        }

        return $this;
    }

    public function upload(GoogleConversionUpload $upload): array
    {
        $this->uploaded[] = [
            'order_id'        => $upload->order_id,
            'operation'       => $upload->operation,
            'action'          => $upload->action,
            'identifier_type' => $upload->identifier_type,
            'identifier'      => $upload->identifier,
            'value'           => $upload->conversion_value,
        ];

        $next = array_shift($this->queue) ?? ['ok' => true, 'error' => null];

        return ['ok' => $next['ok'], 'error' => $next['error'], 'response' => ['fake' => true]];
    }
}
