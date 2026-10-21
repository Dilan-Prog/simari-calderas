<?php

namespace App\Services;

use App\Models\AdVisit;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadCall;
use App\Models\Pipeline;
use App\Models\PoolCalculatorLead;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bandeja unificada de leads: captura (con detección de contacto existente
 * y atribución primer/último clic), llamadas y conversión a Negocio/Cliente.
 */
class LeadService
{
    /** Ajustes (tabla settings, con default si no existen). */
    public static function autoCreateDeal(): bool
    {
        return (bool) Setting::get('crm.lead_auto_deal', false);
    }

    public static function callMinSeconds(): int
    {
        return (int) Setting::get('crm.call_min_seconds', 30);
    }

    // ── Normalización ───────────────────────────────────────────────────

    /**
     * Teléfono a formato internacional E.164. Sin código de país asume +52
     * (México). Devuelve null si no parece un teléfono válido.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $raw = trim($phone);
        $hasPlus = str_starts_with($raw, '+') || str_starts_with($raw, '00');
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($raw, '00')) {
            $digits = substr($digits, 2);
        }

        if (! $hasPlus) {
            // Prefijos antiguos de marcación local en México: 044 / 045 + 10 dígitos.
            if (strlen($digits) === 13 && (str_starts_with($digits, '044') || str_starts_with($digits, '045'))) {
                $digits = substr($digits, 3);
            }
            if (strlen($digits) === 10) {
                return '+52' . $digits;
            }
            // 52 + 10 dígitos
            if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
                return '+' . $digits;
            }
            // Formato histórico de móvil: 521 + 10 dígitos
            if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
                return '+52' . substr($digits, 3);
            }
        } elseif (str_starts_with($digits, '521') && strlen($digits) === 13) {
            return '+52' . substr($digits, 3);
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return '+' . $digits;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = $email === null ? '' : mb_strtolower(trim($email));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    // ── Búsqueda de contacto existente ──────────────────────────────────

    public function findCustomer(?string $phoneE164, ?string $email): ?Customer
    {
        if ($email) {
            $byEmail = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        if ($phoneE164) {
            $last10 = substr(preg_replace('/\D+/', '', $phoneE164), -10);
            if (strlen($last10) === 10) {
                return Customer::whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),')',''),'+','') LIKE ?",
                    ['%' . $last10]
                )->first();
            }
        }

        return null;
    }

    public function findOpenLead(?string $phoneE164, ?string $email, ?int $customerId = null): ?Lead
    {
        if (! $phoneE164 && ! $email && ! $customerId) {
            return null;
        }

        return Lead::open()
            ->where(function ($q) use ($phoneE164, $email, $customerId) {
                if ($phoneE164) {
                    $q->orWhere('phone_normalized', $phoneE164);
                }
                if ($email) {
                    $q->orWhere('contact_email', $email);
                }
                if ($customerId) {
                    $q->orWhere('customer_id', $customerId);
                }
            })
            ->orderByDesc('id')
            ->first();
    }

    // ── Atribución ──────────────────────────────────────────────────────

    /** @return array{first: ?array, last: ?array} snapshots desde AdVisit */
    public function attributionFromVisit(?string $visitorUuid): array
    {
        if (! $visitorUuid) {
            return ['first' => null, 'last' => null];
        }

        $visit = AdVisit::where('visitor_uuid', $visitorUuid)->first();
        if (! $visit) {
            return ['first' => null, 'last' => null];
        }

        $first = $this->clickSnapshot([
            'gclid'        => $visit->first_gclid,
            'gbraid'       => $visit->first_gbraid,
            'wbraid'       => $visit->first_wbraid,
            'utm_source'   => $visit->first_utm_source,
            'utm_medium'   => $visit->first_utm_medium,
            'utm_campaign' => $visit->first_utm_campaign,
            'utm_content'  => $visit->first_utm_content,
            'utm_term'     => $visit->first_utm_term,
            'landing_url'  => $visit->first_landing_url,
            'seen_at'      => $visit->first_seen_at,
        ]);

        $last = $this->clickSnapshot([
            'gclid'        => $visit->gclid,
            'gbraid'       => $visit->gbraid,
            'wbraid'       => $visit->wbraid,
            'utm_source'   => $visit->utm_source,
            'utm_medium'   => $visit->utm_medium,
            'utm_campaign' => $visit->utm_campaign,
            'utm_content'  => $visit->utm_content,
            'utm_term'     => $visit->utm_term,
            'landing_url'  => $visit->landing_url,
            'seen_at'      => $visit->last_seen_at,
        ]);

        return ['first' => $first, 'last' => $last];
    }

    /** Devuelve null si el snapshot no trae ningún dato de anuncio. */
    private function clickSnapshot(array $data): ?array
    {
        $hasAd = false;
        foreach (['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_campaign'] as $k) {
            if (! empty($data[$k])) {
                $hasAd = true;
            }
        }
        if (! $hasAd) {
            return null;
        }

        $data['seen_at'] = $data['seen_at'] instanceof Carbon ? $data['seen_at']->toIso8601String() : $data['seen_at'];

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    /** Aplica atribución sin pisar el primer clic; el último se actualiza. */
    private function applyAttribution(Lead $lead, array $attr): void
    {
        if ($attr['first'] && empty($lead->first_click)) {
            $lead->first_click = $attr['first'];
        }

        $last = $attr['last'];
        if ($last) {
            $isNewer = ! $lead->last_click
                || ($last['seen_at'] ?? '') >= ($lead->last_click['seen_at'] ?? '')
                || ($last['gclid'] ?? null) !== ($lead->last_click['gclid'] ?? null);

            if ($isNewer) {
                $lead->last_click = $last;
                foreach (['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $k) {
                    $lead->{$k} = $last[$k] ?? null;
                }
                $lead->click_at = isset($last['seen_at']) ? Carbon::parse($last['seen_at']) : now();
            }
        }
    }

    // ── Captura ─────────────────────────────────────────────────────────

    /**
     * Registra un contacto entrante. Si ya hay un lead abierto para ese
     * teléfono/correo/cliente lo actualiza (conserva el primer clic y guarda
     * el último) en lugar de duplicarlo.
     *
     * $data: source, name, phone, email, visitor_uuid, customer_id,
     *        whatsapp_conversation_id, pool_calculator_lead_id, notes, at
     */
    public function capture(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            $phone = self::normalizePhone($data['phone'] ?? null);
            $email = self::normalizeEmail($data['email'] ?? null);
            $source = array_key_exists($data['source'] ?? '', Lead::SOURCES) ? $data['source'] : 'manual';
            $at = isset($data['at']) ? Carbon::parse($data['at']) : now();

            $customer = ! empty($data['customer_id'])
                ? Customer::find($data['customer_id'])
                : $this->findCustomer($phone, $email);

            $visitor = $data['visitor_uuid'] ?? $customer?->visitor_uuid;
            $attr = $this->attributionFromVisit($visitor);

            $lead = null;
            if (! empty($data['pool_calculator_lead_id'])) {
                $lead = Lead::where('pool_calculator_lead_id', $data['pool_calculator_lead_id'])->first();
            }
            if (! $lead && ! empty($data['whatsapp_conversation_id'])) {
                $lead = Lead::open()->where('whatsapp_conversation_id', $data['whatsapp_conversation_id'])->first();
            }
            $lead ??= $this->findOpenLead($phone, $email, $customer?->id);

            $isNew = ! $lead;
            $lead ??= new Lead(['status' => 'nuevo', 'source' => $source]);

            // Completa datos de contacto solo donde faltan.
            $name = trim((string) ($data['name'] ?? '')) ?: ($customer ? trim($customer->first_name . ' ' . $customer->last_name) : null);
            $lead->contact_name ??= $name ?: null;
            if ($phone && ! $lead->phone_normalized) {
                $lead->phone_normalized = $phone;
                $lead->contact_phone = $data['phone'];
            }
            $lead->contact_email ??= $email;
            $lead->customer_id ??= $customer?->id;
            $lead->visitor_uuid ??= $visitor;
            if (! empty($data['whatsapp_conversation_id'])) {
                $lead->whatsapp_conversation_id = $data['whatsapp_conversation_id'];
            }
            if (! empty($data['pool_calculator_lead_id'])) {
                $lead->pool_calculator_lead_id = $data['pool_calculator_lead_id'];
            }
            if (! empty($data['notes'])) {
                $lead->notes = trim(($lead->notes ? $lead->notes . "\n" : '') . $data['notes']);
            }

            $this->applyAttribution($lead, $attr);
            $lead->last_activity_at = $at;
            $lead->save();

            if ($isNew && self::autoCreateDeal()) {
                $this->convertToDeal($lead);
            }

            return $lead->fresh();
        });
    }

    /**
     * Solicitud llegada por correo (n8n). Idempotente por message_id.
     *
     * @return array{duplicate:bool,lead_id:?int,task_id:?int,is_new_lead:bool,customer_id:?int,assigned_to:?int}
     */
    public function captureFromEmail(array $data): array
    {
        $existing = \App\Models\LeadInboundEmail::where('message_id', $data['message_id'])->first();
        if ($existing) {
            return [
                'duplicate'   => true,
                'lead_id'     => $existing->lead_id,
                'task_id'     => $existing->task_id,
                'is_new_lead' => $existing->is_new_lead,
                'customer_id' => $existing->lead?->customer_id,
                'assigned_to' => $existing->lead?->assigned_to,
            ];
        }

        return DB::transaction(function () use ($data) {
            $subject = trim((string) ($data['subject'] ?? ''));
            $excerpt = trim((string) ($data['message'] ?? ''));
            $at = isset($data['received_at']) ? Carbon::parse($data['received_at']) : now();
            $maxBefore = (int) Lead::max('id');

            $lead = $this->capture([
                'source' => 'correo',
                'name'   => $data['name'] ?? null,
                'email'  => $data['email'],
                'phone'  => $data['phone'] ?? null,
                'notes'  => 'Correo' . ($subject !== '' ? ': ' . $subject : '') . ($excerpt !== '' ? "\n" . mb_substr($excerpt, 0, 1500) : ''),
                'at'     => $at,
            ]);
            $isNew = $lead->id > $maxBefore;

            if (! empty($data['assigned_to']) && ! $lead->assigned_to) {
                $lead->assigned_to = (int) $data['assigned_to'];
                $lead->save();
            }

            $task = null;
            if (($data['create_task'] ?? true) !== false) {
                $task = Task::create([
                    'taskable_type' => Lead::class,
                    'taskable_id'   => $lead->id,
                    'assigned_to'   => $lead->assigned_to,
                    'title'         => 'Atender solicitud por correo' . ($subject !== '' ? ': ' . mb_substr($subject, 0, 120) : '') . ' · ' . $lead->display_name,
                    'description'   => 'De: ' . (! empty($data['name']) ? $data['name'] . ' <' . $data['email'] . '>' : $data['email'])
                        . ' · ' . $at->format('d/m/Y H:i')
                        . ($excerpt !== '' ? "\n\n" . mb_substr($excerpt, 0, 1500) : ''),
                    'due_at'        => now()->addHours(4),
                    'status'        => 'open',
                ]);
            }

            \App\Models\LeadInboundEmail::create([
                'message_id'  => $data['message_id'],
                'lead_id'     => $lead->id,
                'from_email'  => self::normalizeEmail($data['email']),
                'from_name'   => $data['name'] ?? null,
                'subject'     => $subject !== '' ? mb_substr($subject, 0, 255) : null,
                'excerpt'     => $excerpt !== '' ? mb_substr($excerpt, 0, 5000) : null,
                'received_at' => $at,
                'task_id'     => $task?->id,
                'is_new_lead' => $isNew,
            ]);

            return [
                'duplicate'   => false,
                'lead_id'     => $lead->id,
                'task_id'     => $task?->id,
                'is_new_lead' => $isNew,
                'customer_id' => $lead->customer_id,
                'assigned_to' => $lead->assigned_to,
            ];
        });
    }

    /** Extrae las direcciones de un texto tipo "Nombre <a@b.com>, c@d.com" (o de una lista). */
    public static function extractEmails($value): array
    {
        $text = is_array($value) ? implode(',', array_map('strval', $value)) : (string) $value;
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m);

        return array_values(array_unique(array_filter(array_map(fn ($e) => self::normalizeEmail($e), $m[0]))));
    }

    /**
     * Correo que administracion@ envia a un cliente (n8n lo manda desde la carpeta Enviados). Solo se
     * guarda si el destinatario es un lead que YA tiene una tarea: se liga por hilo (In-Reply-To = un
     * correo entrante ya registrado) o por correo del destinatario. Idempotente por message_id.
     *
     * @return array{matched:bool,duplicate:bool,lead_id:?int,task_id:?int,outbound_email_id:?int}
     */
    public function recordOutboundEmail(array $data): array
    {
        $none = ['matched' => false, 'duplicate' => false, 'lead_id' => null, 'task_id' => null, 'outbound_email_id' => null];

        if ($existing = \App\Models\LeadOutboundEmail::where('message_id', $data['message_id'])->first()) {
            return ['matched' => true, 'duplicate' => true, 'lead_id' => $existing->lead_id, 'task_id' => $existing->task_id, 'outbound_email_id' => $existing->id];
        }

        $recipients = array_values(array_unique(array_merge(self::extractEmails($data['to'] ?? []), self::extractEmails($data['cc'] ?? []))));
        $inReplyTo = trim((string) ($data['in_reply_to'] ?? ''));

        // 1) Por hilo: respuesta a un correo entrante que ya creo la tarea.
        $inbound = $inReplyTo !== '' ? \App\Models\LeadInboundEmail::where('message_id', $inReplyTo)->first() : null;
        $lead = $inbound?->lead;
        $task = $inbound?->task_id ? Task::find($inbound->task_id) : null;

        // 2) Por destinatario: un lead con tarea cuyo correo sea alguno de los destinatarios.
        if (! $lead && $recipients) {
            $lead = Lead::whereIn('contact_email', $recipients)->whereHas('tasks')->orderByDesc('last_activity_at')->orderByDesc('id')->first();
        }
        if (! $lead) {
            return $none;
        }

        $task ??= $lead->tasks()->where('status', '!=', 'closed')->latest('id')->first() ?? $lead->tasks()->latest('id')->first();
        if (! $task) {
            return $none;
        }

        $sentAt = ! empty($data['sent_at']) ? Carbon::parse($data['sent_at']) : now();
        $to = in_array($lead->contact_email, $recipients, true) ? $lead->contact_email : ($recipients[0] ?? $lead->contact_email);

        return DB::transaction(function () use ($data, $lead, $task, $recipients, $sentAt, $to) {
            $subject = trim((string) ($data['subject'] ?? ''));
            $email = \App\Models\LeadOutboundEmail::create([
                'message_id'  => $data['message_id'],
                'lead_id'     => $lead->id,
                'task_id'     => $task->id,
                'from_email'  => self::normalizeEmail($data['from_email'] ?? null),
                'to_email'    => $to,
                'recipients'  => $recipients,
                'subject'     => $subject !== '' ? mb_substr($subject, 0, 255) : null,
                'body'        => isset($data['body']) ? mb_substr((string) $data['body'], 0, 100000) : null,
                'attachments' => ! empty($data['attachments']) ? array_values(array_map('strval', (array) $data['attachments'])) : null,
                'sent_at'     => $sentAt,
            ]);

            $task->logEvent('sent_email', 'Correo enviado a ' . $to . ($subject !== '' ? ': ' . mb_substr($subject, 0, 120) : ''), null, $email->id);

            // Primera respuesta al cliente: alimenta el KPI de la bandeja; la actividad del lead se refresca.
            $lead->first_contact_at ??= $sentAt;
            $lead->last_activity_at = $sentAt;
            $lead->save();

            return ['matched' => true, 'duplicate' => false, 'lead_id' => $lead->id, 'task_id' => $task->id, 'outbound_email_id' => $email->id];
        });
    }

    public function captureFromWhatsapp(WhatsappConversation $conversation, ?WhatsappMessage $message = null): Lead
    {
        $data = [
            'source'                   => 'whatsapp',
            'phone'                    => $conversation->contact_phone,
            'customer_id'              => $conversation->customer_id,
            'whatsapp_conversation_id' => $conversation->id,
            'at'                       => $message?->sent_at ?? now(),
        ];

        // La calculadora de alberca manda "Ref EQ-XXXX" por WhatsApp: ese
        // código enlaza el mensaje con la visita (atribución) del formulario.
        if ($message && $message->content && preg_match('/\bEQ-[A-Z0-9]{4}\b/', strtoupper($message->content), $m)) {
            $pool = PoolCalculatorLead::where('ref', $m[0])->first();
            if ($pool) {
                $data['visitor_uuid'] = $pool->visitor_uuid;
                $existing = Lead::where('pool_calculator_lead_id', $pool->id)->first();
                if ($existing && $existing->isOpen()) {
                    $data['pool_calculator_lead_id'] = $pool->id;
                }
            }
        }

        return $this->capture($data);
    }

    public function captureFromPoolCalculator(PoolCalculatorLead $pool): Lead
    {
        $datos = $pool->payload['datos'] ?? ($pool->payload ?? []);
        $pick = function (array $keys) use ($datos) {
            foreach ($keys as $k) {
                if (! empty($datos[$k]) && is_string($datos[$k])) {
                    return $datos[$k];
                }
            }

            return null;
        };

        return $this->capture([
            'source'                  => 'formulario',
            'name'                    => $pick(['nombre', 'name']) ?? 'Calculadora de alberca ' . $pool->ref,
            'phone'                   => $pick(['telefono', 'phone', 'whatsapp', 'celular']),
            'email'                   => $pick(['correo', 'email']),
            'visitor_uuid'            => $pool->visitor_uuid,
            'pool_calculator_lead_id' => $pool->id,
            'notes'                   => 'Calculadora de alberca · Ref ' . $pool->ref,
        ]);
    }

    // ── Llamadas ────────────────────────────────────────────────────────

    /**
     * Registra una llamada. Una llamada perdida, o contestada con duración
     * menor al umbral, crea una Task de seguimiento.
     *
     * $data: phone, name?, email?, status (contestada|perdida), duration_seconds,
     *        started_at?, direction?, external_id?, tracking_number?, notes?, lead_id?
     */
    public function recordCall(array $data): LeadCall
    {
        $status = ($data['status'] ?? 'contestada') === 'perdida' ? 'perdida' : 'contestada';
        $duration = max(0, (int) ($data['duration_seconds'] ?? 0));
        $startedAt = isset($data['started_at']) ? Carbon::parse($data['started_at']) : now();

        if (! empty($data['external_id'])) {
            $existing = LeadCall::where('external_id', $data['external_id'])->first();
            if ($existing) {
                return $existing; // idempotente
            }
        }

        $lead = ! empty($data['lead_id'])
            ? Lead::findOrFail($data['lead_id'])
            : $this->capture([
                'source'       => 'llamada',
                'phone'        => $data['phone'] ?? null,
                'name'         => $data['name'] ?? null,
                'email'        => $data['email'] ?? null,
                'visitor_uuid' => $data['visitor_uuid'] ?? null,
                'at'           => $startedAt,
            ]);

        $call = $lead->calls()->create([
            'direction'        => ($data['direction'] ?? 'entrante') === 'saliente' ? 'saliente' : 'entrante',
            'status'           => $status,
            'duration_seconds' => $duration,
            'started_at'       => $startedAt,
            'external_id'      => $data['external_id'] ?? null,
            'tracking_number'  => $data['tracking_number'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);

        $lead->forceFill(['last_activity_at' => $startedAt])->save();

        $needsFollowUp = $status === 'perdida'
            || ($status === 'contestada' && $call->direction === 'entrante' && $duration < self::callMinSeconds());

        if ($needsFollowUp) {
            $this->createFollowUpTask($lead, $call);
        } elseif ($status === 'contestada' && ! $lead->first_contact_at) {
            $lead->forceFill(['first_contact_at' => $startedAt])->save();
        }

        return $call;
    }

    private function createFollowUpTask(Lead $lead, LeadCall $call): Task
    {
        $reason = $call->status === 'perdida'
            ? 'Llamada perdida'
            : 'Llamada corta (' . $call->duration_seconds . ' s)';

        return Task::create([
            'taskable_type' => Lead::class,
            'taskable_id'   => $lead->id,
            'assigned_to'   => $lead->assigned_to,
            'title'         => $reason . ': dar seguimiento a ' . $lead->display_name,
            'description'   => 'Llamada #' . $call->id . ' del ' . $call->started_at->format('d/m/Y H:i')
                . ($lead->contact_phone ? ' · Tel. ' . $lead->contact_phone : ''),
            'due_at'        => now()->addHours(2),
            'status'        => 'open',
        ]);
    }

    // ── Acciones de bandeja ─────────────────────────────────────────────

    public function changeStatus(Lead $lead, string $status): Lead
    {
        if (! array_key_exists($status, Lead::STATUSES)) {
            throw ValidationException::withMessages(['status' => 'Estado de lead inválido.']);
        }

        if ($status !== 'nuevo' && ! $lead->first_contact_at) {
            $lead->first_contact_at = now();
        }
        $lead->status = $status;
        $lead->save();

        return $lead;
    }

    public function assign(Lead $lead, ?int $userId): Lead
    {
        $lead->assigned_to = $userId;
        if ($userId && $lead->status === 'nuevo') {
            $lead->status = 'contactado';
            $lead->first_contact_at ??= now();
        }
        $lead->save();

        return $lead;
    }

    public function convertToCustomer(Lead $lead): Customer
    {
        if ($lead->customer_id && ($c = Customer::find($lead->customer_id))) {
            return $c;
        }

        if (! $lead->phone_normalized && ! $lead->contact_email) {
            throw ValidationException::withMessages(['lead' => 'El lead no tiene teléfono ni correo para crear el cliente.']);
        }

        $parts = preg_split('/\s+/', trim((string) $lead->contact_name), 2) ?: [];
        $customer = Customer::create([
            'first_name' => $parts[0] ?? ($lead->phone_normalized ?? 'Sin nombre'),
            'last_name'  => $parts[1] ?? '',
            // customers.email es NOT NULL y único: sin correo se usa un marcador (.invalid) editable después.
            'email'      => $lead->contact_email ?: 'sin-correo-lead' . $lead->id . '@lead.invalid',
            'company'    => '',
            'phone'      => $lead->phone_normalized ?: '',
            'document_type' => 'ine',
            'source'     => 'lead:' . $lead->source,
            'status'     => 'active',
            'visitor_uuid' => $lead->visitor_uuid,
        ]);

        $lead->update(['customer_id' => $customer->id]);

        return $customer;
    }

    public function convertToDeal(Lead $lead, ?User $user = null): ?\App\Models\Deal
    {
        if ($lead->deal_id && ($d = \App\Models\Deal::find($lead->deal_id))) {
            return $d;
        }

        $pipeline = Pipeline::query()->deals()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();
        if (! $pipeline || ! $pipeline->stages()->exists()) {
            return null;
        }

        $deal = app(DealService::class)->create([
            'pipeline_id'            => $pipeline->id,
            'name'                   => 'Lead: ' . $lead->display_name,
            'amount'                 => 0,
            'currency'               => 'MXN',
            'owner_id'               => $lead->assigned_to ?? $user?->id,
            'customer_id'            => $lead->customer_id,
            'contact_snapshot_name'  => $lead->contact_name,
            'contact_snapshot_email' => $lead->contact_email,
            'contact_snapshot_phone' => $lead->phone_normalized,
            'source'                 => $lead->source,
            'status'                 => 'open',
        ]);

        $lead->update(['deal_id' => $deal->id]);

        return $deal;
    }

    /** Llamado cuando el Negocio ligado se gana: el lead queda convertido. */
    public function markConvertedByDeal(\App\Models\Deal $deal): void
    {
        Lead::where('deal_id', $deal->id)->whereNotIn('status', ['convertido'])->update(['status' => 'convertido']);
    }
}
