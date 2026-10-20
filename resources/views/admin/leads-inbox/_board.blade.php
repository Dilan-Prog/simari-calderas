@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-leads-board.css'])
@endpush

@php
    $lbTones = ['whatsapp' => 'ok', 'llamada' => 'info', 'correo' => 'acc', 'formulario' => 'neu', 'manual' => 'neu'];
    $lbStageTone = ['nuevo' => 'neu', 'contactado' => 'info', 'calificado' => 'warn', 'convertido' => 'ok'];
    $lbTime = function ($date) {
        if (!$date) return '';
        $mins = (int) $date->diffInMinutes(now());
        if ($mins < 1) return 'Ahora';
        if ($mins < 60) return $mins . ' min';
        if ($mins < 1440) return intdiv($mins, 60) . ' h';
        $days = intdiv($mins, 1440);
        return $days === 1 ? 'Ayer' : $days . ' d';
    };
    $lbInitials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $ini = '';
        foreach (array_slice($parts, 0, 2) as $p) { $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
        return $ini ?: '?';
    };
@endphp

<div class="lb">
    @foreach ($board as $col)
        <section class="lb-col lb-col--{{ $lbStageTone[$col['status']] ?? 'neu' }}">
            <header class="lb-col-head">
                <span class="lb-col-title">{{ $col['label'] }}</span>
                <span class="lb-col-count">{{ $col['total'] }}</span>
            </header>
            <div class="lb-col-body">
                @forelse ($col['leads'] as $lead)
                    @php
                        $tone = $lbTones[$lead->source] ?? 'neu';
                        $when = $lead->preview_at ?? $lead->last_activity_at ?? $lead->created_at;
                        $stale = $col['status'] === 'nuevo' && $when && $when->diffInHours(now()) >= 24;
                        $company = optional($lead->customer)->company;
                        $owner = optional($lead->assignee)->name;
                    @endphp
                    <a class="lb-card" href="{{ route('admin.leads-inbox.index', ['lead' => $lead->id]) }}">
                        <div class="lb-card-top">
                            <span class="lb-card-name">{{ $lead->display_name }}</span>
                            <span class="lb-chan lb-chan--{{ $tone }}" title="{{ $lead->source_label }}">
                                @if ($lead->source === 'llamada')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                @elseif ($lead->source === 'correo')
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                                @else
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.5 8.5 0 0 1-3.8-.9L3 20l1.1-4.9A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z"/></svg>
                                @endif
                            </span>
                        </div>
                        <span class="lb-card-sub">{{ $company ?: 'Sin identificar' }}</span>
                        @if (($lead->preview ?? '') !== '')
                            <span class="lb-card-prev">{{ $lead->preview }}</span>
                        @endif
                        <div class="lb-card-foot">
                            <span class="lb-time {{ $stale ? 'is-stale' : '' }}">{{ $lbTime($when) }}</span>
                            <span class="lb-avatar {{ $owner ? '' : 'is-none' }}" title="{{ $owner ?: 'Sin asignar' }}">{{ $owner ? $lbInitials($owner) : '?' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="lb-empty">Sin leads</div>
                @endforelse

                @if ($col['total'] > $col['leads']->count())
                    <div class="lb-more">y {{ $col['total'] - $col['leads']->count() }} más</div>
                @endif
            </div>
        </section>
    @endforeach
</div>
