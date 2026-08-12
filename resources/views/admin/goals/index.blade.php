@extends('admin.layouts.master')

@section('title', 'Metas - Admin')

@push('styles')
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
.goals-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

/* ── Header ─────────────────────────────── */
.goals-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.goals-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.goals-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.goals-breadcrumb-current { color: #374151; }
.goals-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.goals-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.goals-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.goals-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

/* ── Cards grid ──────────────────────────── */
.goals-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
.goal-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.goal-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.goal-card-name { font-size: 15px; font-weight: 600; color: #111827; margin: 0; line-height: 1.3; }
.goal-metric-pill { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; white-space: nowrap; }
.goal-card-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.goal-action-btn { width: 30px; height: 30px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.goal-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.goal-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }

/* ── Progress bar ────────────────────────── */
.goal-progress-track { width: 100%; height: 10px; border-radius: 999px; background: #F3F4F6; overflow: hidden; }
.goal-progress-fill { height: 100%; border-radius: 999px; background: var(--secondary-color); transition: width .3s ease; }
.goal-progress-fill.is-complete { background: #16A34A; }
.goal-progress-text { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.goal-progress-values { font-size: 13px; color: #374151; }
.goal-progress-values strong { font-weight: 600; color: #111827; }
.goal-progress-percent { font-size: 13px; font-weight: 700; color: var(--secondary-color); }
.goal-progress-percent.is-complete { color: #16A34A; }

/* ── Meta info ───────────────────────────── */
.goal-card-meta { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: var(--text-description-color); padding-top: 8px; border-top: 1px solid #F3F4F6; }
.goal-card-meta span { display: inline-flex; align-items: center; gap: 5px; }
.goal-card-meta svg { flex-shrink: 0; color: #9CA3AF; }

/* ── Empty state ─────────────────────────── */
.goals-empty { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 48px 20px; text-align: center; }
.goals-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.goals-empty-inner svg { opacity: .4; }
.goals-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 640px) {
    .goals-page { padding: 16px; gap: 16px; }
    .goals-header { flex-direction: column; align-items: stretch; }
    .goals-btn-new { justify-content: center; }
    .goals-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="goals-page">

    {{-- Header --}}
    <div class="goals-header">
        <div>
            <div class="goals-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="goals-breadcrumb-current">Metas</span>
            </div>
            <h1 class="goals-title">Metas</h1>
            <p class="goals-subtitle">Objetivos de ventas y actividad por responsable y período</p>
        </div>
        <div>
            <a href="{{ route('admin.goals.create') }}" class="goals-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nueva meta
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;color:#16A34A;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif

    @php
        $metricLabels = [
            'deals_won_amount' => 'Monto de negocios ganados',
            'deals_won_count'  => 'Negocios ganados (cantidad)',
            'emails_sent'      => 'Correos enviados',
        ];
    @endphp

    {{-- Cards --}}
    @if($goals->isEmpty())
    <div class="goals-empty">
        <div class="goals-empty-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            <p>No hay metas registradas todavía.</p>
            <a href="{{ route('admin.goals.create') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Crear la primera meta</a>
        </div>
    </div>
    @else
    <div class="goals-grid">
        @foreach($goals as $goal)
            @php
                $progress = $goal->progressData;
                $percent = round($progress['percent'], 1);
                $isComplete = $percent >= 100;
                $isAmount = $goal->metric_type === 'deals_won_amount';
            @endphp
            <div class="goal-card">
                <div class="goal-card-top">
                    <div>
                        <h3 class="goal-card-name">{{ $goal->name }}</h3>
                        <span class="goal-metric-pill">{{ $metricLabels[$goal->metric_type] ?? $goal->metric_type }}</span>
                    </div>
                    <div class="goal-card-actions">
                        <a href="{{ route('admin.goals.edit', $goal) }}" class="goal-action-btn" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                        </a>
                        <form method="POST" action="{{ route('admin.goals.destroy', $goal) }}" onsubmit="return confirm('¿Eliminar esta meta?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="goal-action-btn goal-action-btn-delete" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="goal-progress-text">
                        <span class="goal-progress-values">
                            <strong>{{ $isAmount ? '$'.number_format($progress['current'], 2) : number_format($progress['current']) }}</strong>
                            / {{ $isAmount ? '$'.number_format($progress['target'], 2) : number_format($progress['target']) }}
                        </span>
                        <span class="goal-progress-percent {{ $isComplete ? 'is-complete' : '' }}">{{ $percent }}%</span>
                    </div>
                    <div class="goal-progress-track">
                        <div class="goal-progress-fill {{ $isComplete ? 'is-complete' : '' }}" style="width: {{ min(100, $percent) }}%;"></div>
                    </div>
                </div>

                <div class="goal-card-meta">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        {{ $goal->period_start?->format('d M Y') }} — {{ $goal->period_end?->format('d M Y') }}
                    </span>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        {{ $goal->owner?->name ?? 'Meta de equipo' }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
