<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Bitácora genérica: cualquier modelo que use este trait queda instrumentado
 * automáticamente contra `system_logs` en created/updated/deleted, sin tocar
 * el controller que lo dispara. Usa el hook `boot{Trait}` de Eloquent
 * (bootLogsActivity), NO `booted()` — varios modelos (Category, ServicePage)
 * ya definen su propio `booted()` para lógica de negocio (cascada de slugs,
 * redirects); el hook de trait convive con eso sin que nadie tenga que
 * acordarse de llamar a parent::booted().
 *
 * La personalización por modelo (exclusiones extra, etiqueta de entidad) se
 * hace con métodos a sobrescribir, no propiedades: PHP no permite que una
 * clase que usa un trait redeclare el valor por defecto de una propiedad
 * definida en ese trait (error fatal de "incompatible declaration").
 */
trait LogsActivity
{
    /**
     * Campos que NUNCA se escriben en old_value/new_value, sin importar el
     * modelo. created_at/updated_at se excluyen porque Eloquent los toca en
     * CADA save() aunque no cambie nada de negocio — dejarlos generaría una
     * fila de bitácora por cada guardado no-op.
     */
    private const BASE_EXCLUDED_FIELDS = [
        'password',
        'password_hash',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /** Override por modelo, ej.: protected static function logExcept(): array { return ['signature_data']; } */
    protected static function logExcept(): array
    {
        return [];
    }

    /** Override por modelo, ej.: protected static function logEntityType(): string { return 'purchase_order'; } */
    protected static function logEntityType(): string
    {
        return Str::snake(class_basename(static::class));
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::writeActivityLog(
                $model,
                'created',
                null,
                static::filterLogAttributes($model->attributesToArray())
            );
        });

        static::updated(function ($model) {
            $changes = static::filterLogAttributes($model->getChanges());

            if (empty($changes)) {
                // Todo lo que cambió estaba excluido — no genera fila.
                return;
            }

            $original = collect($changes)->keys()
                ->mapWithKeys(fn ($key) => [$key => $model->getOriginal($key)])
                ->all();

            static::writeActivityLog($model, 'updated', $original, $changes);
        });

        static::deleted(function ($model) {
            static::writeActivityLog(
                $model,
                'deleted',
                static::filterLogAttributes($model->attributesToArray()),
                null
            );
        });
    }

    private static function filterLogAttributes(array $attributes): array
    {
        $excluded = array_merge(self::BASE_EXCLUDED_FIELDS, static::logExcept());

        return Arr::except($attributes, $excluded);
    }

    private static function writeActivityLog($model, string $action, ?array $old, ?array $new): void
    {
        // request()/auth() nunca lanzan excepción fuera de un contexto HTTP,
        // pero se resuelven explícitamente a null en consola para que quede
        // documentado por qué un log de seeder/tinker/job trae ip_address y
        // user_agent vacíos — no es un bug, es el contexto.
        $inConsole = app()->runningInConsole();

        SystemLog::create([
            'entity_type' => static::logEntityType(),
            'entity_id' => $model->getKey(),
            'action' => $action,
            'description' => null,
            'old_value' => $old,
            'new_value' => $new,
            'performed_by_user_id' => auth()->id(),
            'performed_at' => now(),
            'ip_address' => $inConsole ? null : request()?->ip(),
            'user_agent' => $inConsole ? null : request()?->userAgent(),
        ]);
    }
}
