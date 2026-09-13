@php
    $config = $section->config ?? [];
    // Dueño de las FAQs según la página: producto o colección (misma
    // estructura [{question, answer}]); la sección solo aporta
    // título/descripción. Sin dueño o sin FAQs, no se muestra.
    $faqOwner = $product ?? $collection ?? $servicePage ?? null;
    $isProductOwner = $faqOwner instanceof \App\Models\Products;
    $items = collect($faqOwner?->faqs ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->map(fn ($item) => [
            'question' => $isProductOwner ? $faqOwner->resolveVariables($item['question']) : $item['question'],
            'answer'   => $isProductOwner ? $faqOwner->resolveVariables($item['answer']) : $item['answer'],
        ])
        ->values();
@endphp

@php
    $titleStyle = $config['title_style'] ?? null;
    $titleTag = \App\Support\TextStyle::tag($titleStyle, 'h2', ['h2', 'h3']);
@endphp
@if ($items->isNotEmpty())
<section class="home-faq">
    @if ($section->title)
        <{{ $titleTag }} class="home-faq__title"{!! \App\Support\TextStyle::attr($titleStyle) !!}>{{ $section->resolveText($section->title, $faqOwner) }}</{{ $titleTag }}>
    @endif
    @if (!empty($config['description']))
        <p class="home-faq__description">{{ $section->resolveText($config['description'], $faqOwner) }}</p>
    @endif

    <div class="home-faq__list" x-data="{ open: null }">
        @foreach ($items as $i => $item)
            <div class="home-faq__item">
                <button type="button" class="home-faq__question"
                    @click="open = open === {{ $i }} ? null : {{ $i }}">
                    <span>{{ $item['question'] }}</span>
                    <span x-text="open === {{ $i }} ? '−' : '+'">+</span>
                </button>
                <div class="home-faq__answer" x-show="open === {{ $i }}" x-cloak>
                    <p>{!! \App\Support\TextLinks::render($item['answer']) !!}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
