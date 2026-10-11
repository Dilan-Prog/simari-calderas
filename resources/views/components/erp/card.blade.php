@props(['title' => null, 'sub' => null])
{{-- Tarjeta con título opcional; el slot `actions` va a la derecha del título. --}}
<section {{ $attributes->class(['erp-card']) }}>
    @if ($title || isset($actions))
        <div class="erp-card-head">
            <div>
                @if ($title)<h2 class="erp-card-title">{{ $title }}</h2>@endif
                @if ($sub)<p class="erp-card-sub">{{ $sub }}</p>@endif
            </div>
            @isset($actions)<div class="erp-head-actions">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="erp-card-body">{{ $slot }}</div>
</section>
