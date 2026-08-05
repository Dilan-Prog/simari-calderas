<?php

return [
    'duplicate_hamming_threshold' => (int) env('GALLERY_DUP_HASH_THRESHOLD', 10),

    // Umbral separado y más estricto para la deduplicación automática al
    // ingerir una imagen nueva (subida/import) — ahí se fusiona sin
    // revisión humana, así que un falso positivo uniría por error la foto
    // de un producto con la de otro. El escaneo manual (arriba) sí tolera
    // un umbral más laxo porque un admin filtra los falsos positivos.
    'ingestion_hamming_threshold' => (int) env('GALLERY_INGEST_HASH_THRESHOLD', 3),
];
