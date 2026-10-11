@props(['title', 'subtitle' => null, 'status' => null, 'tone' => 'neutral', 'facts' => [], 'back' => null])
{{--
    Cabecera de "Object Page" estilo SAP.
      title    : folio / nombre
      subtitle : texto secundario (cliente, tipo…)
      status   : texto del estatus (chip con semáforo); tone: ok | warn | bad | neutral
      facts    : [['label' => 'Cliente', 'value' => 'ACME'], ...] (3-4 datos clave)
      back     : URL del listado
    Las acciones principales van en el slot por defecto.
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
<header class="erp-objhead">
    <div class="erp-objhead-main">
        @if ($back)
            <a href="{{ $back }}" class="erp-objhead-back">← Volver</a>
        @endif
        <div class="erp-objhead-titlerow">
            <h1 class="erp-objhead-title">{{ $title }}</h1>
            @if ($status)
                <span class="erp-chip erp-tone-{{ $tone }}">{{ $status }}</span>
            @endif
        </div>
        @if ($subtitle)
            <p class="erp-objhead-sub">{{ $subtitle }}</p>
        @endif
        @if (count($facts))
            <dl class="erp-objhead-facts">
                @foreach ($facts as $f)
                    <div>
                        <dt>{{ $f['label'] }}</dt>
                        <dd>{{ $f['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
    <div class="erp-objhead-actions">{{ $slot }}</div>
</header>
