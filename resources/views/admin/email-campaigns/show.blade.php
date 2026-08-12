@extends('admin.layouts.master')

@section('title', $emailCampaign->name . ' - Admin')

@push('styles')
<style>
:root {
    --secondary-color: #ff6213;
    --button-primary-color: #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family: 'Inter', sans-serif;
}
.ecs-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }
.ecs-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.ecs-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 4px; }
.ecs-subtitle { font-size: 13px; color: #6B7280; margin: 0; }
.ecs-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.ecs-btn { height: 38px; padding: 0 16px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; }
.ecs-btn:hover { background: var(--button-primary-color-hover); }
.ecs-btn-secondary { height: 38px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; cursor: pointer; }
.ecs-btn-danger { height: 38px; padding: 0 16px; border: 1px solid #FCA5A5; background: #fff; color: #DC2626; border-radius: 6px; font-size: 13px; cursor: pointer; }
.ecs-metrics { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
.ecs-metric-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 18px; text-align: center; }
.ecs-metric-value { font-size: 24px; font-weight: 700; color: #111827; }
.ecs-metric-pct { font-size: 12px; color: #ff6213; font-weight: 600; margin-top: 2px; }
.ecs-metric-label { font-size: 12px; color: #6B7280; margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }
.ecs-btn:disabled { opacity: .6; cursor: not-allowed; }
.ecs-alert-error { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 12px 16px; color: #DC2626; font-size: 13px; display: none; }
.ecs-section-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 12px; }
.ecs-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
.ecs-table th, .ecs-table td { padding: 10px 16px; text-align: left; font-size: 13px; border-bottom: 1px solid #F3F4F6; }
.ecs-table th { color: #6B7280; font-weight: 600; text-transform: uppercase; font-size: 11px; }
.ecs-table tr:last-child td { border-bottom: none; }
.ecs-empty { padding: 30px; text-align: center; color: #9CA3AF; font-size: 13px; }
.ecs-schedule-box { display: flex; gap: 8px; align-items: center; }
.ecs-schedule-box input { height: 38px; padding: 0 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; }
</style>
@endpush

@section('content')
<div class="ecs-page">

    @if (session('success'))
        <div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:8px;padding:12px 16px;color:#065F46;font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    <div id="ecs-send-error" class="ecs-alert-error"></div>

    <div class="ecs-header">
        <div>
            <h1 class="ecs-title">{{ $emailCampaign->name }}</h1>
            <p class="ecs-subtitle">
                Plantilla: {{ $emailCampaign->template->name ?? '—' }} &middot;
                Lista: {{ $emailCampaign->list->name ?? '—' }} &middot;
                Estado: <strong>{{ $emailCampaign->status }}</strong>
            </p>
        </div>
        <div class="ecs-actions">
            @if(in_array($emailCampaign->status, ['draft', 'scheduled']))
                <button
                    type="button"
                    id="ecs-send-now-btn"
                    class="ecs-btn"
                    data-send-url="{{ route('admin.email-campaigns.send', $emailCampaign) }}"
                    data-redirect-url="{{ route('admin.email-campaigns.show', $emailCampaign) }}"
                >
                    Enviar ahora
                </button>
            @endif
            @if($emailCampaign->status === 'draft')
                <form method="POST" action="{{ route('admin.email-campaigns.schedule', $emailCampaign) }}" class="ecs-schedule-box">
                    @csrf
                    <input type="datetime-local" name="scheduled_at" required>
                    <button type="submit" class="ecs-btn-secondary">Programar</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.email-campaigns.destroy', $emailCampaign) }}" onsubmit="return confirm('¿Eliminar esta campaña?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="ecs-btn-danger">Eliminar</button>
            </form>
        </div>
    </div>

    @php
        $ecsPct = fn (int $part) => $metrics['total'] > 0 ? round(($part / $metrics['total']) * 100, 1) : 0;
    @endphp
    <div class="ecs-metrics">
        <div class="ecs-metric-card">
            <div class="ecs-metric-value">{{ $metrics['total'] }}</div>
            <div class="ecs-metric-label">Enviados</div>
        </div>
        <div class="ecs-metric-card">
            <div class="ecs-metric-value">{{ $metrics['opened'] }}</div>
            <div class="ecs-metric-pct">{{ $ecsPct($metrics['opened']) }}%</div>
            <div class="ecs-metric-label">Abiertos</div>
        </div>
        <div class="ecs-metric-card">
            <div class="ecs-metric-value">{{ $metrics['clicked'] }}</div>
            <div class="ecs-metric-pct">{{ $ecsPct($metrics['clicked']) }}%</div>
            <div class="ecs-metric-label">Con clicks</div>
        </div>
        <div class="ecs-metric-card">
            <div class="ecs-metric-value">{{ $metrics['bounced'] }}</div>
            <div class="ecs-metric-pct">{{ $ecsPct($metrics['bounced']) }}%</div>
            <div class="ecs-metric-label">Rebotados</div>
        </div>
        <div class="ecs-metric-card">
            <div class="ecs-metric-value">{{ $metrics['unsubscribed'] }}</div>
            <div class="ecs-metric-pct">{{ $ecsPct($metrics['unsubscribed']) }}%</div>
            <div class="ecs-metric-label">Bajas</div>
        </div>
    </div>

    <div>
        <h2 class="ecs-section-title">Mapa de clicks</h2>
        <table class="ecs-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Clicks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clickMap as $row)
                    <tr>
                        <td style="word-break:break-all;">{{ $row['url'] }}</td>
                        <td>{{ $row['count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="ecs-empty">Aún no hay clicks registrados en esta campaña.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.email-campaigns.partials._scripts')
@endpush
