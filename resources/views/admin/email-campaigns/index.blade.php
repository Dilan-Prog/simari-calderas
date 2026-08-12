@extends('admin.layouts.master')

@section('title', 'Campañas de Email - Admin')

@push('styles')
<style>
:root {
    --secondary-color: #ff6213;
    --button-primary-color: #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family: 'Inter', sans-serif;
}
.ec-page { padding: 32px; font-family: var(--font-family); }
.ec-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.ec-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }
.ec-btn { height: 38px; padding: 0 16px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; }
.ec-btn:hover { background: var(--button-primary-color-hover); }
.ec-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
.ec-table th, .ec-table td { padding: 12px 16px; text-align: left; font-size: 13px; border-bottom: 1px solid #F3F4F6; }
.ec-table th { color: #6B7280; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .04em; }
.ec-table tr:last-child td { border-bottom: none; }
.ec-table a { color: var(--secondary-color); text-decoration: none; font-weight: 500; }
.ec-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.ec-badge-draft { background: #F3F4F6; color: #374151; }
.ec-badge-scheduled { background: #FEF3C7; color: #92400E; }
.ec-badge-sending { background: #DBEAFE; color: #1E40AF; }
.ec-badge-sent { background: #D1FAE5; color: #065F46; }
.ec-empty { padding: 40px; text-align: center; color: #9CA3AF; font-size: 13px; }
</style>
@endpush

@section('content')
<div class="ec-page">
    <div class="ec-header">
        <h1 class="ec-title">Campañas de Email</h1>
        <a href="{{ route('admin.email-campaigns.create') }}" class="ec-btn">+ Nueva campaña</a>
    </div>

    @if (session('success'))
        <div style="background:#D1FAE5;border:1px solid #6EE7B7;border-radius:8px;padding:12px 16px;color:#065F46;font-size:13px;margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    <table class="ec-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Plantilla</th>
                <th>Lista</th>
                <th>Estado</th>
                <th>Programada</th>
                <th>Enviada</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($emailCampaigns as $campaign)
                <tr>
                    <td><a href="{{ route('admin.email-campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                    <td>{{ $campaign->template->name ?? '—' }}</td>
                    <td>{{ $campaign->list->name ?? '—' }}</td>
                    <td><span class="ec-badge ec-badge-{{ $campaign->status }}">{{ $campaign->status }}</span></td>
                    <td>{{ $campaign->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $campaign->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="ec-empty">Aún no hay campañas creadas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px;">
        {{ $emailCampaigns->links() }}
    </div>
</div>
@endsection
