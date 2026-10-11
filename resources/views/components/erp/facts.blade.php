@props(['items' => []])
{{-- Cuadrícula de datos (etiqueta + valor). items: [['label' => 'Cliente', 'value' => 'ACME', 'sub' => 'Planta Celaya'], ...] --}}
<dl {{ $attributes->class(['erp-facts']) }} style="margin: 0;">
    @foreach ($items as $f)
        <div>
            <dt class="erp-fact-label">{{ $f['label'] }}</dt>
            <dd class="erp-fact-value" style="margin: 2px 0 0;">{{ $f['value'] ?? '—' }}</dd>
            @if (!empty($f['sub']))<div class="erp-fact-sub">{{ $f['sub'] }}</div>@endif
        </div>
    @endforeach
</dl>
