@props(['tone' => 'neu', 'label' => null])
{{-- Chip de estatus con punto. tone: ok | warn | bad | info | neu | acc. Texto por prop `label` o slot. --}}
@php $tone = in_array($tone, \App\Support\ErpUi::TONES, true) ? $tone : 'neu'; @endphp
<span {{ $attributes->class(['erp-chip', 'erp-chip--' . $tone]) }}><i class="erp-dot"></i>{{ $label ?? $slot }}</span>
