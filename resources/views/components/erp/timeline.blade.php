@props(['items' => []])
{{--
    Línea de tiempo de actividad (conversación + eventos del sistema).
      items : [['type' => 'whatsapp|llamada|anuncio|tarea|formulario|lead', 'title' => '', 'at' => Carbon|null, 'body' => ''|null], ...]
    Los mensajes con cuerpo (whatsapp, formulario, llamada) se muestran como burbuja; el resto como evento.
--}}
@php $bubbles = ['whatsapp', 'formulario', 'llamada']; @endphp
<ol {{ $attributes->class(['erp-timeline']) }}>
    @forelse ($items as $item)
        @php $type = $item['type'] ?? 'lead'; @endphp
        <li class="erp-tl-item erp-tl-item--{{ $type }}">
            <span class="erp-tl-dot" aria-hidden="true"></span>
            <div class="erp-tl-content">
                <div class="erp-tl-head">
                    <span class="erp-tl-title">{{ $item['title'] }}</span>
                    <time class="erp-tl-meta">{{ optional($item['at'] ?? null)->format('d/m/Y H:i') }}</time>
                </div>
                @if (!empty($item['body']))
                    @if (in_array($type, $bubbles, true))
                        <div class="erp-bubble">{{ $item['body'] }}</div>
                    @else
                        <div class="erp-tl-body">{{ $item['body'] }}</div>
                    @endif
                @endif
            </div>
        </li>
    @empty
        <li class="erp-tl-empty">Sin actividad registrada.</li>
    @endforelse
</ol>
