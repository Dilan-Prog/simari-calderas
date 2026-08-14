<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

/**
 * Envoltura de solo lectura sobre config/automatable_modules.php (Fase 16).
 *
 * Único punto de acceso al registro de módulos automatizables -- ni el
 * observer genérico ni los servicios de workflow deben leer
 * config('automatable_modules') directamente, para poder centralizar aquí
 * cache/derivaciones (p. ej. fieldsFor() vía reflexión de $fillable).
 */
class AutomatableModuleRegistry
{
    /**
     * Cache en memoria del request para fieldsFor(), ya que la reflexión de
     * $fillable no cambia dentro de una misma request.
     *
     * @var array<string, array>
     */
    protected array $fieldsCache = [];

    /**
     * Todas las entradas del registro, indexadas por `type` de Workflow.
     */
    public function all(): array
    {
        return config('automatable_modules', []);
    }

    /**
     * Entrada completa del registro para un `type` dado, o null si no existe.
     */
    public function entry(string $type): ?array
    {
        return $this->all()[$type] ?? null;
    }

    /**
     * Resuelve el `type` de Workflow correspondiente a la clase real de un
     * modelo (usa get_class($model), no instanceof, para que subclases no
     * se confundan entre sí).
     */
    public function typeForModel(Model $model): ?string
    {
        $modelClass = get_class($model);

        foreach ($this->all() as $type => $entry) {
            if (($entry['model'] ?? null) === $modelClass) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Nombres de los campos $fillable del modelo real asociado a un `type`,
     * derivados vía reflexión (no se duplican a mano en config). Cacheado en
     * memoria por request.
     */
    public function fieldsFor(string $type): array
    {
        if (array_key_exists($type, $this->fieldsCache)) {
            return $this->fieldsCache[$type];
        }

        $entry = $this->entry($type);

        if (!$entry || empty($entry['model']) || !class_exists($entry['model'])) {
            return $this->fieldsCache[$type] = [];
        }

        $reflection = new ReflectionClass($entry['model']);
        $instance = $reflection->newInstanceWithoutConstructor();

        $fields = method_exists($instance, 'getFillable') ? $instance->getFillable() : [];

        $extra  = $entry['extra_fields'] ?? [];
        $fields = array_values(array_unique(array_merge($fields, $extra)));

        return $this->fieldsCache[$type] = $fields;
    }

    /**
     * Etiqueta legible del módulo (para selects/UI).
     */
    public function label(string $type): ?string
    {
        return $this->entry($type)['label'] ?? null;
    }

    /**
     * Agrupación visual del módulo (CRM/Ecommerce/Servicios/ERP).
     */
    public function group(string $type): ?string
    {
        return $this->entry($type)['group'] ?? null;
    }
}
