@php
    $tabs = collect($section->config['tabs'] ?? [])
        ->filter(fn ($tab) => !empty($tab['label']))
        ->values();
@endphp

@if ($tabs->isNotEmpty())
<section class="svc-tabs" x-data="{ active: 0 }" @if ($previewMode) data-section-id="{{ $section->id }}" @endif>
    <div class="svc-tabs__nav" role="tablist">
        @foreach ($tabs as $i => $tab)
            <button type="button" class="svc-tabs__tab" :class="{ 'is-active': active === {{ $i }} }" @click="active = {{ $i }}" role="tab" :aria-selected="active === {{ $i }}">{{ $tab['label'] }}</button>
        @endforeach
    </div>

    @foreach ($tabs as $i => $tab)
        @php
            $tabImageUrl = !empty($tab['image_id'])
                ? $servicePage->images->firstWhere('id', $tab['image_id'])?->url
                : null;
        @endphp
        <div class="svc-tabs__panel" x-show="active === {{ $i }}" x-cloak role="tabpanel">
            @if (!empty($tab['subtitle']))<h3>{{ $tab['subtitle'] }}</h3>@endif
            @if (!empty($tab['body']))<p>{{ $tab['body'] }}</p>@endif
            @if (!empty($tab['bullets']))
                <ul>
                    @foreach ($tab['bullets'] as $bullet)
                        <li>{{ $bullet }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($tabImageUrl)
                <div class="svc-tabs__panel-media">
                    <img src="{{ $tabImageUrl }}" alt="">
                </div>
            @endif
        </div>
    @endforeach
</section>
@endif
