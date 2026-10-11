@props(['value', 'label' => null, 'tone' => null])
{{-- Chip de estatus que deduce tono y texto desde el valor guardado (ver App\Support\ErpUi::tone). --}}
<x-erp.chip :tone="$tone ?? \App\Support\ErpUi::tone($value)" :label="$label ?? \App\Support\ErpUi::label($value)" {{ $attributes }} />
