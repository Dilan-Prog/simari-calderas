@if (!empty($section->config['items']))
<section class="svc-benefits" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <h2 class="svc-benefits__title">{{ $section->title }}</h2>
    @endif
    <div class="svc-benefits__grid">
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
