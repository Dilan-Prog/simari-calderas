@php
    $config = $section->config ?? [];
    $faqProduct = $product ?? null;
    $items = collect($config['items'] ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->values();
@endphp

@if ($items->isNotEmpty())
<section class="home-faq">
    @if ($section->title)
        <h2 class="home-faq__title">{{ $section->resolveText($section->title, $faqProduct) }}</h2>
    @endif
    @if (!empty($config['description']))
        <p class="home-faq__description">{{ $section->resolveText($config['description'], $faqProduct) }}</p>
    @endif

    <div class="home-faq__list" x-data="{ open: null }">
        @foreach ($items as $i => $item)
            <div class="home-faq__item">
                <button type="button" class="home-faq__question"
                    @click="open = open === {{ $i }} ? null : {{ $i }}">
                    <span>{{ $section->resolveText($item['question'], $faqProduct) }}</span>
                    <span x-text="open === {{ $i }} ? '−' : '+'">+</span>
                </button>
                <div class="home-faq__answer" x-show="open === {{ $i }}" x-cloak>
                    <p>{{ $section->resolveText($item['answer'], $faqProduct) }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
