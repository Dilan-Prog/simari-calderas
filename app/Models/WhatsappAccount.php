<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cuenta de WhatsApp Business (Meta Cloud API). El access_token real nunca
 * se guarda en claro — se cifra con Crypt::encryptString() en
 * `encrypted_access_token`, mismo patrón que Credential::storePayload().
 */
class WhatsappAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'phone_number_id',
        'whatsapp_business_account_id',
        'provider',
        'webhook_verify_token',
        'encrypted_access_token',
        'webhook_url',
        'is_active',
    ];

    protected $hidden = [
        'encrypted_access_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'provider' => 'meta_cloud_api',
    ];

    /**
     * Access token descifrado, listo para usarse en la llamada a Graph API.
     * Nunca se serializa (ver $hidden); se accede explícitamente desde
     * WhatsappService.
     */
    public function getAccessTokenAttribute(): ?string
    {
        if (empty($this->encrypted_access_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_access_token);
        } catch (DecryptException $e) {
            return null;
        }
    }

    public static function encryptAccessToken(string $token): string
    {
        return Crypt::encryptString($token);
    }

    public function conversations()
    {
        return $this->hasMany(WhatsappConversation::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
