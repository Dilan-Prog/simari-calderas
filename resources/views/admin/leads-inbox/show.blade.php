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
        $fmtClick = fn ($c) => isset($c['seen_at']) ? \Illuminate\Support\Carbon::parse($c['seen_at'])->format('d/m/Y H:i') : '';
    @endphp
    <div class="erp-page">
        <x-erp.object-header
            :title="$lead->display_name"
            :subtitle="'Lead #' . $lead->id . ' · ' . $lead->source_label"
            :status="$lead->status_label"
            :tone="$lead->status_tone"
            :back="route('admin.leads-inbox.index')"
            eyebrow="BANDEJA DE LEADS · VTA1"
            :facts="[
                ['label' => 'Teléfono', 'value' => \App\Support\PhoneFormat::pretty($lead->contact_phone)],
                ['label' => 'Correo', 'value' => $lead->contact_email],
                ['label' => 'Responsable', 'value' => $lead->assignee?->first_name],
                ['label' => 'Campaña', 'value' => $lead->utm_campaign],
            ]">
            @if ($lead->deal)
                <a class="erp-btn" href="{{ route('admin.deals.show', $lead->deal_id) }}">Ver negocio {{ $lead->deal->folio }}</a>
            @else
                <form method="POST" action="{{ route('admin.leads-inbox.convert', $lead) }}">@csrf
                    <input type="hidden" name="target" value="deal">
                    <button class="erp-btn erp-btn--primary" type="submit">Convertir a Negocio</button>
                </form>
            @endif
            @unless ($lead->customer_id)
                <form method="POST" action="{{ route('admin.leads-inbox.convert', $lead) }}">@csrf
                    <input type="hidden" name="target" value="customer">
                    <button class="erp-btn" type="submit">Crear cliente</button>
                </form>
            @endunless
        </x-erp.object-header>

        @if (session('success'))<div class="crm-flash ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err" role="alert">{{ $errors->first() }}</div>@endif

        <div class="crm-detail">
            <div>
                <x-erp.card title="Línea de tiempo" sub="Conversación y eventos del lead, del más reciente al más antiguo.">
                    <div class="crm-conversation">
                        <x-erp.timeline :items="$timeline" />
                    </div>
                </x-erp.card>

                <x-erp.card title="Registrar llamada">
                    <form method="POST" action="{{ route('admin.leads-inbox.calls.store', $lead) }}">@csrf
                        <div class="crm-form-row">
                            <select class="erp-select" name="status" aria-label="Resultado"><option value="contestada">Contestada</option><option value="perdida">Perdida</option></select>
                            <select class="erp-select" name="direction" aria-label="Dirección"><option value="entrante">Entrante</option><option value="saliente">Saliente</option></select>
                            <input class="erp-input" type="number" name="duration_seconds" min="0" placeholder="Duración (s)" aria-label="Duración en segundos">
                        </div>
                        <div class="crm-form-row"><input class="erp-input" type="text" name="notes" placeholder="Notas" aria-label="Notas"></div>
                        <div class="erp-form-actions">
                            <button class="erp-btn" type="submit">Guardar llamada</button>
                            <span class="crm-muted">Una llamada perdida o muy corta crea una tarea de seguimiento.</span>
                        </div>
                    </form>
                </x-erp.card>

                @if ($lead->conversionUploads->count())
                    <x-erp.card title="Conversiones a Google">
                        <div class="erp-table-wrap crm-sub-table">
                            <table class="erp-table">
                                <tbody>
                                @foreach ($lead->conversionUploads as $u)
                                    <tr>
                                        <td><a class="crm-link" href="{{ route('admin.google-conversions.show', $u) }}">{{ $u->action_label }}</a></td>
                                        <td>{{ $u->operation_label }}</td>
                                        <td><x-erp.chip :tone="$u->status_tone" :label="$u->status_label" /></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-erp.card>
                @endif
            </div>

            <div>
                <x-erp.card title="Gestión">
                    <form method="POST" action="{{ route('admin.leads-inbox.update', $lead) }}">@csrf @method('PUT')
                        <div class="crm-form-row">
                            <select class="erp-select" name="status" aria-label="Estado">
                                @foreach (\App\Models\Lead::STATUSES as $k => $label)
                                    <option value="{{ $k }}" @selected($lead->status === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="crm-form-row"><textarea class="erp-input" name="notes" placeholder="Notas" aria-label="Notas">{{ $lead->notes }}</textarea></div>
                        <button class="erp-btn" type="submit">Guardar</button>
                    </form>
                    <hr class="crm-hr">
                    <form method="POST" action="{{ route('admin.leads-inbox.assign', $lead) }}">@csrf
                        <div class="crm-form-row">
                            <select class="erp-select" name="assigned_to" aria-label="Responsable">
                                <option value="">Sin asignar</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected($lead->assigned_to === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                                @endforeach
                            </select>
                            <button class="erp-btn" type="submit">Asignar</button>
                        </div>
                    </form>
                </x-erp.card>

                <x-erp.card title="Origen y atribución">
                    <dl class="crm-attr-list" style="margin:0">
                        <div class="crm-attr"><dt>Identificador</dt><dd class="crm-mono">{{ $lead->click_identifier ? strtoupper($lead->click_identifier['type']) . ': ' . $lead->click_identifier['value'] : 'Sin identificador de clic' }}</dd></div>
                        <div class="crm-attr"><dt>Campaña</dt><dd>{{ $lead->utm_campaign ?: '—' }}</dd></div>
                        <div class="crm-attr"><dt>Fuente / medio</dt><dd>{{ $lead->utm_source ?: '—' }} / {{ $lead->utm_medium ?: '—' }}</dd></div>
                        <div class="crm-attr"><dt>Término</dt><dd>{{ $lead->utm_term ?: '—' }}</dd></div>
                        <div class="crm-attr"><dt>Primer clic</dt><dd>{{ $first['utm_campaign'] ?? ($first['utm_source'] ?? '—') }} <small>{{ $fmtClick($first) }}</small></dd></div>
                        <div class="crm-attr"><dt>Último clic</dt><dd>{{ $last['utm_campaign'] ?? ($last['utm_source'] ?? '—') }} <small>{{ $fmtClick($last) }}</small></dd></div>
                        <div class="crm-attr"><dt>Visitante</dt><dd class="crm-mono">{{ $lead->visitor_uuid ?: '—' }}</dd></div>
                    </dl>
                    @unless ($lead->click_identifier)
                        <div class="crm-note">Sin GCLID, GBRAID ni WBRAID. La conversión no se subirá a Google Ads con clic; solo con correo o teléfono si existen.</div>
                    @endunless
                </x-erp.card>

                <x-erp.card title="Contacto">
                    <dl style="margin:0">
                        <div class="crm-attr"><dt>Teléfono (E.164)</dt><dd class="crm-mono">{{ $lead->phone_normalized ?: '—' }}</dd></div>
                        <div class="crm-attr"><dt>Cliente</dt><dd>{{ $lead->customer ? trim($lead->customer->first_name . ' ' . $lead->customer->last_name) : '—' }}</dd></div>
                        <div class="crm-attr"><dt>Primer contacto</dt><dd>{{ optional($lead->first_contact_at)->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div class="crm-attr"><dt>Creado</dt><dd>{{ $lead->created_at->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                    @if ($lead->customer)
                        <div class="crm-note crm-note--info">Cliente existente vinculado: {{ trim($lead->customer->first_name . ' ' . $lead->customer->last_name) }}.</div>
                    @endif
                </x-erp.card>
            </div>
        </div>
    </div>
@endsection
