<?php

namespace App\Contracts;

use App\Models\GoogleConversionUpload;

/**
 * Frontera entre el servicio de conversiones y Google. La implementación real
 * (DataManagerUploader) habla con la Data Manager API; en pruebas se usa
 * FakeGoogleConversionUploader.
 */
interface GoogleConversionUploader
{
    /** ¿Hay credenciales para intentar la subida? Si no, la fila queda pendiente sin gastar intentos. */
    public function isConfigured(): bool;

    /**
     * Envía una conversión o ajuste.
     *
     * @return array{ok: bool, error: ?string, response: array}
     */
    public function upload(GoogleConversionUpload $upload): array;
}
