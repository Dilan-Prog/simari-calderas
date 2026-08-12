@extends('admin.layouts.master')

@section('title', 'Negocios - Admin')

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

/* ── Layout ─────────────────────────────── */
.deals-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

/* ── Header ─────────────────────────────── */
.deals-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.deals-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.deals-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.deals-breadcrumb-current { color: #374151; }
.deals-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.deals-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.deals-header-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.deals-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.deals-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }
.deals-btn-kanban { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.deals-btn-kanban:hover { background: #F9FAFB; color: #111827; }

/* ── Filter card ─────────────────────────── */
.deals-filters-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 16px; }
.deals-filters-grid { display: grid; grid-template-columns: 3fr 2fr 2fr 2fr 1fr; gap: 12px; align-items: center; }
.deals-filter-field { position: relative; }
.deals-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; pointer-events: none; display: flex; align-items: center; }
.deals-filter-input { width: 100%; height: 40px; padding: 0 12px 0 34px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; }
.deals-filter-input::placeholder { color: #9CA3AF; }
.deals-filter-input:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.deals-select-wrap { position: relative; }
.deals-filter-select { width: 100%; height: 40px; padding: 0 32px 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; appearance: none; cursor: pointer; transition: border-color .2s; box-sizing: border-box; }
.deals-filter-select:focus { outline: none; border-color: var(--secondary-color); }
.deals-select-chevron { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text-description-color); pointer-events: none; display: flex; align-items: center; }
.deals-btn-filter { height: 40px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--secondary-color); color: var(--secondary-color); background: transparent; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; white-space: nowrap; width: 100%; }
.deals-btn-filter:hover { background: #FFF7ED; }

/* ── Table card ──────────────────────────── */
.deals-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.deals-table-scroll { overflow-x: auto; }
.deals-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.deals-table thead tr { background: var(--header-footer-color); height: 44px; }
.deals-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.deals-table tbody tr { height: 52px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.deals-table tbody tr:last-child { border-bottom: none; }
.deals-table tbody tr:hover { background: rgba(255,247,237,.4); }
.deals-table td { padding: 0 20px; vertical-align: middle; }
.deals-td-folio { font-size: 13px; font-weight: 600; color: var(--secondary-color); font-variant-numeric: tabular-nums; text-decoration: none; }
.deals-td-folio:hover { text-decoration: underline; color: var(--button-primary-color-hover); }
.deals-td-name { font-size: 14px; color: #111827; font-weight: 500; }
.deals-td-sub { font-size: 14px; color: #374151; }
.deals-td-amount { font-size: 14px; font-weight: 600; color: #111827; font-variant-numeric: tabular-nums; }
.deals-td-fecha { font-size: 13px; color: var(--text-description-color); }
.deals-stage-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; white-space: nowrap; }
.deals-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.deals-badge-open { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }
.deals-badge-won  { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.deals-badge-lost { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.deals-actions { display: flex; align-items: center; gap: 4px; }
.deals-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.deals-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.deals-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }
.deals-empty-row td { padding: 48px 20px; text-align: center; }
.deals-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.deals-empty-inner svg { opacity: .4; }
.deals-empty-inner p { font-size: 14px; margin: 0; }

/* ── Pagination ──────────────────────────── */
.deals-pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F3F4F6; gap: 16px; flex-wrap: wrap; }
.deals-pagination-info { font-size: 13px; color: var(--text-description-color); }
.deals-pagination-info strong { font-weight: 600; color: #111827; }

/* ── Responsive ──────────────────────────── */
@media (max-width: 1024px) {
    .deals-filters-grid { grid-template-columns: 1fr 1fr; }
    .deals-filter-field:first-child { grid-column: 1 / -1; }
}
@media (max-width: 640px) {
    .deals-page { padding: 16px; gap: 16px; }
    .deals-filters-grid { grid-template-columns: 1fr; }
    .deals-header { flex-direction: column; align-items: stretch; }
    .deals-btn-new, .deals-btn-kanban { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="deals-page">

    {{-- Header --}}
    <div class="deals-header">
        <div>
            <div class="deals-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="deals-breadcrumb-current">Negocios</span>
            </div>
            <h1 class="deals-title">Gestión de Negocios</h1>
            <p class="deals-subtitle">Listado de negocios (deals) en todos los pipelines</p>
        </div>
        <div class="deals-header-actions">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'deals.index',
                'columnDefs' => [
                    'pipeline_etapa' => 'Pipeline / Etapa',
                    'amount' => 'Monto',
                    'cliente' => 'Cliente / Empresa',
                    'owner' => 'Responsable',
                    'estado' => 'Estado',
                    'fecha' => 'Fecha',
                ],
            ])
            <a href="{{ route('admin.deals.index') }}" class="deals-btn-kanban">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                Ver Kanban
            </a>
            <a href="{{ route('admin.deals.create') }}" class="deals-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nuevo negocio
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form id="deals-filter-form" method="GET" action="{{ route('admin.deals.table') }}" class="deals-filters-card">
        <div class="deals-filters-grid">

            <div class="deals-filter-field">
                <span class="deals-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" name="search" class="deals-filter-input" placeholder="Buscar por nombre o folio..." value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="deals-select-wrap">
                <select name="status" class="deals-filter-select">
                    <option value="">Todos los estados</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Abierto</option>
                    <option value="won"  {{ request('status') === 'won'  ? 'selected' : '' }}>Ganado</option>
                    <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Perdido</option>
                </select>
                <span class="deals-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            <div class="deals-select-wrap">
                <select name="pipeline_id" class="deals-filter-select">
                    <option value="">Todos los pipelines</option>
                    @foreach($pipelines as $pipeline)
                        <option value="{{ $pipeline->id }}" {{ (string) request('pipeline_id') === (string) $pipeline->id ? 'selected' : '' }}>{{ $pipeline->name }}</option>
                    @endforeach
                </select>
                <span class="deals-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            <div class="deals-select-wrap">
                <select name="owner_id" class="deals-filter-select">
                    <option value="">Todos los responsables</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" {{ (string) request('owner_id') === (string) $owner->id ? 'selected' : '' }}>{{ $owner->name }}</option>
                    @endforeach
                </select>
                <span class="deals-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            <button type="submit" class="deals-btn-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
                Filtrar
            </button>
        </div>
    </form>

    {{-- Table card --}}
    <div class="deals-table-card">
        <div class="deals-table-scroll">
            <table class="deals-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Nombre</th>
                        <th data-col="pipeline_etapa">Pipeline / Etapa</th>
                        <th data-col="amount">Monto</th>
                        <th data-col="cliente">Cliente / Empresa</th>
                        <th data-col="owner">Responsable</th>
                        <th data-col="estado">Estado</th>
                        <th data-col="fecha">Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deals as $deal)
                    <tr>
                        <td><a href="{{ route('admin.deals.show', $deal) }}" class="deals-td-folio">{{ $deal->folio }}</a></td>
                        <td class="deals-td-name">{{ $deal->name }}</td>
                        <td data-col="pipeline_etapa">
                            <span class="deals-stage-pill">{{ $deal->pipeline?->name }} — {{ $deal->stage?->name ?? 'Sin etapa' }}</span>
                        </td>
                        <td class="deals-td-amount" data-col="amount">
                            @if($deal->amount !== null)
                                {{ $deal->currency ?? 'MXN' }} ${{ number_format($deal->amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="deals-td-sub" data-col="cliente">
                            {{ $deal->customer ? trim($deal->customer->first_name.' '.$deal->customer->last_name) : ($deal->contact_snapshot_name ?? '—') }}
                            @if($deal->company_snapshot)
                                <br><span style="font-size:12px;color:#9CA3AF;">{{ $deal->company_snapshot }}</span>
                            @endif
                        </td>
                        <td class="deals-td-sub" data-col="owner">{{ $deal->owner?->name ?? '—' }}</td>
                        <td data-col="estado">
                            @php
                                $badgeMap = [
                                    'open' => ['class' => 'deals-badge-open', 'label' => 'Abierto'],
                                    'won'  => ['class' => 'deals-badge-won',  'label' => 'Ganado'],
                                    'lost' => ['class' => 'deals-badge-lost', 'label' => 'Perdido'],
                                ];
                                $badge = $badgeMap[$deal->status] ?? ['class' => 'deals-badge-open', 'label' => $deal->status];
                            @endphp
                            <span class="deals-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td class="deals-td-fecha" data-col="fecha">{{ $deal->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="deals-actions">
                                <a href="{{ route('admin.deals.show', $deal) }}" class="deals-action-btn" title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('admin.deals.edit', $deal) }}" class="deals-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.deals.destroy', $deal) }}" onsubmit="return confirm('¿Eliminar este negocio?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="deals-action-btn deals-action-btn-delete" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="deals-empty-row">
                        <td colspan="9">
                            <div class="deals-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                <p>No se encontraron negocios con los filtros actuales.</p>
                                @if(request()->hasAny(['search','status','pipeline_id','owner_id']))
                                <a href="{{ route('admin.deals.table') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Limpiar filtros</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deals->total() > 0)
        <div class="deals-pagination-bar">
            <span class="deals-pagination-info">
                Mostrando <strong>{{ $deals->firstItem() }}-{{ $deals->lastItem() }}</strong>
                de <strong>{{ $deals->total() }}</strong> negocios
            </span>
            {{ $deals->appends(request()->query())->links('admin.components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/column-visibility.js') }}"></script>
<script>
    initColumnVisibility({
        tableKey: 'deals.index',
        savedColumns: @json($visibleColumns),
        saveUrl: '{{ route('admin.column-preferences.update') }}',
    });
</script>
@endpush
