@props(['title', 'subtitle' => null, 'status' => null, 'tone' => 'neu', 'facts' => [], 'back' => null, 'eyebrow' => null, 'process' => null, 'steps' => [], 'current' => null])
{{--
    Cabecera de Object Page.
      eyebrow : "PROYECTO · PRY-2026-0016" (opcional)         title/subtitle : título y línea secundaria
      status  : texto del estatus (chip con semáforo); tone: ok | warn | bad | info | neu | acc
                (acepta también el antiguo "neutral"). Si omites tone se deduce del texto.
      facts   : [['label' => 'Persona a cargo', 'value' => 'Mariana'], ...] (3-4 datos clave)
      process : etiqueta de la tira ("PLAN-TO-PRODUCE") + steps/current → chevrons
      back    : URL del listado
    Las acciones principales (Editar, Generar…) van en el slot por defecto.
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
@php
    $tone = $tone === 'neutral' ? 'neu' : $tone;
    if ($status && !in_array($tone, \App\Support\ErpUi::TONES, true)) {
        $tone = \App\Support\ErpUi::tone($status);
    }
@endphp
<header class="erp-objhead">
    <div class="erp-objhead-top">
        <div class="erp-objhead-main">
            <div>
                @if ($back)<a href="{{ $back }}" class="erp-objhead-back">‹ Volver</a>@endif
                @if ($eyebrow)<span class="erp-eyebrow">{{ $eyebrow }}</span>@endif
            </div>
            <div class="erp-objhead-titlerow">
                <h1 class="erp-objhead-title">{{ $title }}</h1>
                @if ($status)<x-erp.chip :tone="$tone" :label="$status" />@endif
            </div>
            @if ($subtitle)<p class="erp-objhead-sub">{{ $subtitle }}</p>@endif
        </div>
        <div class="erp-objhead-actions">{{ $slot }}</div>
    </div>
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
    @if (count($steps))
        <div class="erp-objhead-process">
            @if ($process)<div class="erp-process-label">{{ $process }}</div>@endif
            <x-erp.process-chevrons :steps="$steps" :current="$current" />
        </div>
    @endif
</header>
