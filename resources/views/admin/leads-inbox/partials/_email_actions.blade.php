{{-- Acciones sobre un correo de la conversación (crear negocio / cotización). Vars: $ev, $lead. Claves opcionales de $ev: email_id, dir, sent_by, source. --}}
@php
    $lwaEv = is_array($ev ?? null) ? $ev : [];
    $lwaOut = ($lwaEv['dir'] ?? null) === 'out'
        || (! isset($lwaEv['dir']) && (! empty($lwaEv['out']) || str_starts_with((string) ($lwaEv['title'] ?? ''), 'Correo enviado')));
    $lwaEmailId = $lwaEv['email_id'] ?? null;
    $lwaLead = $lead ?? null;
@endphp
@once
    <style>
        .lwa-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,.08); }
        .lwa-btn { display: inline-flex; align-items: center; min-height: 30px; padding: 4px 11px; border: 1px solid var(--erp-border, #d6dae0); border-radius: 8px; background: var(--erp-surface, #fff); color: var(--erp-text, #1f2937); font: inherit; font-size: 12px; font-weight: 600; line-height: 1.2; text-decoration: none; cursor: pointer; }
        .lwa-btn:hover { background: var(--erp-surface-2, #f3f4f6); }
        .lwa-btn:focus-visible { outline: 2px solid var(--erp-primary, #2563eb); outline-offset: 2px; }
        .lwa-form { display: inline; margin: 0; }
        .lwa-muted { font-size: 12px; color: var(--erp-muted, #6b7280); }
        @media (max-width: 640px) { .lwa-btn { min-height: 36px; flex: 1 1 auto; justify-content: center; } }
    </style>
@endonce
@if ($lwaLead)
    @if ($lwaOut)
        @if (! empty($lwaEv['sent_by']))
            <div class="lwa-bar"><span class="lwa-muted">Enviado por {{ $lwaEv['sent_by'] }}</span></div>
        @elseif (($lwaEv['source'] ?? null) === 'external')
            <div class="lwa-bar"><span class="lwa-muted">Registrado desde Enviados</span></div>
        @endif
    @else
        @php
            $lwaHasCustomer = (bool) $lwaLead->customer_id;
            $lwaHint = $lwaHasCustomer ? null : 'Primero hay que agregar al lead como cliente';
        @endphp
        <div class="lwa-bar">
            @if ($lwaLead->deal_id)
                <a class="lwa-btn" href="{{ route('admin.deals.show', $lwaLead->deal_id) }}">Ver negocio</a>
            @else
                <form class="lwa-form" method="POST" action="{{ route('admin.leads-inbox.email.deal', $lwaLead) }}">
                    @csrf
                    @if ($lwaEmailId)<input type="hidden" name="email_id" value="{{ $lwaEmailId }}">@endif
                    <button type="submit" class="lwa-btn" @if ($lwaHint) title="{{ $lwaHint }}" @endif>{{ $lwaHasCustomer ? 'Crear negocio' : 'Negocio desde correo' }}</button>
                </form>
            @endif
            <a class="lwa-btn" @if ($lwaHint) title="{{ $lwaHint }}" @endif href="{{ route('admin.leads-inbox.email.quote', array_filter(['lead' => $lwaLead->id, 'email_id' => $lwaEmailId])) }}">{{ $lwaHasCustomer ? 'Crear cotización' : 'Cotización desde correo' }}</a>
        </div>
    @endif
@endif
