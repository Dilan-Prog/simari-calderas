@extends('admin.layouts.master')
@section('title')
    {{ $lead->display_name }} - Lead - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-crm.js'])
@endpush
@section('content')
    @php
        $first = $lead->first_click ?? [];
        $last = $lead->last_click ?? [];
    @endphp
    <div class="erp-page">
        <x-erp.object-header
            :title="$lead->display_name"
            :subtitle="'Lead #' . $lead->id . ' · ' . $lead->source_label"
            :status="$lead->status_label"
            :tone="$lead->status_tone"
            :back="route('admin.leads-inbox.index')"
            :facts="[
                ['label' => 'Teléfono', 'value' => $lead->contact_phone],
                ['label' => 'Correo', 'value' => $lead->contact_email],
                ['label' => 'Responsable', 'value' => $lead->assignee?->first_name],
                ['label' => 'Campaña', 'value' => $lead->utm_campaign],
            ]">
            @if ($lead->deal)
                <a class="erp-btn" href="{{ route('admin.deals.show', $lead->deal_id) }}">Ver negocio {{ $lead->deal->folio }}</a>
            @else
                <form method="POST" action="{{ route('admin.leads-inbox.convert', $lead) }}">@csrf
                    <input type="hidden" name="target" value="deal">
                    <button class="erp-btn primary" type="submit">Convertir a Negocio</button>
                </form>
            @endif
            @unless ($lead->customer_id)
                <form method="POST" action="{{ route('admin.leads-inbox.convert', $lead) }}">@csrf
                    <input type="hidden" name="target" value="customer">
                    <button class="erp-btn" type="submit">Crear cliente</button>
                </form>
            @endunless
        </x-erp.object-header>

        @if (session('success'))<div class="crm-flash ok">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err">{{ $errors->first() }}</div>@endif

        <div class="crm-grid">
            <div>
                <div class="crm-card">
                    <h2>Línea de tiempo</h2>
                    <ul class="crm-timeline">
                        @foreach ($timeline as $item)
                            <li class="t-{{ $item['type'] }}">
                                <div class="crm-tl-title">{{ $item['title'] }}</div>
                                <div class="crm-tl-meta">{{ optional($item['at'])->format('d/m/Y H:i') }}</div>
                                @if ($item['body'])<div class="crm-tl-body">{{ $item['body'] }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="crm-card">
                    <h2>Registrar llamada</h2>
                    <form method="POST" action="{{ route('admin.leads-inbox.calls.store', $lead) }}">@csrf
                        <div class="crm-form-row">
                            <select name="status"><option value="contestada">Contestada</option><option value="perdida">Perdida</option></select>
                            <select name="direction"><option value="entrante">Entrante</option><option value="saliente">Saliente</option></select>
                            <input type="number" name="duration_seconds" min="0" placeholder="Duración (s)" style="width:130px">
                        </div>
                        <div class="crm-form-row"><input type="text" name="notes" placeholder="Notas" style="width:100%"></div>
                        <button class="erp-btn" type="submit">Guardar llamada</button>
                        <span class="crm-muted">Una llamada perdida o muy corta crea una tarea de seguimiento.</span>
                    </form>
                </div>

                @if ($lead->conversionUploads->count())
                    <div class="crm-card">
                        <h2>Conversiones a Google</h2>
                        <table class="crm-table">
                            @foreach ($lead->conversionUploads as $u)
                                <tr>
                                    <td><a href="{{ route('admin.google-conversions.show', $u) }}">{{ $u->action_label }}</a></td>
                                    <td>{{ $u->operation_label }}</td>
                                    <td><span class="erp-chip erp-tone-{{ $u->status_tone }}">{{ $u->status_label }}</span></td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <div class="crm-card">
                    <h2>Gestión</h2>
                    <form method="POST" action="{{ route('admin.leads-inbox.update', $lead) }}">@csrf @method('PUT')
                        <div class="crm-form-row">
                            <select name="status">
                                @foreach (\App\Models\Lead::STATUSES as $k => $label)
                                    <option value="{{ $k }}" @selected($lead->status === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-form-row"><textarea name="notes" placeholder="Notas">{{ $lead->notes }}</textarea></div>
                        <button class="erp-btn" type="submit">Guardar</button>
                    </form>
                    <hr style="border:0;border-top:1px solid #f3f4f6;margin:14px 0">
                    <form method="POST" action="{{ route('admin.leads-inbox.assign', $lead) }}">@csrf
                        <div class="crm-form-row">
                            <select name="assigned_to">
                                <option value="">Sin asignar</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected($lead->assigned_to === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                                @endforeach
                            </select>
                            <button class="erp-btn" type="submit">Asignar</button>
                        </div>
                    </form>
                </div>

                <div class="crm-card">
                    <h2>Atribución</h2>
                    <dl class="crm-dl">
                        <dt>Identificador</dt><dd class="crm-mono">{{ $lead->click_identifier ? strtoupper($lead->click_identifier['type']) . ': ' . $lead->click_identifier['value'] : 'Sin identificador de clic' }}</dd>
                        <dt>Campaña</dt><dd>{{ $lead->utm_campaign ?: '—' }}</dd>
                        <dt>Fuente / medio</dt><dd>{{ $lead->utm_source ?: '—' }} / {{ $lead->utm_medium ?: '—' }}</dd>
                        <dt>Término</dt><dd>{{ $lead->utm_term ?: '—' }}</dd>
                        <dt>Primer clic</dt><dd>{{ $first['utm_campaign'] ?? ($first['utm_source'] ?? '—') }} <span class="crm-muted">{{ isset($first['seen_at']) ? \Illuminate\Support\Carbon::parse($first['seen_at'])->format('d/m/Y H:i') : '' }}</span></dd>
                        <dt>Último clic</dt><dd>{{ $last['utm_campaign'] ?? ($last['utm_source'] ?? '—') }} <span class="crm-muted">{{ isset($last['seen_at']) ? \Illuminate\Support\Carbon::parse($last['seen_at'])->format('d/m/Y H:i') : '' }}</span></dd>
                        <dt>Visitante</dt><dd class="crm-mono">{{ $lead->visitor_uuid ?: '—' }}</dd>
                    </dl>
                </div>

                <div class="crm-card">
                    <h2>Contacto</h2>
                    <dl class="crm-dl">
                        <dt>Teléfono (E.164)</dt><dd class="crm-mono">{{ $lead->phone_normalized ?: '—' }}</dd>
                        <dt>Cliente</dt><dd>{{ $lead->customer ? trim($lead->customer->first_name . ' ' . $lead->customer->last_name) : '—' }}</dd>
                        <dt>Primer contacto</dt><dd>{{ optional($lead->first_contact_at)->format('d/m/Y H:i') ?? '—' }}</dd>
                        <dt>Creado</dt><dd>{{ $lead->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
