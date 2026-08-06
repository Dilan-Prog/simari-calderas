<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Services\BackupFailedException;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Consola SQL admin-only (lectura y escritura). Acceso restringido en dos
 * capas independientes: la ruta va gateada por 'permission:devops' (que
 * nunca existe como fila real en la tabla permissions — 'devops' se omite
 * deliberadamente de config/modules.php para que jamás sea asignable a un
 * rol no-admin desde la UI de Roles) MÁS el guard explícito abort_unless()
 * en cada método de este controller, que no depende de esa convención.
 *
 * Toda escritura requiere: una sola sentencia (sin punto y coma adicional),
 * confirmación explícita re-verificada en servidor, y un respaldo completo
 * de la BD generado justo antes — si el respaldo falla, la escritura nunca
 * se ejecuta. Cada intento (exitoso, rechazado o fallido) queda registrado
 * en system_logs.
 */
class DevOpsController extends Controller
{
    private const MAX_RESULT_ROWS = 500;

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $history = SystemLog::where('entity_type', 'sql')
            ->orderByDesc('performed_at')
            ->paginate(20);

        return view('admin.devops.index', compact('history'));
    }

    public function execute(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'sql' => 'required|string|max:20000',
            'confirmed' => 'sometimes|boolean',
        ]);

        $sql = trim($validated['sql']);

        // Guardia de sentencia única: se quita un ; final si existe, y si
        // queda cualquier otro ; en el texto, se rechaza ANTES de clasificar
        // o tocar la BD — así "SELECT 1; DROP TABLE users;" nunca llega ni a
        // clasificarse como lectura/escritura.
        $withoutTrailingSemicolon = rtrim($sql, "; \t\n\r\0\x0B");
        if (str_contains($withoutTrailingSemicolon, ';')) {
            $this->logAttempt($request, $sql, 'rejected_multi_statement', null, null);

            return response()->json([
                'ok' => false,
                'message' => 'Solo se permite una sentencia SQL por ejecución. Se detectó más de un punto y coma.',
            ], 422);
        }

        $isRead = $this->isReadStatement($sql);

        if (!$isRead && !$request->boolean('confirmed')) {
            $this->logAttempt($request, $sql, 'rejected_unconfirmed_write', null, null);

            return response()->json([
                'ok' => false,
                'requires_confirmation' => true,
                'message' => 'Esta sentencia modifica datos y requiere confirmación explícita.',
            ], 422);
        }

        $backupPath = null;

        if (!$isRead) {
            try {
                $backupPath = app(DatabaseBackupService::class)->createBackup();
            } catch (BackupFailedException $e) {
                $this->logAttempt($request, $sql, 'rejected_backup_failed', null, ['error' => $e->getMessage()]);

                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
        }

        try {
            if ($isRead) {
                $rows = DB::select($sql);
                $result = $this->formatReadResult($rows);

                $this->logAttempt($request, $sql, 'select', null, $result['meta']);

                return response()->json([
                    'ok' => true,
                    'type' => 'read',
                    'columns' => $result['columns'],
                    'rows' => $result['rows'],
                    'truncated' => $result['meta']['truncated'],
                ]);
            }

            $affected = DB::affectingStatement($sql);

            $this->logAttempt($request, $sql, 'write', $backupPath, ['affected' => $affected]);

            return response()->json([
                'ok' => true,
                'type' => 'write',
                'message' => 'Sentencia ejecutada correctamente.',
                'affected' => $affected,
                'backup_file' => basename($backupPath),
            ]);
        } catch (\Throwable $e) {
            $this->logAttempt(
                $request,
                $sql,
                $isRead ? 'select_failed' : 'write_failed',
                $backupPath,
                ['error' => $e->getMessage()]
            );

            // Nunca se devuelve el mensaje crudo de la excepción al navegador
            // (podría revelar estructura de tablas/columnas) — el detalle
            // real solo queda en el log de auditoría (new_value de arriba).
            return response()->json([
                'ok' => false,
                'message' => 'Error al ejecutar la sentencia SQL.',
            ], 500);
        }
    }

    /**
     * Solo SELECT/SHOW/DESCRIBE/DESC/EXPLAIN cuentan como lectura. Cualquier
     * otra cosa (incluyendo WITH/CTEs y sentencias no reconocidas) se trata
     * como escritura por defecto — nunca sub-clasificar.
     */
    private function isReadStatement(string $sql): bool
    {
        $normalized = preg_replace('/^(\s|--[^\n]*\n|\/\*.*?\*\/)+/s', '', $sql);

        return (bool) preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $normalized ?? '');
    }

    private function formatReadResult(array $rows): array
    {
        $total = count($rows);
        $truncated = $total > self::MAX_RESULT_ROWS;
        $shown = $truncated ? array_slice($rows, 0, self::MAX_RESULT_ROWS) : $rows;

        $columns = $shown ? array_keys((array) $shown[0]) : [];
        $formattedRows = array_map(fn ($row) => array_values((array) $row), $shown);

        return [
            'columns' => $columns,
            'rows' => $formattedRows,
            'meta' => [
                'row_count' => $total,
                'truncated' => $truncated,
            ],
        ];
    }

    private function logAttempt(Request $request, string $sql, string $action, ?string $backupPath, ?array $meta): void
    {
        SystemLog::create([
            'entity_type' => 'sql',
            // Una ejecución SQL no tiene un "entity" natural al que atarse;
            // 0 se usa como centinela explícito en vez de alterar el NOT
            // NULL de esta tabla compartida por una sola razón.
            'entity_id' => 0,
            'action' => $action,
            'description' => $sql,
            'old_value' => $backupPath ? ['backup_file' => basename($backupPath)] : null,
            'new_value' => $meta,
            'performed_by_user_id' => $request->user()->id,
            'performed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
