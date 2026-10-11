@props(['document'])
{{--
    Flujo del documento (línea vertical estilo SAP): documentos ya enlazados (verde), el actual (naranja)
    y los pasos de la cadena principal aún sin documento (hueco gris "Pendiente").
    Los enlaces se crean con App\Services\Erp\DocumentFlow::link($origen, $destino).
--}}
@php
    $nodes = \App\Services\Erp\DocumentFlow::flow($document);
    $docType = \App\Services\Erp\DocumentFlow::typeOf($document);
    $defs = config('erp_documents', []);
    // Cadena principal que siempre se muestra completa (pendientes incluidos).
    $main = ['quote', 'sales_order', 'project', 'material_requirement', 'purchase_order', 'shipment', 'receivable', 'payment'];
    $present = collect($nodes)->pluck('type')->all();

    $items = collect($nodes)->map(fn ($n) => $n + ['pending' => false]);
    if (in_array($docType, $main, true) || count($nodes) > 1) {
        foreach ($main as $t) {
            if (!in_array($t, $present, true) && isset($defs[$t])) {
                $items->push(['type' => $t, 'id' => null, 'label' => $defs[$t]['label'], 'number' => '—', 'status' => null, 'url' => null,
                    'current' => false, 'stage' => (int) $defs[$t]['stage'], 'pending' => true]);
            }
        }
        $items = $items->sortBy('stage')->values();
    }
@endphp
@if ($items->count() > 1)
    <div class="erp-docflow" aria-label="Flujo del documento">
        <div class="erp-docflow-title">Flujo del documento</div>
        <ol class="erp-docflow-list">
            @foreach ($items as $n)
                <li class="erp-docflow-node {{ $n['current'] ? 'is-current' : ($n['pending'] ? 'is-pending' : 'is-done') }}">
                    @if ($n['url'] && !$n['current'])
                        <a href="{{ $n['url'] }}" class="erp-docflow-card">
                    @else
                        <div class="erp-docflow-card">
                    @endif
                        <span class="erp-docflow-type">{{ $n['label'] }}</span>
                        <span class="erp-docflow-number">{{ $n['number'] }}</span>
                        @if ($n['current'])
                            <span class="erp-docflow-status erp-tone-acc"><i class="erp-dot erp-dot--acc"></i>Este documento</span>
                        @elseif ($n['pending'])
                            <span class="erp-docflow-status erp-tone-neu"><i class="erp-dot"></i>Pendiente</span>
                        @elseif ($n['status'])
                            @php $tn = \App\Support\ErpUi::tone($n['status']); @endphp
                            <span class="erp-docflow-status erp-tone-{{ $tn }}"><i class="erp-dot erp-dot--{{ $tn }}"></i>{{ \App\Support\ErpUi::label($n['status']) }}</span>
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
