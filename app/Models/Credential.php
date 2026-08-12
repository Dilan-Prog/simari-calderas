<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Credential extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'encrypted_payload',
        'created_by',
    ];

    protected $hidden = [
        'encrypted_payload',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPayloadAttribute()
    {
        if (empty($this->encrypted_payload)) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($this->encrypted_payload), true);
        } catch (DecryptException $e) {
            return null;
        }
    }

    public static function storePayload(array $data): string
    {
        return Crypt::encryptString(json_encode($data));
    }
}
