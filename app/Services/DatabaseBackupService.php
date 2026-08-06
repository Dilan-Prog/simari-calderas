<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;
use Symfony\Component\Process\Process;

class BackupFailedException extends \RuntimeException
{
}

/**
 * Respaldo de la BD vía mysqldump, usado como red de seguridad obligatoria
 * antes de cualquier escritura ejecutada desde la consola SQL de DevOps
 * (ver DevOpsController::execute()). No es un sistema de backups general —
 * solo existe como paso previo a una escritura manual desde ese módulo.
 */
class DatabaseBackupService
{
    private const KEEP_LAST = 20;

    /**
     * @return string Ruta absoluta al archivo .sql generado.
     * @throws BackupFailedException
     */
    public function createBackup(): string
    {
        $dir = storage_path('app/devops-backups');
        File::ensureDirectoryExists($dir, 0750);

        $conn = config('database.connections.mysql');
        $binary = config('devops.mysqldump_path');

        $filename = sprintf('backup_%s.sql', now()->format('Ymd_His'));
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        $args = [
            $binary,
            '-h', $conn['host'],
            '-P', (string) $conn['port'],
            '-u', $conn['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            $conn['database'],
        ];

        // La contraseña se pasa por variable de entorno del subproceso (no
        // como argumento -p en el argv) para que no quede visible en listados
        // de procesos del sistema operativo.
        $process = new Process($args, null, ['MYSQL_PWD' => $conn['password'] ?? '']);
        $process->setTimeout(300);

        try {
            $process->mustRun(function ($type, $buffer) use ($path) {
                if ($type === Process::OUT) {
                    file_put_contents($path, $buffer, FILE_APPEND);
                }
            });
        } catch (ProcessExceptionInterface $e) {
            @unlink($path);
            throw new BackupFailedException(
                'No se pudo generar el respaldo de la base de datos antes de ejecutar la sentencia. '
                . 'La ejecución fue cancelada.'
            );
        }

        if (!file_exists($path) || filesize($path) === 0) {
            @unlink($path);
            throw new BackupFailedException('El respaldo se generó vacío; se canceló la ejecución.');
        }

        $this->pruneOldBackups();

        return $path;
    }

    private function pruneOldBackups(): void
    {
        $dir = storage_path('app/devops-backups');

        collect(File::files($dir))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values()
            ->slice(self::KEEP_LAST)
            ->each(fn ($f) => File::delete($f->getPathname()));
    }
}
