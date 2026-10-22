<?php

namespace App\Support;

use App\Models\AdEvent;
use App\Models\AdVisit;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadCall;
use App\Models\LeadInboundEmail;
use App\Models\LeadNote;
use App\Models\LeadOutboundEmail;
use App\Models\PipelineStage;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Bitacora de un lead: todos los movimientos desde el primer contacto, de la mas antigua a la mas reciente.
 * Cada entrada: ['at' => Carbon, 'kind' => string, 'title' => string, 'detail' => ?string, 'by' => ?string].
 */
class LeadLog
{
    public const MAX_ENTRIES = 200;
    private const MAX_WHATSAPP = 60;
    private const MAX_SITE = 15;

    /** @var array<int,string> */
    private array $userNames = [];

    public static function for(Lead $lead): array
    {
        return (new self())->build($lead);
    }

    private function build(Lead $lead): array
    {
        $e = collect();

        $logs = SystemLog::query()->where('entity_type', 'lead')->where('entity_id', $lead->id)
            ->orderBy('performed_at')->orderBy('id')->get();

        $tasks = $lead->tasks()->get();
        $taskLogs = $tasks->isEmpty() ? collect() : SystemLog::query()->where('entity_type', 'task')
            ->where('action', 'created')->whereIn('entity_id', $tasks->pluck('id'))->get()->keyBy('entity_id');

        $this->primeUsers($logs->pluck('performed_by_user_id')->merge($taskLogs->pluck('performed_by_user_id')));

        $this->origin($lead, $logs, $e);
        $this->site($lead, $e);
        $this->whatsapp($lead, $e);
        $this->emails($lead, $e);
        $this->calls($lead, $e);
        $this->notes($lead, $e);
        $this->tasks($tasks, $taskLogs, $e);
        $this->changes($logs, $e);

        // Orden estable: por hora y, a igualdad, el orden en que se generaron (el origen queda primero).
        $sorted = $e->values()->map(fn ($x, $i) => $x + ['_i' => $i])
            ->sortBy(fn ($x) => sprintf('%012d-%06d', $x['at']->getTimestamp(), $x['_i']))
            ->map(function ($x) { unset($x['_i']); return $x; })->values();

        if ($sorted->count() > self::MAX_ENTRIES) {
            $dropped = $sorted->count() - self::MAX_ENTRIES + 1;
            $sorted = $sorted->slice(-(self::MAX_ENTRIES - 1))->values();
            $sorted->prepend($this->entry($sorted->first()['at'], 'sistema', 'Movimientos anteriores omitidos', "Se omitieron {$dropped} movimientos mas antiguos para mantener la bitacora ligera."));
        }

        return $sorted->all();
    }

    private function entry($at, string $kind, string $title, ?string $detail = null, ?string $by = null): array
    {
        return [
            'at' => $at instanceof Carbon ? $at : Carbon::parse($at ?? now()),
            'kind' => $kind,
            'title' => $title,
            'detail' => ($detail !== null && trim($detail) !== '') ? trim($detail) : null,
            'by' => $by,
        ];
    }

    private function primeUsers(Collection $ids): void
    {
        $ids = $ids->filter()->unique()->diff(array_keys($this->userNames));
        if ($ids->isEmpty()) {
            return;
        }
        foreach (User::whereIn('id', $ids)->get() as $u) {
            $this->userNames[$u->id] = trim($u->first_name . ' ' . $u->last_name) ?: (string) $u->email;
        }
    }

    private function userName($id): ?string
    {
        if (! $id) {
            return null;
        }
        if (! isset($this->userNames[$id])) {
            $this->primeUsers(collect([$id]));
        }

        return $this->userNames[$id] ?? null;
    }

    private function excerpt(?string $text, int $max): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));

        return $text === '' ? null : Str::limit($text, $max);
    }

    private function origin(Lead $lead, Collection $logs, Collection $e): void
    {
        $at = $lead->created_at ?? now();
        $created = $logs->firstWhere('action', 'created');

        switch ($lead->source) {
            case 'correo':
                $mail = LeadInboundEmail::where('lead_id', $lead->id)->orderBy('received_at')->orderBy('id')->first();
                $detail = $mail ? trim('Correo recibido de ' . ($mail->from_email ?: 'desconocido') . ($mail->subject ? ' · ' . $mail->subject : '')) : null;
                $e->push($this->entry($at, 'origen', 'Lead agregado por N8N', $detail));
                break;
            case 'whatsapp':
                $first = $lead->whatsapp_conversation_id
                    ? WhatsappMessage::where('conversation_id', $lead->whatsapp_conversation_id)->where('sender_type', WhatsappMessage::SENDER_CONTACT)->orderBy('id')->first()
                    : null;
                $e->push($this->entry($first ? ($first->sent_at ?? $first->created_at ?? $at) : $at, 'origen', 'Escribio por WhatsApp', $first ? $this->excerpt($first->content, 100) : null));
                break;
            case 'llamada':
                $e->push($this->entry($at, 'origen', 'Llamada registrada'));
                break;
            case 'formulario':
                $e->push($this->entry($at, 'origen', 'Formulario del sitio'));
                break;
            default:
                $e->push($this->entry($at, 'origen', 'Registrado manualmente', null, $this->userName($created?->performed_by_user_id)));
        }
    }

    private function site(Lead $lead, Collection $e): void
    {
        if (! $lead->visitor_uuid) {
            return;
        }
        $visit = AdVisit::where('visitor_uuid', $lead->visitor_uuid)->first();
        if (! $visit) {
            return;
        }

        $campaign = $visit->first_utm_campaign ?: $visit->utm_campaign;
        $source = $visit->first_utm_source ?: $visit->utm_source;
        $parts = array_filter([
            $campaign ? 'campana ' . $campaign : null,
            $source ? 'origen ' . $source : null,
            ($visit->first_gclid ?: $visit->gclid) ? 'anuncio de Google' : null,
            $visit->first_landing_url ?: $visit->landing_url,
        ]);
        $e->push($this->entry($visit->first_seen_at, 'sitio', 'Primera visita al sitio', implode(' · ', $parts)));

        $hasElement = Schema::hasColumn('ad_events', 'element');
        $events = AdEvent::with('product')->where('ad_visit_id', $visit->id)
            ->whereIn('event_type', ['whatsapp_click', 'quote_start', 'add_to_cart', 'checkout_start'])
            ->orderByDesc('occurred_at')->limit(self::MAX_SITE - 1)->get();

        foreach ($events as $ev) {
            $product = $ev->product?->name;
            switch ($ev->event_type) {
                case 'whatsapp_click':
                    $element = $hasElement ? $ev->getAttribute('element') : null;
                    $title = match ($element) {
                        'flotante' => 'Clic en el boton flotante de WhatsApp',
                        'producto' => 'Clic en WhatsApp en la pagina del producto' . ($product ? ' ' . $product : ''),
                        'tarjeta' => 'Clic en WhatsApp en una tarjeta de producto',
                        'servicio' => 'Clic en WhatsApp en una pagina de servicio',
                        'seccion' => 'Clic en WhatsApp en una seccion de la pagina',
                        default => 'Clic en WhatsApp',
                    };
                    break;
                case 'quote_start':
                    // Los botones "Solicitar cotizacion" abren WhatsApp: se indica desde donde se pulsaron.
                    $element = $hasElement ? $ev->getAttribute('element') : null;
                    $title = match ($element) {
                        'producto' => 'Pulso "Solicitar cotizacion" (abre WhatsApp) en la pagina del producto' . ($product ? ' ' . $product : ''),
                        'tarjeta' => 'Pulso "Solicitar cotizacion" (abre WhatsApp) en una tarjeta de producto',
                        'servicio' => 'Pulso "Solicitar cotizacion" (abre WhatsApp) en una pagina de servicio',
                        'seccion' => 'Pulso "Solicitar cotizacion" (abre WhatsApp) en una seccion de la pagina',
                        default => 'Inicio una cotizacion en el sitio',
                    };
                    break;
                case 'add_to_cart':
                    $title = 'Agrego un producto al carrito';
                    break;
                default:
                    $title = 'Inicio el pago en el sitio';
            }
            $detail = trim($ev->url . ($product && ! str_contains($title, $product) ? ' · ' . $product : ''));
            $e->push($this->entry($ev->occurred_at, 'sitio', $title, $detail));
        }
    }

    private function whatsapp(Lead $lead, Collection $e): void
    {
        if (! $lead->whatsapp_conversation_id) {
            return;
        }
        $messages = WhatsappMessage::where('conversation_id', $lead->whatsapp_conversation_id)
            ->orderByDesc('id')->limit(self::MAX_WHATSAPP)->get();

        foreach ($messages as $m) {
            if ($m->sender_type === WhatsappMessage::SENDER_SYSTEM) {
                continue;
            }
            $in = $m->sender_type === WhatsappMessage::SENDER_CONTACT;
            $title = ($in ? 'Mensaje recibido' : 'Mensaje enviado')
                . ($m->is_template ? ' (plantilla' . ($m->template_name ? ' ' . $m->template_name : '') . ')' : '');
            $detail = $this->excerpt($m->content, 100)
                ?? ($m->message_type && $m->message_type !== 'text' ? 'Archivo: ' . $m->message_type : null);
            $e->push($this->entry($m->sent_at ?? $m->created_at, 'whatsapp', $title, $detail));
        }
    }

    private function emails(Lead $lead, Collection $e): void
    {
        foreach (LeadInboundEmail::where('lead_id', $lead->id)->latest('id')->limit(50)->get() as $m) {
            $e->push($this->entry($m->received_at ?? $m->created_at, 'correo', 'Correo recibido' . ($m->subject ? ': ' . $m->subject : ''), $this->excerpt($m->excerpt, 150)));
        }
        foreach (LeadOutboundEmail::where('lead_id', $lead->id)->latest('id')->limit(50)->get() as $m) {
            $e->push($this->entry(
                $m->sent_at ?? $m->created_at,
                'correo',
                'Correo enviado' . ($m->from_email ? ' por ' . $m->from_email : '') . ($m->subject ? ': ' . $m->subject : ''),
                $m->to_email ? 'Para ' . $m->to_email : null
            ));
        }
    }

    private function calls(Lead $lead, Collection $e): void
    {
        foreach (LeadCall::where('lead_id', $lead->id)->get() as $c) {
            $dir = $c->direction === 'saliente' ? 'saliente' : 'entrante';
            $st = $c->status === 'perdida' ? 'perdida' : 'contestada';
            $duration = $c->duration_seconds ? 'Duracion ' . gmdate($c->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $c->duration_seconds) : null;
            $detail = trim(($duration ?? '') . ' ' . ($this->excerpt($c->notes, 150) ?? ''));
            $e->push($this->entry($c->started_at, 'llamada', "Llamada {$dir} · {$st}", $detail));
        }
    }

    private function notes(Lead $lead, Collection $e): void
    {
        foreach (LeadNote::with('author')->where('lead_id', $lead->id)->latest('id')->limit(50)->get() as $n) {
            $by = $n->author ? (trim($n->author->first_name . ' ' . $n->author->last_name) ?: $n->author->email) : null;
            $e->push($this->entry($n->created_at, 'nota', 'Nota agregada', $this->excerpt($n->body, 200), $by));
        }
    }

    private function tasks(Collection $tasks, Collection $taskLogs, Collection $e): void
    {
        foreach ($tasks as $t) {
            $e->push($this->entry($t->created_at, 'tarea', 'Tarea creada: ' . $t->title, null, $this->userName($taskLogs->get($t->id)?->performed_by_user_id)));
        }
    }

    private function changes(Collection $logs, Collection $e): void
    {
        $changes = [];
        foreach ($logs as $l) {
            if ($l->action === 'created') {
                if (! empty($l->new_value['assigned_to'])) {
                    $changes[] = [$l, 'assigned_to', null, $l->new_value['assigned_to']];
                }
                continue;
            }
            if ($l->action !== 'updated') {
                continue;
            }
            foreach (($l->new_value ?? []) as $field => $new) {
                $changes[] = [$l, $field, ($l->old_value ?? [])[$field] ?? null, $new];
            }
        }

        $userIds = [];
        $stageIds = [];
        $customerIds = [];
        foreach ($changes as [, $f, $old, $new]) {
            if ($f === 'assigned_to') {
                array_push($userIds, $old, $new);
            } elseif ($f === 'pipeline_stage_id') {
                array_push($stageIds, $old, $new);
            } elseif ($f === 'customer_id') {
                $customerIds[] = $new;
            }
        }
        $this->primeUsers(collect($userIds));
        $stages = PipelineStage::whereIn('id', array_filter($stageIds))->pluck('name', 'id');
        $customers = Customer::whereIn('id', array_filter($customerIds))->get()->keyBy('id');

        $contactLabels = ['contact_name' => 'nombre', 'contact_email' => 'correo', 'contact_phone' => 'telefono', 'contact_whatsapp' => 'WhatsApp'];
        $contact = [];

        foreach ($changes as [$l, $f, $old, $new]) {
            $by = $l->performed_by_user_id ? $this->userName($l->performed_by_user_id) : 'Sistema';
            $at = $l->performed_at;

            if ($f === 'assigned_to') {
                $e->push($this->entry($at, 'asignacion', $new ? 'Asignado a ' . ($this->userName($new) ?? 'usuario #' . $new) : 'Sin asignar', null, $by));
            } elseif ($f === 'pipeline_stage_id') {
                $from = $old ? ($stages[$old] ?? 'Sin etapa') : 'Sin etapa';
                $to = $new ? ($stages[$new] ?? 'Sin etapa') : 'Sin etapa';
                $e->push($this->entry($at, 'etapa', "Etapa: {$from} -> {$to}", null, $by));
            } elseif ($f === 'status') {
                $from = Lead::STATUSES[$old] ?? ($old ?: 'Sin estado');
                $to = Lead::STATUSES[$new] ?? ($new ?: 'Sin estado');
                $e->push($this->entry($at, 'estado', "Estado: {$from} -> {$to}", null, $by));
            } elseif ($f === 'customer_id') {
                if ($new) {
                    $c = $customers->get($new);
                    $name = $c ? (trim($c->first_name . ' ' . $c->last_name) ?: $c->company) : null;
                    $e->push($this->entry($at, 'cliente', 'Ligado al cliente ' . ($name ?: '#' . $new), null, $by));
                } else {
                    $e->push($this->entry($at, 'cliente', 'Cliente desligado', null, $by));
                }
            } elseif (isset($contactLabels[$f])) {
                $contact[$l->id]['log'] = $l;
                $contact[$l->id]['fields'][] = $contactLabels[$f];
            }
        }

        foreach ($contact as $item) {
            $l = $item['log'];
            $e->push($this->entry(
                $l->performed_at,
                'contacto',
                'Datos de contacto actualizados',
                implode(', ', $item['fields']),
                $l->performed_by_user_id ? $this->userName($l->performed_by_user_id) : 'Sistema'
            ));
        }
    }
}
