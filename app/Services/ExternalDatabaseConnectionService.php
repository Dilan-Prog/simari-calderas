<?php

namespace App\Services;

use App\Models\Credential;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Throwable;

/**
 * Abre y consulta conexiones MySQL/MariaDB externas definidas en runtime
 * por una Credential (type = 'mysql'). Primera pieza del proyecto que
 * conecta a una base de datos que no es la conexión Laravel por defecto —
 * ver la nota de riesgo en el plan de Fase 8.
 *
 * El secreto (payload de la Credential) nunca se serializa hacia afuera de
 * este servicio: no se loguea, no se retorna en ningún array de resultado.
 */
class ExternalDatabaseConnectionService
{
    private const CONNECT_TIMEOUT_SECONDS = 5;

    /** @var array<string, Connection> conexiones ya registradas en este proceso */
    private static array $connections = [];

    /**
     * Registra (si hace falta) y devuelve la conexión dinámica para una
     * Credential dada. Cacheada por-request/proceso vía $connections para
     * no repetir config()+registro en cada llamada dentro del mismo job.
     */
    public function connectionFor(Credential $credential): Connection
    {
        $key = "ext_{$credential->id}";

        if (isset(self::$connections[$key])) {
            return self::$connections[$key];
        }

        $payload = $credential->payload ?? [];

        config(["database.connections.{$key}" => $this->buildConnectionConfig($payload)]);

        $connection = DB::connection($key);

        self::$connections[$key] = $connection;

        return $connection;
    }

    /**
     * Prueba una conexión con un payload dado sin persistir ni registrar
     * nada permanente. Usado por el botón "Probar conexión" del modal de
     * Credenciales antes de guardar.
     *
     * @return array{ok: bool, message?: string}
     */
    public function testConnection(array $payload): array
    {
        $config = $this->buildConnectionConfig($payload);

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

            new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return ['ok' => true];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => $this->classifyError($e)];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo conectar: error desconocido.'];
        }
    }

    /**
     * @return string[] nombres de tabla del esquema conectado.
     */
    public function listTables(Credential $credential): array
    {
        $connection = $this->connectionFor($credential);

        $rows = $connection->select(
            'select table_name as table_name from information_schema.tables where table_schema = database()'
        );

        return array_map(fn ($row) => $row->table_name, $rows);
    }

    /**
     * @return string[] nombres de columna de una tabla, previa validación
     *                   de que la tabla existe de verdad en ese esquema.
     */
    public function listColumns(Credential $credential, string $table): array
    {
        if (!in_array($table, $this->listTables($credential), true)) {
            return [];
        }

        $connection = $this->connectionFor($credential);

        $rows = $connection->select(
            'select column_name as column_name from information_schema.columns where table_schema = database() and table_name = ?',
            [$table]
        );

        return array_map(fn ($row) => $row->column_name, $rows);
    }

    private function buildConnectionConfig(array $payload): array
    {
        return [
            'driver'      => 'mysql',
            'host'        => $payload['host'] ?? '127.0.0.1',
            'port'        => $payload['port'] ?? 3306,
            'database'    => $payload['database'] ?? '',
            'username'    => $payload['username'] ?? '',
            'password'    => $payload['password'] ?? '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'options'     => [
                PDO::ATTR_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            ],
        ];
    }

    /**
     * Clasifica el error PDO en un mensaje genérico sin filtrar el mensaje
     * crudo del driver (que puede incluir host/usuario/detalles internos).
     */
    private function classifyError(PDOException $e): string
    {
        $code = (string) $e->getCode();
        $message = $e->getMessage();

        if ($code === '1045' || str_contains($message, 'Access denied')) {
            return 'Usuario o contraseña incorrectos.';
        }

        if ($code === '1049' || str_contains($message, 'Unknown database')) {
            return 'La base de datos especificada no existe.';
        }

        if (str_contains($message, 'timed out') || str_contains($message, 'Connection refused') || $code === '2002') {
            return 'No se pudo alcanzar el host (verifica host/puerto y que el servidor acepte conexiones remotas).';
        }

        return 'No se pudo conectar a la base de datos externa.';
    }
}
