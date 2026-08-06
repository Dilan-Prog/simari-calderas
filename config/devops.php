<?php

return [
    // Ruta al binario mysqldump usado por DatabaseBackupService antes de
    // cualquier escritura en la consola SQL de DevOps. En local (Windows/
    // Laragon) puede necesitar apuntar al binario específico; en producción
    // (Linux) normalmente basta con 'mysqldump' si está en el PATH.
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
];
