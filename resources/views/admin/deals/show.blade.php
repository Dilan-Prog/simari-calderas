@extends('admin.layouts.master')

@section('title', 'Negocio '.$deal->folio.' - Admin')

@push('styles')
@vite('resources/css/admin/pages/pipeline.css')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.deal-show-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }
.deal-show-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.deal-show-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.deal-show-breadcrumb-current { color: #374151; }

.deal-show-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.deal-show-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.deal-show-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0; }
.deal-show-folio { font-size: 13px; font-weight: 600; color: var(--secondary-color); font-variant-numeric: tabular-nums; }
.deal-show-subtitle { font-size: 14px; color: var(--text-description-color); margin: 4px 0 0; }
.deal-show-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.deal-btn-edit { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; }
.deal-btn-edit:hover { background: var(--button-primary-color-hover); color: #fff; }
.deal-btn-back { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; transition: background .2s; white-space: nowrap; }
.deal-btn-back:hover { background: #F9FAFB; color: #111827; }

.deal-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.deal-badge-open { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }
.deal-badge-won  { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.deal-badge-lost { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.deal-stage-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; background: #FFF7ED; color: var(--secondary-color); border: 1px solid #FED7AA; white-space: nowrap; }

.deal-show-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }

.deal-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 20px 24px; }
.deal-card-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
.deal-card-title svg { color: var(--secondary-color); }

/* Properties grid */
.deal-props-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
.deal-prop { display: flex; flex-direction: column; gap: 3px; }
.deal-prop-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #9CA3AF; }
.deal-prop-value { font-size: 14px; color: #111827; }
.deal-prop-value.muted { color: #9CA3AF; }
.deal-prop-full { grid-column: 1 / -1; }

/* Timeline */
.deal-timeline { display: flex; flex-direction: column; gap: 0; margin-top: 4px; }
.deal-timeline-item { display: flex; gap: 12px; position: relative; padding-bottom: 20px; }
.deal-timeline-item:last-child { padding-bottom: 0; }
.deal-timeline-dot-col { display: flex; flex-direction: column; align-items: center; }
.deal-timeline-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--secondary-color); flex-shrink: 0; margin-top: 3px; }
.deal-timeline-line { flex: 1; width: 1px; background: #E5E7EB; margin-top: 4px; }
.deal-timeline-item:last-child .deal-timeline-line { display: none; }
.deal-timeline-body { flex: 1; }
.deal-timeline-text { font-size: 13px; color: #111827; }
.deal-timeline-text strong { font-weight: 600; }
.deal-timeline-meta { font-size: 12px; color: #9CA3AF; margin-top: 2px; }

/* Contacts / quotes lists */
.deal-list { display: flex; flex-direction: column; gap: 10px; }
.deal-list-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border: 1px solid #F3F4F6; border-radius: 6px; }
.deal-list-item-main { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.deal-list-item-title { font-size: 13px; font-weight: 500; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.deal-list-item-sub { font-size: 12px; color: #9CA3AF; }
.deal-list-item-link { font-size: 12px; font-weight: 500; color: var(--secondary-color); text-decoration: none; white-space: nowrap; flex-shrink: 0; }
.deal-list-item-link:hover { text-decoration: underline; }
.deal-empty { font-size: 13px; color: #9CA3AF; padding: 8px 0; }

.deal-notes-box { font-size: 13px; color: #374151; white-space: pre-wrap; line-height: 1.5; }

@media (max-width: 900px) {
    .deal-show-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .deal-show-page { padding: 16px; }
    .deal-props-grid { grid-template-columns: 1fr; }
    .deal-show-header { flex-direction: column; align-items: stretch; }
}
</style>
@endpush

@section('content')
<div class="deal-show-page">

    <div class="deal-show-header">
        <div>
            <div class="deal-show-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('admin.deals.table') }}" style="color:inherit;text-decoration:none;">Negocios</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="deal-show-breadcrumb-current">{{ $deal->folio }}</span>
            </div>
            <div class="deal-show-title-row">
                <h1 class="deal-show-title">{{ $deal->name }}</h1>
                @php
                    $badgeMap = [
                        'open' => ['class' => 'deal-badge-open', 'label' => 'Abierto'],
                        'won'  => ['class' => 'deal-badge-won',  'label' => 'Ganado'],
                        'lost' => ['class' => 'deal-badge-lost', 'label' => 'Perdido'],
                    ];
                    $badge = $badgeMap[$deal->status] ?? ['class' => 'deal-badge-open', 'label' => $deal->status];
                @endphp
                <span class="deal-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                <span class="deal-stage-pill">{{ $deal->stage?->name ?? 'Sin etapa' }}</span>
            </div>
            <p class="deal-show-subtitle">
                <span class="deal-show-folio">{{ $deal->folio }}</span>
                · Pipeline: {{ $deal->pipeline?->name ?? '—' }}
            </p>
        </div>
        <div class="deal-show-actions">
            <a href="{{ route('admin.deals.table') }}" class="deal-btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver
            </a>
            <a href="{{ route('admin.deals.edit', $deal) }}" class="deal-btn-edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                Editar negocio
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;color:#16A34A;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif

    <div class="deal-show-grid">

        {{-- Left column --}}
        <div style="display:flex; flex-direction:column; gap:24px;">

            {{-- Properties --}}
            <div class="deal-card">
                <h2 class="deal-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                    Propiedades del negocio
                </h2>
                <div class="deal-props-grid">
                    <div class="deal-prop">
                        <span class="deal-prop-label">Monto</span>
                        <span class="deal-prop-value">
                            @if($deal->amount !== null)
                                {{ $deal->currency ?? 'MXN' }} ${{ number_format($deal->amount, 2) }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Fecha estimada de cierre</span>
                        <span class="deal-prop-value {{ $deal->expected_close_date ? '' : 'muted' }}">{{ $deal->expected_close_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Responsable</span>
                        <span class="deal-prop-value {{ $deal->owner ? '' : 'muted' }}">{{ $deal->owner?->name ?? 'Sin asignar' }}</span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Origen</span>
                        <span class="deal-prop-value {{ $deal->source ? '' : 'muted' }}">{{ $deal->source ?? '—' }}</span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Cliente</span>
                        <span class="deal-prop-value {{ $deal->customer ? '' : 'muted' }}">
                            {{ $deal->customer ? trim($deal->customer->first_name.' '.$deal->customer->last_name) : ($deal->contact_snapshot_name ?? '—') }}
                        </span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Empresa</span>
                        <span class="deal-prop-value {{ ($deal->customer?->company ?? $deal->company_snapshot) ? '' : 'muted' }}">{{ $deal->customer?->company ?? $deal->company_snapshot ?? '—' }}</span>
                    </div>
                    @if(!$deal->customer)
                    <div class="deal-prop">
                        <span class="deal-prop-label">Email de contacto</span>
                        <span class="deal-prop-value {{ $deal->contact_snapshot_email ? '' : 'muted' }}">{{ $deal->contact_snapshot_email ?? '—' }}</span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Teléfono de contacto</span>
                        <span class="deal-prop-value {{ $deal->contact_snapshot_phone ? '' : 'muted' }}">{{ $deal->contact_snapshot_phone ?? '—' }}</span>
                    </div>
                    @endif
                    <div class="deal-prop">
                        <span class="deal-prop-label">Creado</span>
                        <span class="deal-prop-value">{{ $deal->created_at?->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="deal-prop">
                        <span class="deal-prop-label">Cerrado</span>
                        <span class="deal-prop-value {{ $deal->closed_at ? '' : 'muted' }}">{{ $deal->closed_at?->format('d M Y, H:i') ?? '—' }}</span>
                    </div>
                    @if($deal->status === 'lost' && $deal->lost_reason)
                    <div class="deal-prop deal-prop-full">
                        <span class="deal-prop-label">Motivo de pérdida</span>
                        <span class="deal-prop-value">{{ $deal->lost_reason }}</span>
                    </div>
                    @endif
                    @if($deal->notes)
                    <div class="deal-prop deal-prop-full">
                        <span class="deal-prop-label">Notas</span>
                        <div class="deal-notes-box">{{ $deal->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quotes --}}
            <div class="deal-card">
                <h2 class="deal-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                    Cotizaciones ligadas
                </h2>
                @if($deal->quotes->isEmpty())
                    <p class="deal-empty">Este negocio no tiene cotizaciones ligadas.</p>
                @else
                    <div class="deal-list">
                        @foreach($deal->quotes as $quote)
                        <div class="deal-list-item">
                            <div class="deal-list-item-main">
                                <span class="deal-list-item-title">{{ $quote->quote_number }}</span>
                                <span class="deal-list-item-sub">{{ $quote->currency }} ${{ number_format($quote->total, 2) }} · {{ ucfirst($quote->status) }}</span>
                            </div>
                            @if(\Illuminate\Support\Facades\Route::has('admin.quotes.show'))
                            <a href="{{ route('admin.quotes.show', $quote) }}" class="deal-list-item-link">Ver →</a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- Right column --}}
        <div style="display:flex; flex-direction:column; gap:24px;">

            {{-- Contacts --}}
            <div class="deal-card">
                <h2 class="deal-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Contactos asociados
                </h2>
                @if($deal->contacts->isEmpty())
                    <p class="deal-empty">No hay contactos asociados a este negocio.</p>
                @else
                    <div class="deal-list">
                        @foreach($deal->contacts as $contact)
                        <div class="deal-list-item">
                            <div class="deal-list-item-main">
                                <span class="deal-list-item-title">{{ trim($contact->first_name.' '.$contact->last_name) }}</span>
                                <span class="deal-list-item-sub">{{ $contact->pivot->role ?? 'Contacto' }}{{ $contact->email ? ' · '.$contact->email : '' }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Stage history timeline --}}
            <div class="deal-card">
                <h2 class="deal-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Historial de etapas
                </h2>
                @if($deal->stageHistory->isEmpty())
                    <p class="deal-empty">Sin movimientos registrados todavía.</p>
                @else
                    <div class="deal-timeline">
                        @foreach($deal->stageHistory as $history)
                        <div class="deal-timeline-item">
                            <div class="deal-timeline-dot-col">
                                <span class="deal-timeline-dot"></span>
                                <span class="deal-timeline-line"></span>
                            </div>
                            <div class="deal-timeline-body">
                                <div class="deal-timeline-text">
                                    @if($history->fromStage)
                                        Movido de <strong>{{ $history->fromStage->name }}</strong> a <strong>{{ $history->toStage?->name ?? '—' }}</strong>
                                    @else
                                        Creado en <strong>{{ $history->toStage?->name ?? '—' }}</strong>
                                    @endif
                                </div>
                                <div class="deal-timeline-meta">
                                    {{ $history->moved_at?->format('d M Y, H:i') }}
                                    · {{ $history->movedByUser?->name ?? 'Sistema' }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
