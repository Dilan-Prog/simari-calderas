<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Models\LeadNote;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadInboxController extends Controller
{
    public function __construct(private LeadService $leads)
    {
    }

    /**
     * @param bool $withStatus false para el tablero (las columnas ya son por estado)
     */
    private function filtered(Request $request, bool $withStatus = true, bool $withChip = true)
    {
        $q = Lead::query()->with(['assignee', 'customer']);

        if ($request->filled('source')) {
            $q->where('source', $request->source);
        }
        if ($withStatus) {
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            } elseif ($request->boolean('abiertos', true) && ! $request->filled('q') && ! $request->has('todos')) {
                // por defecto la bandeja muestra lo vivo (no descartados ni convertidos)
                $q->whereIn('status', Lead::OPEN_STATUSES);
            }
        }
        if ($request->filled('campaign')) {
            $q->where('utm_campaign', $request->campaign);
        }
        if ($request->filled('assigned_to')) {
            $request->assigned_to === 'sin'
                ? $q->whereNull('assigned_to')
                : $q->where('assigned_to', (int) $request->assigned_to);
        }
        if ($withChip) {
            match ($request->input('chip')) {
                'sin_atender' => $q->where('status', 'nuevo'),
                'mios'        => $q->where('assigned_to', $request->user()?->id ?? 0),
                'sin_asignar' => $q->whereNull('assigned_to'),
                default       => null,
            };
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('contact_name', 'like', "%{$term}%")
                    ->orWhere('contact_email', 'like', "%{$term}%")
                    ->orWhere('contact_phone', 'like', "%{$term}%")
                    ->orWhere('phone_normalized', 'like', "%{$term}%");
            });
        }

        return $q;
    }

    public function index(Request $request)
    {
        $view = $request->input('view') === 'embudo' ? 'embudo' : 'bandeja';

        $leads = $this->filtered($request)->orderByDesc('last_activity_at')->orderByDesc('id')->limit(60)->get();
        $this->attachPreviews($leads);

        $selected = null;
        if ($leads->isNotEmpty() || $request->filled('lead')) {
            if ($request->filled('lead')) {
                $selected = $leads->firstWhere('id', (int) $request->lead) ?: Lead::find((int) $request->lead);
            }
            $selected = $selected ?: $leads->first();
        }

        $timeline = [];
        $nextTask = null;
        if ($selected) {
            $selected->load(['assignee', 'customer', 'deal', 'calls', 'tasks', 'whatsappConversation', 'noteEntries.author']);
            $timeline = $this->timeline($selected);
            $nextTask = $selected->tasks
                ->where('status', 'open')
                ->sortBy(fn ($t) => [$t->due_at ? 0 : 1, $t->due_at?->timestamp ?? 0, $t->id])
                ->first();
        }

        $bySource = Lead::open()->selectRaw('source, count(*) as c')->groupBy('source')->pluck('c', 'source');
        $channels = [
            'todos'      => (int) $bySource->sum(),
            'whatsapp'   => (int) ($bySource['whatsapp'] ?? 0),
            'llamada'    => (int) ($bySource['llamada'] ?? 0),
            'correo'     => (int) ($bySource['correo'] ?? 0),
            'formulario' => (int) ($bySource['formulario'] ?? 0),
        ];

        $uid = $request->user()?->id ?? 0;
        $chipCounts = [
            'todos'       => $channels['todos'],
            'sin_atender' => Lead::where('status', 'nuevo')->count(),
            'mios'        => Lead::open()->where('assigned_to', $uid)->count(),
            'sin_asignar' => Lead::open()->whereNull('assigned_to')->count(),
        ];

        $times = Lead::whereNotNull('first_contact_at')->where('created_at', '>=', now()->subDays(30))
            ->get(['created_at', 'first_contact_at'])
            ->map(fn ($l) => max(0, $l->created_at->diffInMinutes($l->first_contact_at, false)));
        $kpis = [
            'sin_atender'           => $chipCounts['sin_atender'],
            'primera_respuesta_min' => $times->isEmpty() ? null : (int) round($times->avg()),
        ];

        return view('admin.leads-inbox.index', [
            'view'       => $view,
            'chips'      => $this->activeChips($request),
            'leads'      => $leads,
            'selected'   => $selected,
            'timeline'   => $timeline,
            'nextTask'   => $nextTask,
            'channels'   => $channels,
            'chipCounts' => $chipCounts,
            'kpis'       => $kpis,
            'board'      => $view === 'embudo' ? $this->board($request) : [],
            'campaigns'  => Lead::whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
            'users'      => User::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'counts'     => [
                'nuevos'   => $chipCounts['sin_atender'],
                'abiertos' => $channels['todos'],
            ],
        ]);
    }

    /** Filtros activos como etiquetas removibles (compatibilidad con la vista). */
    private function activeChips(Request $request): array
    {
        $chips = [];
        $labels = ['source' => 'Origen', 'status' => 'Estado', 'campaign' => 'Campaña', 'q' => 'Búsqueda'];
        foreach ($labels as $key => $label) {
            if ($request->filled($key)) {
                $value = match ($key) {
                    'source' => Lead::SOURCES[$request->source] ?? $request->source,
                    'status' => Lead::STATUSES[$request->status] ?? $request->status,
                    default  => $request->input($key),
                };
                $chips[] = ['label' => $label, 'value' => $value, 'remove' => $request->fullUrlWithoutQuery([$key, 'page'])];
            }
        }
        if ($request->filled('assigned_to')) {
            $u = $request->assigned_to === 'sin' ? 'Sin asignar' : optional(User::find($request->assigned_to))->first_name;
            $chips[] = ['label' => 'Asignado', 'value' => $u, 'remove' => $request->fullUrlWithoutQuery(['assigned_to', 'page'])];
        }

        return $chips;
    }

    /** Columnas del embudo: una por estado, con total real y hasta 30 leads. */
    private function board(Request $request): array
    {
        $columns = [];
        foreach (['nuevo', 'contactado', 'calificado', 'convertido'] as $status) {
            $base = $this->filtered($request, false, false)->where('status', $status);
            $total = (clone $base)->count();
            $leads = $base->orderByDesc('last_activity_at')->orderByDesc('id')->limit(30)->get();
            $this->attachPreviews($leads);
            $columns[] = ['status' => $status, 'label' => Lead::STATUSES[$status], 'total' => $total, 'leads' => $leads];
        }

        return $columns;
    }

    /** Agrega ->preview y ->preview_at a cada lead con consultas por lote. */
    private function attachPreviews($leads): void
    {
        if ($leads->isEmpty()) {
            return;
        }
        $ids = $leads->pluck('id')->all();
        $convIds = $leads->pluck('whatsapp_conversation_id')->filter()->unique()->all();

        $latest = function (string $table, string $col, array $keys) {
            if (! $keys) {
                return collect();
            }
            $maxIds = DB::table($table)->whereIn($col, $keys)->groupBy($col)->selectRaw('max(id) as id')->pluck('id');

            return DB::table($table)->whereIn('id', $maxIds)->get()->keyBy($col);
        };

        $wa = $latest('whatsapp_messages', 'conversation_id', $convIds);
        $mail = $latest('lead_inbound_emails', 'lead_id', $ids);
        $call = $latest('lead_calls', 'lead_id', $ids);
        $note = $latest('lead_notes', 'lead_id', $ids);

        foreach ($leads as $lead) {
            $cands = [];
            if ($m = $wa[$lead->whatsapp_conversation_id] ?? null) {
                $cands[] = [$m->sent_at ?? $m->created_at, $m->content ?: '(' . $m->message_type . ')'];
            }
            if ($e = $mail[$lead->id] ?? null) {
                $cands[] = [$e->received_at ?? $e->created_at, trim((string) ($e->excerpt ?: $e->subject))];
            }
            if ($c = $call[$lead->id] ?? null) {
                $cands[] = [$c->started_at, 'Llamada ' . ($c->status === 'perdida' ? 'perdida' : 'contestada') . ($c->notes ? ' · ' . $c->notes : '')];
            }
            if ($n = $note[$lead->id] ?? null) {
                $cands[] = [$n->created_at, $n->body];
            }
            $cands = array_values(array_filter($cands, fn ($x) => filled($x[1])));
            usort($cands, fn ($a, $b) => strtotime((string) $b[0]) <=> strtotime((string) $a[0]));

            if ($cands) {
                [$at, $text] = $cands[0];
                $at = $at ? Carbon::parse($at) : null;
            } elseif (filled($lead->getAttribute('notes'))) {
                $at = null;
                $text = $lead->getAttribute('notes');
            } else {
                $at = null;
                $text = '';
            }

            $lead->preview = Str::limit(trim(preg_replace('/\s+/', ' ', (string) $text)), 109, '…');
            $lead->preview_at = $at ?: ($lead->last_activity_at ?: $lead->created_at);
        }
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignee', 'customer', 'deal', 'whatsappConversation', 'poolCalculatorLead', 'calls', 'tasks', 'conversionUploads', 'noteEntries.author']);

        return view('admin.leads-inbox.show', [
            'lead'     => $lead,
            'timeline' => $this->timeline($lead),
            'users'    => User::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    /** Línea de tiempo: mensajes, llamadas, visitas/eventos de anuncio y tareas. */
    private function timeline(Lead $lead): array
    {
        $items = [];

        if ($lead->whatsapp_conversation_id) {
            foreach ($lead->whatsappConversation?->messages()->latest('sent_at')->limit(50)->get() ?? [] as $m) {
                $items[] = [
                    'at'    => $m->sent_at ?? $m->created_at,
                    'type'  => 'whatsapp',
                    'title' => $m->sender_type === 'contact' ? 'Mensaje de WhatsApp recibido' : 'Mensaje de WhatsApp enviado',
                    'body'  => $m->content ?: '(' . $m->message_type . ')',
                ];
            }
        }

        foreach ($lead->calls as $c) {
            $items[] = [
                'at'    => $c->started_at,
                'type'  => 'llamada',
                'title' => 'Llamada ' . $c->direction . ' · ' . $c->status,
                'body'  => $c->duration_seconds . ' s' . ($c->notes ? ' · ' . $c->notes : ''),
            ];
        }

        if ($lead->visitor_uuid) {
            $visit = $lead->adVisit;
            if ($visit) {
                foreach ($visit->events()->latest('occurred_at')->limit(30)->get() as $e) {
                    $items[] = [
                        'at'    => $e->occurred_at,
                        'type'  => 'anuncio',
                        'title' => 'Evento del sitio: ' . $e->event_type,
                        'body'  => $e->url,
                    ];
                }
                if ($visit->first_seen_at) {
                    $items[] = [
                        'at'    => $visit->first_seen_at,
                        'type'  => 'anuncio',
                        'title' => 'Primera visita' . ($visit->first_utm_campaign ? ' · ' . $visit->first_utm_campaign : ''),
                        'body'  => $visit->first_landing_url,
                    ];
                }
            }
        }

        if ($lead->poolCalculatorLead) {
            $items[] = [
                'at'    => $lead->poolCalculatorLead->created_at,
                'type'  => 'formulario',
                'title' => 'Calculadora de alberca · Ref ' . $lead->poolCalculatorLead->ref,
                'body'  => null,
            ];
        }

        foreach ($lead->tasks as $t) {
            $items[] = ['at' => $t->created_at, 'type' => 'tarea', 'title' => 'Tarea: ' . $t->title, 'body' => Task::statusLabel($t->status)];
        }

        $notes = $lead->relationLoaded('noteEntries') ? $lead->getRelation('noteEntries') : $lead->noteEntries()->with('author')->get();
        foreach ($notes as $n) {
            $items[] = [
                'at'    => $n->created_at,
                'type'  => 'nota',
                'title' => 'Nota de ' . ($n->author?->first_name ?: 'sistema'),
                'body'  => $n->body,
            ];
        }

        foreach (LeadInboundEmail::where('lead_id', $lead->id)->latest('id')->limit(30)->get() as $em) {
            $items[] = [
                'at'    => $em->received_at ?? $em->created_at,
                'type'  => 'correo',
                'title' => 'Correo recibido: ' . ($em->subject ?: '(sin asunto)'),
                'body'  => $em->excerpt,
            ];
        }

        foreach (\App\Models\LeadOutboundEmail::where('lead_id', $lead->id)->latest('id')->limit(30)->get() as $m) {
            $items[] = [
                'at'    => $m->sent_at ?? $m->created_at,
                'type'  => 'correo',
                'title' => 'Correo enviado: ' . ($m->subject ?: '(sin asunto)'),
                'body'  => \Illuminate\Support\Str::limit(trim((string) $m->body), 600),
            ];
        }

        $items[] = ['at' => $lead->created_at, 'type' => 'lead', 'title' => 'Lead creado (' . $lead->source_label . ')', 'body' => null];

        usort($items, fn ($a, $b) => ($b['at']?->timestamp ?? 0) <=> ($a['at']?->timestamp ?? 0));

        return $items;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:150',
            'source' => 'required|in:' . implode(',', array_keys(Lead::SOURCES)),
            'notes' => 'nullable|string|max:2000',
        ]);

        if (empty($data['phone']) && empty($data['email'])) {
            return back()->withInput()->withErrors(['phone' => 'Captura al menos un teléfono o un correo.']);
        }

        $lead = $this->leads->capture($data);

        return redirect()->route('admin.leads-inbox.show', $lead)->with('success', 'Lead registrado.');
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => 'nullable|in:' . implode(',', array_keys(Lead::STATUSES)),
            'notes'  => 'nullable|string|max:5000',
        ]);

        if (array_key_exists('notes', $data)) {
            $lead->notes = $data['notes'];
            $lead->save();
        }
        if (! empty($data['status'])) {
            $this->leads->changeStatus($lead, $data['status']);
        }

        return back()->with('success', 'Lead actualizado.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $data = $request->validate(['assigned_to' => 'nullable|exists:users,id']);
        $this->leads->assign($lead, $data['assigned_to'] ?? null);

        return back()->with('success', 'Responsable actualizado.');
    }

    public function convert(Request $request, Lead $lead)
    {
        $request->validate(['target' => 'required|in:deal,customer']);

        if ($request->target === 'customer') {
            $this->leads->convertToCustomer($lead);

            return back()->with('success', 'Cliente vinculado al lead.');
        }

        $deal = $this->leads->convertToDeal($lead, $request->user());
        if (! $deal) {
            return back()->with('error', 'No hay un pipeline de Negocios activo con etapas para crear el negocio.');
        }
        $this->leads->changeStatus($lead, 'calificado');

        return redirect()->route('admin.deals.show', $deal->id)->with('success', 'Negocio creado desde el lead.');
    }

    public function storeNote(Request $request, Lead $lead)
    {
        $data = $request->validate(['body' => 'required|string|max:2000']);

        LeadNote::create(['lead_id' => $lead->id, 'user_id' => $request->user()?->id, 'body' => $data['body']]);
        $lead->forceFill(['last_activity_at' => now()])->save();

        return redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id])->with('success', 'Nota agregada.');
    }

    public function storeCall(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status'           => 'required|in:contestada,perdida',
            'direction'        => 'nullable|in:entrante,saliente',
            'duration_seconds' => 'nullable|integer|min:0|max:86400',
            'started_at'       => 'nullable|date',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $this->leads->recordCall($data + ['lead_id' => $lead->id]);

        return back()->with('success', 'Llamada registrada.');
    }
}
