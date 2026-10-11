<?php

namespace App\Services;

use App\Contracts\GoogleConversionUploader;
use App\Jobs\SendGoogleConversionUploadJob;
use App\Models\Deal;
use App\Models\GoogleConversionUpload as Upload;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Products;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Conversiones offline a Google Ads (Data Manager API) con tabla de control
 * anti-duplicados. Ver docs en config/google_conversions.php.
 */
class GoogleConversionService
{
    public function __construct(private ?GoogleConversionUploader $uploader = null)
    {
    }

    public function uploader(): GoogleConversionUploader
    {
        return $this->uploader ??= app()->bound(GoogleConversionUploader::class)
            ? app(GoogleConversionUploader::class)
            : app(\App\Services\Google\DataManagerUploader::class);
    }

    // ── Hashing / identificadores ───────────────────────────────────────

    /** SHA-256 en hex de un correo: minúsculas, sin espacios (Gmail: sin puntos ni +alias). */
    public static function hashEmail(?string $email): ?string
    {
        $email = $email === null ? '' : mb_strtolower(preg_replace('/\s+/', '', $email));
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        [$user, $domain] = explode('@', $email, 2);
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $user = str_replace('.', '', explode('+', $user, 2)[0]);
            $domain = 'gmail.com';
        }

        return hash('sha256', $user . '@' . $domain);
    }

    /** SHA-256 en hex de un teléfono en E.164 (+52...). */
    public static function hashPhone(?string $phone): ?string
    {
        $e164 = LeadService::normalizePhone($phone);

        return $e164 ? hash('sha256', $e164) : null;
    }

    /**
     * Elige el identificador: GCLID → GBRAID → WBRAID (vigentes, ventana de 90
     * días) → correo/teléfono con hash. Devuelve type 'none' si no hay nada,
     * y 'expired' => true si había clics pero todos vencidos.
     *
     * @return array{type:string, value:?string, user_data:?array, click_at:?Carbon, expired:bool}
     */
    public function resolveIdentifier(?Lead $lead, ?Deal $deal, Carbon $conversionAt): array
    {
        $window = (int) config('google_conversions.gclid_window_days', 90);
        $expired = false;

        if ($lead) {
            foreach (array_filter([$lead->last_click, $lead->first_click]) as $click) {
                $clickAt = isset($click['seen_at']) ? Carbon::parse($click['seen_at']) : ($lead->click_at ?? null);
                foreach (['gclid', 'gbraid', 'wbraid'] as $type) {
                    if (empty($click[$type])) {
                        continue;
                    }
                    if ($clickAt && $clickAt->copy()->addDays($window)->lt($conversionAt)) {
                        $expired = true;
                        continue;
                    }

                    return ['type' => $type, 'value' => $click[$type], 'user_data' => null, 'click_at' => $clickAt, 'expired' => false];
                }
            }
            // Datos planos por si el lead no trae snapshots JSON.
            foreach (['gclid', 'gbraid', 'wbraid'] as $type) {
                if (! empty($lead->{$type}) && empty($lead->last_click) && empty($lead->first_click)) {
                    $clickAt = $lead->click_at;
                    if ($clickAt && $clickAt->copy()->addDays($window)->lt($conversionAt)) {
                        $expired = true;
                        continue;
                    }

                    return ['type' => $type, 'value' => $lead->{$type}, 'user_data' => null, 'click_at' => $clickAt, 'expired' => false];
                }
            }
        }

        $email = $lead?->contact_email ?: $deal?->contact_snapshot_email ?: $deal?->customer?->email;
        $phone = $lead?->phone_normalized ?: $deal?->contact_snapshot_phone ?: $deal?->customer?->phone;
        $userData = array_filter([
            'email_sha256' => self::hashEmail($email),
            'phone_sha256' => self::hashPhone($phone),
        ]);

        if ($userData) {
            return ['type' => 'user_data', 'value' => null, 'user_data' => $userData, 'click_at' => null, 'expired' => $expired];
        }

        return ['type' => 'none', 'value' => null, 'user_data' => null, 'click_at' => null, 'expired' => $expired];
    }

    // ── Reglas desde Negocios ───────────────────────────────────────────

    public function isQuotedStage(?PipelineStage $stage): bool
    {
        if (! $stage || $stage->is_won || $stage->is_lost) {
            return false;
        }

        $slugs = array_map('strtolower', (array) config('google_conversions.quoted_stage_slugs', []));

        return in_array(strtolower((string) $stage->slug), $slugs, true)
            || stripos((string) $stage->name, 'cotizad') !== false;
    }

    /** Llamado al registrarse un movimiento de etapa (DealStageHistory::created). */
    public function handleStageMove(Deal $deal, ?PipelineStage $from, ?PipelineStage $to): void
    {
        if (! $to) {
            return;
        }

        if ($to->is_won) {
            app(LeadService::class)->markConvertedByDeal($deal);
            $this->queueConversion($deal, Upload::ACTION_WON);

            return;
        }

        if ($from && $from->is_won) {
            // Salió de "ganada" (reabierto o perdido): se retira la conversión.
            $this->retract($deal);
        }

        if ($this->isQuotedStage($to)) {
            $this->queueConversion($deal, Upload::ACTION_QUOTED);
        }
    }

    /** Llamado cuando cambia monto/moneda del negocio. */
    public function handleAmountChange(Deal $deal): void
    {
        $won = Upload::where('deal_id', $deal->id)->where('action', Upload::ACTION_WON)->where('operation', 'upload')->first();
        if (! $won || $won->status === 'omitida') {
            return;
        }

        $newValue = $this->amountInMxn($deal);

        if ($won->status === 'pendiente') {
            $won->update(['conversion_value' => $newValue]); // aún no sale: basta con corregirla
            return;
        }
        if ($won->status !== 'enviada') {
            return;
        }

        $retracted = Upload::where('parent_upload_id', $won->id)->where('operation', 'retraction')->whereIn('status', ['pendiente', 'enviada'])->exists();
        if ($retracted) {
            return;
        }

        $last = Upload::where('parent_upload_id', $won->id)->where('operation', 'restatement')
            ->whereIn('status', ['pendiente', 'enviada'])->orderByDesc('id')->first();
        $current = (float) ($last->conversion_value ?? $won->conversion_value);

        if (abs($current - $newValue) < 0.005) {
            return;
        }

        $this->queueAdjustment($won, 'restatement', $newValue);
    }

    /** Retira (RETRACTION) la venta ganada ya subida; si aún no se subió, la omite. */
    public function retract(Deal $deal): ?Upload
    {
        $won = Upload::where('deal_id', $deal->id)->where('action', Upload::ACTION_WON)->where('operation', 'upload')->first();
        if (! $won) {
            return null;
        }

        if (in_array($won->status, ['pendiente', 'fallida'], true)) {
            $won->update(['status' => 'omitida', 'last_error' => 'Venta cancelada/revertida antes de subirse.']);

            return $won;
        }
        if ($won->status !== 'enviada') {
            return null;
        }

        $existing = Upload::where('parent_upload_id', $won->id)->where('operation', 'retraction')->whereIn('status', ['pendiente', 'enviada'])->first();
        if ($existing) {
            return $existing;
        }

        return $this->queueAdjustment($won, 'retraction', null);
    }

    public function amountInMxn(Deal $deal): float
    {
        $amount = (float) $deal->amount;

        return strtoupper((string) $deal->currency) === 'USD'
            ? round($amount * Products::exchangeRate(), 2)
            : round($amount, 2);
    }

    // ── Creación de filas ───────────────────────────────────────────────

    public function leadForDeal(Deal $deal): ?Lead
    {
        $lead = Lead::where('deal_id', $deal->id)->orderByDesc('id')->first();
        if ($lead) {
            return $lead;
        }

        $phone = LeadService::normalizePhone($deal->contact_snapshot_phone);
        $email = LeadService::normalizeEmail($deal->contact_snapshot_email);

        return Lead::where(function ($q) use ($phone, $email, $deal) {
            if ($deal->customer_id) {
                $q->orWhere('customer_id', $deal->customer_id);
            }
            if ($phone) {
                $q->orWhere('phone_normalized', $phone);
            }
            if ($email) {
                $q->orWhere('contact_email', $email);
            }
            $q->orWhereRaw('1 = 0');
        })->orderByDesc('id')->first();
    }

    public function queueConversion(Deal $deal, string $action): Upload
    {
        $orderId = 'deal-' . $deal->id . '-' . $action;
        $dedupe = $orderId . ':upload';

        $existing = Upload::where('dedupe_key', $dedupe)->first();
        if ($existing) {
            return $existing; // anti-duplicado
        }

        $lead = $this->leadForDeal($deal);
        $now = now();
        $ident = $this->resolveIdentifier($lead, $deal, $now);

        $when = $now->copy();
        if ($ident['click_at'] && $when->lte($ident['click_at'])) {
            $when = $ident['click_at']->copy()->addSecond(); // la conversión debe ser posterior al clic
        }

        $status = 'pendiente';
        $error = null;
        if ($ident['type'] === 'none') {
            $status = $ident['expired'] ? 'omitida' : 'sin_atribucion';
            $error = $ident['expired']
                ? 'El identificador de clic venció (ventana de ' . config('google_conversions.gclid_window_days') . ' días) y no hay correo/teléfono.'
                : 'El lead no tiene GCLID/GBRAID/WBRAID ni correo/teléfono.';
        }

        $window = (int) config('google_conversions.gclid_window_days', 90);

        $upload = Upload::create([
            'lead_id'              => $lead?->id,
            'deal_id'              => $deal->id,
            'action'               => $action,
            'operation'            => 'upload',
            'order_id'             => $orderId,
            'dedupe_key'           => $dedupe,
            'identifier_type'      => $ident['type'],
            'identifier'           => $ident['value'],
            'user_data'            => $ident['user_data'],
            'conversion_date_time' => $when->copy()->utc(),
            'timezone'             => config('google_conversions.timezone', 'America/Mexico_City'),
            'conversion_value'     => $action === Upload::ACTION_WON ? $this->amountInMxn($deal) : null,
            'currency'             => 'MXN',
            'click_at'             => $ident['click_at'],
            'gclid_expires_at'     => $ident['click_at'] && $ident['value'] ? $ident['click_at']->copy()->addDays($window) : null,
            'status'               => $status,
            'last_error'           => $error,
        ]);

        if ($status === 'pendiente') {
            SendGoogleConversionUploadJob::dispatch($upload->id);
        }

        return $upload;
    }

    private function queueAdjustment(Upload $original, string $operation, ?float $newValue): Upload
    {
        $adj = Upload::create([
            'lead_id'              => $original->lead_id,
            'deal_id'              => $original->deal_id,
            'parent_upload_id'     => $original->id,
            'action'               => $original->action,
            'operation'            => $operation,
            'order_id'             => $original->order_id, // mismo order_id que la conversión original
            'dedupe_key'           => null,
            'identifier_type'      => $original->identifier_type,
            'identifier'           => $original->identifier,
            'user_data'            => $original->user_data,
            'conversion_date_time' => $original->conversion_date_time, // fecha de la conversión original
            'timezone'             => $original->timezone,
            'conversion_value'     => $operation === 'restatement' ? $newValue : null,
            'currency'             => $original->currency,
            'click_at'             => $original->click_at,
            'gclid_expires_at'     => $original->gclid_expires_at,
            'status'               => 'pendiente',
        ]);

        SendGoogleConversionUploadJob::dispatch($adj->id);

        return $adj;
    }

    // ── Envío con reintentos ────────────────────────────────────────────

    /** Un intento de envío. Lo invoca el job; también el reintento manual. */
    public function process(Upload $upload): Upload
    {
        $upload->refresh();

        if (in_array($upload->status, ['enviada', 'omitida', 'sin_atribucion'], true)) {
            return $upload;
        }

        if (! $this->uploader()->isConfigured()) {
            $upload->update(['status' => 'pendiente', 'last_error' => 'Credenciales de Google no configuradas; la conversión queda en cola.']);

            return $upload;
        }

        // Ventana del GCLID: si venció mientras esperaba en cola, se omite (salvo que haya user_data).
        if (in_array($upload->identifier_type, ['gclid', 'gbraid', 'wbraid'], true)
            && $upload->gclid_expires_at && $upload->gclid_expires_at->lt(now())) {
            $upload->update(['status' => 'omitida', 'last_error' => 'El identificador de clic venció (90 días).']);

            return $upload;
        }

        $upload->attempts = $upload->attempts + 1;

        try {
            $result = $this->uploader()->upload($upload);
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'error' => $e->getMessage(), 'response' => []];
        }

        if ($result['ok']) {
            $upload->fill([
                'status' => 'enviada', 'sent_at' => now(), 'last_error' => null,
                'response' => $result['response'] ?? null, 'next_attempt_at' => null,
            ])->save();

            return $upload;
        }

        $max = (int) config('google_conversions.max_attempts', 3);
        $upload->last_error = $result['error'] ?? 'Error desconocido';
        $upload->response = $result['response'] ?? null;

        if ($upload->attempts >= $max) {
            $upload->status = 'fallida';
            $upload->next_attempt_at = null;
            $upload->save();
            $this->alertFailure($upload);

            return $upload;
        }

        $delay = (int) config('google_conversions.backoff_base_seconds', 120) * (2 ** ($upload->attempts - 1));
        $upload->status = 'pendiente';
        $upload->next_attempt_at = now()->addSeconds($delay);
        $upload->save();

        SendGoogleConversionUploadJob::dispatch($upload->id)->delay($upload->next_attempt_at);

        return $upload;
    }

    /** Reintento manual: reinicia intentos y vuelve a encolar. */
    public function retry(Upload $upload): Upload
    {
        if (in_array($upload->status, ['enviada'], true)) {
            return $upload;
        }

        $upload->update(['status' => 'pendiente', 'attempts' => 0, 'alerted_at' => null, 'next_attempt_at' => null]);
        SendGoogleConversionUploadJob::dispatch($upload->id);

        return $upload->fresh();
    }

    private function alertFailure(Upload $upload): void
    {
        if ($upload->alerted_at) {
            return;
        }

        Log::error('Conversión a Google fallida tras ' . $upload->attempts . ' intentos', [
            'upload_id' => $upload->id, 'order_id' => $upload->order_id, 'error' => $upload->last_error,
        ]);

        $taskable = $upload->deal_id ? Deal::withTrashed()->find($upload->deal_id) : null;
        $taskable ??= $upload->lead_id ? Lead::find($upload->lead_id) : null;

        if ($taskable) {
            Task::create([
                'taskable_type' => get_class($taskable),
                'taskable_id'   => $taskable->getKey(),
                'assigned_to'   => $taskable instanceof Lead ? $taskable->assigned_to : ($taskable->owner_id ?? null),
                'title'         => 'Conversión a Google fallida (' . $upload->order_id . ')',
                'description'   => 'Falló ' . $upload->attempts . ' veces: ' . mb_substr((string) $upload->last_error, 0, 300)
                    . '. Revisa Conversiones a Google > subida #' . $upload->id . '.',
                'due_at'        => now()->addDay(),
                'status'        => 'open',
            ]);
        }

        $upload->forceFill(['alerted_at' => now()])->save();
    }

    // ── Resumen ─────────────────────────────────────────────────────────

    public function summary(): array
    {
        $days = (int) config('google_conversions.expiring_days', 14);

        return [
            'enviadas'       => Upload::where('status', 'enviada')->count(),
            'pendientes'     => Upload::where('status', 'pendiente')->count(),
            'fallidas'       => Upload::where('status', 'fallida')->count(),
            'sin_atribucion' => Upload::where('status', 'sin_atribucion')->count(),
            'omitidas'       => Upload::where('status', 'omitida')->count(),
            'por_vencer'     => Upload::where('status', 'pendiente')
                ->whereIn('identifier_type', ['gclid', 'gbraid', 'wbraid'])
                ->whereNotNull('gclid_expires_at')
                ->whereBetween('gclid_expires_at', [now(), now()->addDays($days)])
                ->count(),
        ];
    }
}
