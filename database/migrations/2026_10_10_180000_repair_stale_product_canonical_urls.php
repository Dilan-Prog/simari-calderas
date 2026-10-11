<?php

use App\Support\CanonicalUrls;
use Illuminate\Database\Migrations\Migration;

/**
 * Reparación única de datos: productos cuya canonical_url quedó apuntando al
 * slug viejo de su producto canónico (el slug del destino cambió y las copias
 * no se actualizaban). Idempotente: solo toca filas desfasadas. A partir de
 * ahora Products::booted() las mantiene al día; ver App\Support\CanonicalUrls.
 */
return new class extends Migration
{
    public function up(): void
    {
        CanonicalUrls::repairAll(false);
    }

    public function down(): void
    {
        // Sin reversa: no hay forma (ni motivo) de restaurar URLs desfasadas.
    }
};
