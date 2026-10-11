@props(['date' => null, 'closed' => false])
{{-- Fecha límite con semáforo: verde > 7 días, ámbar ≤ 7, rojo vencida, gris si está cerrada o sin fecha. --}}
@php $d = \App\Support\ErpUi::due($date, (bool) $closed); @endphp
<span {{ $attributes->class(['erp-due', 'is-overdue' => $d['overdue']]) }}><i class="erp-dot erp-dot--{{ $d['tone'] === 'neu' ? 'neu' : $d['tone'] }}"></i>{{ $d['text'] }}</span>
