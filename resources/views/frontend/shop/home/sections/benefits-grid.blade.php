@php
    $titleStyle = $section->config['title_style'] ?? null;
    $titleTag = \App\Support\TextStyle::tag($titleStyle, 'h2', ['h2', 'h3']);
@endphp
@if (!empty($section->config['items']))
<section class="svc-benefits" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <{{ $titleTag }} class="svc-benefits__title"{!! \App\Support\TextStyle::attr($titleStyle) !!}>{{ $section->title }}</{{ $titleTag }}>
    @endif
    <div class="svc-benefits__grid {{ ($section->config['layout'] ?? 'horizontal') === 'vertical' ? 'svc-benefits__grid--vertical' : '' }}">
        @foreach ($section->config['items'] as $item)
            <div class="svc-benefits__card">
                @if (!empty($item['figure']))
                    <div class="svc-benefits__figure">{{ $item['figure'] }}</div>
                @endif
                @if (!empty($item['title']))
                    <p class="svc-benefits__card-title">{{ $item['title'] }}</p>
                @endif
                @if (!empty($item['description']))
                    <p class="svc-benefits__card-desc">{{ $item['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
