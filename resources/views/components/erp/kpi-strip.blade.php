@props(['items' => []])
{{-- Tira de KPIs en línea con punto de semáforo. items: [['label' => 'Partidas', 'value' => 12, 'tone' => 'neu'], ...] --}}
<div {{ $attributes->class(['erp-kpistrip']) }}>
    @foreach ($items as $k)
        @php $t = in_array($k['tone'] ?? 'neu', \App\Support\ErpUi::TONES, true) ? $k['tone'] : 'neu'; @endphp
        <span class="erp-kpistrip-item"><i class="erp-dot erp-dot--{{ $t }}"></i>{{ $k['label'] }} <b>{{ $k['value'] }}</b></span>
    @endforeach
</div>
