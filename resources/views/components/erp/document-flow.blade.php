@props(['document'])
@php
    $nodes = \App\Services\Erp\DocumentFlow::flow($document);
    $statusTone = function (?string $s) {
        $s = strtolower((string) $s);
        if (in_array($s, ['paid', 'pagado', 'completed', 'completado', 'delivered', 'entregado', 'received', 'recibido', 'closed', 'cerrado', 'accepted', 'aceptada', 'aceptado', 'applied', 'aplicado', 'approved', 'signed'], true)) return 'ok';
        if (in_array($s, ['cancelled', 'cancelado', 'cancelada', 'rejected', 'rechazada', 'overdue', 'vencido', 'failed', 'incidencia'], true)) return 'bad';
        if (in_array($s, ['draft', 'borrador', 'pending', 'pendiente'], true)) return 'neutral';
        return 'warn';
    };
@endphp
@if (count($nodes) > 1)
    <div class="erp-docflow" aria-label="Flujo del documento">
        <div class="erp-docflow-title">Flujo del documento</div>
        <ol class="erp-docflow-list">
            @foreach ($nodes as $n)
                <li class="erp-docflow-node {{ $n['current'] ? 'is-current' : '' }}">
                    @if ($n['url'] && !$n['current'])
                        <a href="{{ $n['url'] }}" class="erp-docflow-card">
                    @else
                        <div class="erp-docflow-card">
                    @endif
                        <span class="erp-docflow-type">{{ $n['label'] }}</span>
                        <span class="erp-docflow-number">{{ $n['number'] }}</span>
                        @if ($n['status'])
                            <span class="erp-chip erp-tone-{{ $statusTone($n['status']) }}">{{ $n['status'] }}</span>
                        @endif
                    @if ($n['url'] && !$n['current'])
                        </a>
                    @else
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endif
