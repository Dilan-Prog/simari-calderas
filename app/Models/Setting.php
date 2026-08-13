<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'setting';
    }

    // Tabla genérica clave→valor compartida por Configuración del Sitio E
    // Integraciones (esta última guarda ahí credenciales SMTP encriptadas).
    // La sensibilidad depende del `key` de cada fila, no hay forma de
    // excluir por valor de campo — se excluye `value` en bloque para nunca
    // arriesgar que una credencial, ni siquiera encriptada, llegue al log.
    protected static function logExcept(): array
    {
        return ['value'];
    }

    public $timestamps = false;

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['key', 'value', 'type', 'group_name', 'is_public', 'updated_by_user_id'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();

        if (!$row) {
            return $default;
        }

        return match ($row->type) {
            'boolean' => (bool) $row->value,
            'integer' => (int) $row->value,
            'decimal' => (float) $row->value,
            'json'    => json_decode($row->value, true),
            default   => $row->value,
        };
    }

    public static function set(string $key, $value, ?int $updatedByUserId = null): void
    {
        static::updateOrCreate(['key' => $key], [
            'value'               => is_array($value) ? json_encode($value) : $value,
            'updated_by_user_id'  => $updatedByUserId,
        ]);
    }

    /**
     * Dirección de la empresa compuesta en una sola línea legible, a partir
     * de las claves footer.address_* (Configuración del Sitio > Footer) —
     * fuente única de verdad usada por el footer público y los PDFs. Omite
     * en silencio cualquier parte vacía (ej. si todavía no se llena la
     * colonia) en vez de dejar comas sueltas.
     */
    public static function companyAddressLine(): string
    {
        $postalCode = static::get('footer.address_postal_code');

        $parts = array_filter([
            static::get('footer.address_street'),
            static::get('footer.address_colony'),
            $postalCode ? 'C.P. ' . $postalCode : null,
            static::get('footer.address_city'),
            static::get('footer.address_state'),
        ], fn ($part) => !empty($part));

        return implode(', ', $parts);
    }
}
