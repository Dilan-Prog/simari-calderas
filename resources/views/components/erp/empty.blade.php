@props(['title' => 'Sin resultados', 'text' => null, 'error' => false])
{{-- Estado vacío / error. Las acciones (botón "+ Nuevo…") van en el slot. --}}
<div {{ $attributes->class(['erp-empty', 'erp-empty--error' => $error]) }}>
    <strong>{{ $title }}</strong>
    @if ($text)<p style="margin: 0 0 10px;">{{ $text }}</p>@endif
    {{ $slot }}
</div>
