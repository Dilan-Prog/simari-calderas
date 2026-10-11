<?php

namespace App\Services\Google;

use App\Contracts\GoogleConversionUploader;
use App\Models\Credential;
use App\Models\GoogleConversionUpload;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Sube conversiones con la Data Manager API de Google (events:ingest).
 *
 * Credenciales (nunca hardcodeadas): Credential de tipo
 * config('google_conversions.credential_type') con payload JSON, o claves
 * google_ads.* de la tabla settings. Endpoint configurable.
 *
 * SUPUESTO: los ajustes (RESTATEMENT/RETRACTION) viajan en el mismo endpoint
 * con el campo adjustmentType y el mismo transactionId; si Google expone otro
 * contrato, solo hay que tocar buildEvent().
 */
class DataManagerUploader implements GoogleConversionUploader
{
    private ?array $config = null;

    public function isConfigured(): bool
    {
        $c = $this->settings();

        return ! empty($c['client_id']) && ! empty($c['client_secret'])
            && ! empty($c['refresh_token']) && ! empty($c['account_id']);
    }

    public function upload(GoogleConversionUpload $upload): array
    {
        $c = $this->settings();
        $actionId = $c['conversion_actions'][$upload->action] ?? config('google_conversions.conversion_actions.' . $upload->action);

        if (empty($actionId)) {
            return $this->fail("No hay acción de conversión configurada para '{$upload->action}'.");
        }

        try {
            $token = $this->accessToken($c);
        } catch (\Throwable $e) {
            return $this->fail('OAuth: ' . $e->getMessage());
        }

        $destination = [
            'operatingAccount'   => ['accountType' => 'GOOGLE_ADS', 'accountId' => (string) $c['account_id']],
            'productDestinationId' => (string) $actionId,
        ];
        if (! empty($c['login_account_id'])) {
            $destination['loginAccount'] = ['accountType' => 'GOOGLE_ADS', 'accountId' => (string) $c['login_account_id']];
        }

        $body = [
            'destinations' => [$destination],
            'events'       => [$this->buildEvent($upload)],
            'encoding'     => 'HEX',
            'validateOnly' => false,
        ];

        try {
            $response = Http::withToken($token)
                ->timeout((int) config('google_conversions.http_timeout', 20))
                ->acceptJson()
                ->asJson()
                ->post(config('google_conversions.endpoint'), $body);
        } catch (\Throwable $e) {
            return $this->fail('Red: ' . $e->getMessage());
        }

        $json = $response->json() ?? ['body' => mb_substr($response->body(), 0, 1000)];

        if ($response->successful()) {
            return ['ok' => true, 'error' => null, 'response' => $json];
        }

        $message = $json['error']['message'] ?? ('HTTP ' . $response->status());

        return ['ok' => false, 'error' => 'HTTP ' . $response->status() . ': ' . $message, 'response' => $json];
    }

    public function buildEvent(GoogleConversionUpload $u): array
    {
        $event = [
            'eventTimestamp' => $u->conversion_date_time->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'transactionId'  => $u->order_id,
            'eventSource'    => 'WEB',
        ];

        if ($u->conversion_value !== null) {
            $event['conversionValue'] = (float) $u->conversion_value;
            $event['currency'] = $u->currency;
        }

        if (in_array($u->identifier_type, ['gclid', 'gbraid', 'wbraid'], true)) {
            $event['adIdentifiers'] = [$u->identifier_type => $u->identifier];
        } elseif ($u->identifier_type === 'user_data') {
            $ids = [];
            if (! empty($u->user_data['email_sha256'])) {
                $ids[] = ['emailAddress' => $u->user_data['email_sha256']];
            }
            if (! empty($u->user_data['phone_sha256'])) {
                $ids[] = ['phoneNumber' => $u->user_data['phone_sha256']];
            }
            $event['userData'] = ['userIdentifiers' => $ids];
        }

        if ($u->operation !== 'upload') {
            $event['adjustmentType'] = strtoupper($u->operation); // RESTATEMENT | RETRACTION
        }

        return $event;
    }

    private function accessToken(array $c): string
    {
        return Cache::remember('google_dm_token_' . md5($c['client_id'] . $c['refresh_token']), now()->addMinutes(50), function () use ($c) {
            $res = Http::asForm()->timeout(15)->post(config('google_conversions.token_endpoint'), [
                'client_id'     => $c['client_id'],
                'client_secret' => $c['client_secret'],
                'refresh_token' => $c['refresh_token'],
                'grant_type'    => 'refresh_token',
            ]);

            if (! $res->successful() || empty($res->json('access_token'))) {
                throw new \RuntimeException('no se pudo obtener el token (' . $res->status() . ')');
            }

            return $res->json('access_token');
        });
    }

    private function settings(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $payload = [];
        try {
            $cred = Credential::where('type', config('google_conversions.credential_type'))->latest()->first();
            $payload = $cred?->payload ?? [];
        } catch (\Throwable $e) {
            $payload = [];
        }

        $fromSettings = fn (string $k) => Setting::get('google_ads.' . $k);

        return $this->config = [
            'client_id'          => $payload['client_id'] ?? $fromSettings('client_id'),
            'client_secret'      => $payload['client_secret'] ?? $fromSettings('client_secret'),
            'refresh_token'      => $payload['refresh_token'] ?? $fromSettings('refresh_token'),
            'account_id'         => $payload['account_id'] ?? $fromSettings('account_id'),
            'login_account_id'   => $payload['login_account_id'] ?? $fromSettings('login_account_id'),
            'conversion_actions' => $payload['conversion_actions'] ?? [],
        ];
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'error' => $error, 'response' => []];
    }
}
