<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
}
