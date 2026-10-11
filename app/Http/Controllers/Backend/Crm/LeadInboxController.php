<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\Request;

class LeadInboxController extends Controller
{
    public function __construct(private LeadService $leads)
    {
    }

    private function filtered(Request $request)
    {
        $q = Lead::query()->with(['assignee', 'customer']);

        if ($request->filled('source')) {
            $q->where('source', $request->source);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        } elseif ($request->boolean('abiertos', true) && ! $request->filled('q') && ! $request->has('todos')) {
            // por defecto la bandeja muestra lo vivo (no descartados ni convertidos)
            $q->whereIn('status', ['nuevo', 'contactado', 'calificado']);
        }
        if ($request->filled('campaign')) {
            $q->where('utm_campaign', $request->campaign);
        }
        if ($request->filled('assigned_to')) {
            $request->assigned_to === 'sin'
                ? $q->whereNull('assigned_to')
                : $q->where('assigned_to', (int) $request->assigned_to);
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
        $leads = $this->filtered($request)->orderByDesc('last_activity_at')->orderByDesc('id')->paginate(25)->withQueryString();

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

        return view('admin.leads-inbox.index', [
            'leads'     => $leads,
            'chips'     => $chips,
            'campaigns' => Lead::whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
            'users'     => User::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'counts'    => [
                'nuevos' => Lead::where('status', 'nuevo')->count(),
                'abiertos' => Lead::open()->count(),
            ],
        ]);
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignee', 'customer', 'deal', 'whatsappConversation', 'poolCalculatorLead', 'calls', 'tasks', 'conversionUploads']);

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
