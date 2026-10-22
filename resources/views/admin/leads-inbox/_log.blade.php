@php
    $__log = \App\Support\LeadLog::for($lead);
    $__kindColor = [
        'origen' => 'var(--erp-accent)', 'sitio' => 'var(--erp-muted)', 'whatsapp' => '#1f9d55', 'correo' => '#2b6cb0',
        'llamada' => '#b7791f', 'asignacion' => 'var(--erp-accent)', 'etapa' => 'var(--erp-accent)', 'estado' => 'var(--erp-accent)',
        'cliente' => '#1f9d55', 'nota' => '#b7791f', 'tarea' => '#2b6cb0', 'contacto' => 'var(--erp-muted)', 'sistema' => 'var(--erp-muted)',
    ];
@endphp
<style>
    .lw-log { margin-top: 10px; border: 1px solid var(--erp-line); border-radius: 8px; font-size: 13px; color: var(--erp-ink); }
    .lw-log > summary { cursor: pointer; padding: 8px 10px; font-weight: 600; font-size: 13px; list-style: none; }
    .lw-log > summary::-webkit-details-marker { display: none; }
    .lw-log > summary::before { content: '\25B8'; display: inline-block; margin-right: 6px; color: var(--erp-muted); transition: transform .15s; }
    .lw-log[open] > summary::before { transform: rotate(90deg); }
    .lw-log-list { list-style: none; margin: 0; padding: 6px 10px 10px 12px; max-height: 320px; overflow-y: auto; border-top: 1px solid var(--erp-line); }
    .lw-log-item { position: relative; padding: 6px 0 6px 14px; border-left: 2px solid var(--erp-line); line-height: 1.35; }
    .lw-log-item::before { content: ''; position: absolute; left: -5px; top: 11px; width: 8px; height: 8px; border-radius: 50%; background: var(--lw-dot, var(--erp-muted)); }
    .lw-log-time { display: block; font-size: 11px; color: var(--erp-muted); }
    .lw-log-title { font-weight: 700; overflow-wrap: anywhere; }
    .lw-log-detail { color: var(--erp-muted); overflow-wrap: anywhere; }
    .lw-log-by { font-size: 11px; color: var(--erp-muted); font-style: italic; }
    .lw-log-empty { padding: 8px 10px; color: var(--erp-muted); }
</style>
<details class="lw-log">
    <summary>Bitacora ({{ count($__log) }})</summary>
    @if (count($__log))
        <ul class="lw-log-list">
            @foreach ($__log as $__e)
                <li class="lw-log-item" style="--lw-dot: {{ $__kindColor[$__e['kind']] ?? 'var(--erp-muted)' }}">
                    <span class="lw-log-time">{{ $__e['at']->format('d/m/Y H:i') }}</span>
                    <span class="lw-log-title">{{ $__e['title'] }}</span>
                    @if ($__e['detail'])<div class="lw-log-detail">{{ $__e['detail'] }}</div>@endif
                    @if ($__e['by'])<div class="lw-log-by">por {{ $__e['by'] }}</div>@endif
                </li>
            @endforeach
        </ul>
    @else
        <div class="lw-log-empty">Sin movimientos.</div>
    @endif
</details>
