@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/service-page.css', 'resources/js/frontend/shop/service-page.js'];

    $metaTitle = $servicePage->seo_title ?: ($servicePage->name . ' — Equiterm Industries');
    $metaDescription = $servicePage->seo_description
        ?: \Illuminate\Support\Str::limit(strip_tags($servicePage->short_description ?? $servicePage->description ?? ''), 160)
        ?: ('Conoce el servicio ' . $servicePage->name . ' de Equiterm Industries.');
    $canonicalUrl = route('service-page.show', $servicePage->slug);

    $ogImage = $servicePage->og_image_url ?: $servicePage->cover_image_url;
    if ($ogImage && !str_starts_with($ogImage, 'http')) {
        $ogImage = \App\Support\UploadPath::url($ogImage);
    }
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('canonical', $canonicalUrl)
@section('og_title', $metaTitle)
@section('og_description', $metaDescription)
@section('og_url', $canonicalUrl)
@if ($ogImage)
    @section('og_image', $ogImage)
@endif

@php
    $schemaFaqs = collect($servicePage->faqs ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->values();
    $faqSectionActive = $sections->contains(fn ($s) => $s->type === 'faq');
    $hasRichHeader = $sections->contains(fn ($s) => $s->type === 'rich_header');

    // El JSON-LD de rating SOLO se deriva de reseñas individuales reales y
    // visibles — NUNCA de las estadísticas de marketing editables a mano
    // (rating_average_displayed/rating_total_rated/etc.), que son solo copy
    // visual. Ver comentario en la migración add_rating_stats_to_service_pages_table.
    $ratingSectionActive = $sections->contains(fn ($s) => $s->type === 'rating_reviews');
    $visibleReviews = $ratingSectionActive ? $servicePage->visibleReviews()->get() : collect();
@endphp

@section('schema')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $servicePage->name,
            'url' => $canonicalUrl,
            'description' => $metaDescription,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Equiterm Industries',
            ],
            'aggregateRating' => $visibleReviews->isNotEmpty() ? [
                '@type' => 'AggregateRating',
                'ratingValue' => round($visibleReviews->avg('rating'), 1),
                'reviewCount' => $visibleReviews->count(),
                'bestRating' => 5,
                'worstRating' => 1,
            ] : null,
            'review' => $visibleReviews->isNotEmpty() ? $visibleReviews->map(fn ($r) => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $r->customer_name],
                'datePublished' => optional($r->review_date)->toDateString(),
                'reviewBody' => $r->comment,
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
            ])->values()->all() : null,
        ]), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @if ($faqSectionActive && $schemaFaqs->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schemaFaqs->map(fn ($item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ])->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
@endsection

@section('content')
<div class="eq-shop-service">
    <div class="collection-breadcrumb">
        <a href="{{ route('home') }}">Inicio</a>
        &nbsp;›&nbsp; <span>{{ $servicePage->name }}</span>
    </div>

    @unless ($hasRichHeader)
        {{-- Hero de respaldo: se omite cuando el servicio ya tiene un bloque
             "Encabezado enriquecido" activo, para no duplicar el H1. --}}
        <section class="collection-hero {{ $servicePage->cover_image_url ? 'collection-hero--with-image' : '' }}">
            <div class="collection-hero__text">
                <h1>{{ $servicePage->name }}</h1>
                @if ($servicePage->short_description)
                    <p>{{ $servicePage->short_description }}</p>
                @endif
                @if ($servicePage->price)
                    <span class="collection-hero__count">Desde ${{ number_format($servicePage->price, 2) }} {{ $servicePage->currency }}</span>
                @endif
            </div>
            @if ($servicePage->cover_image_url)
                <div class="collection-hero__image">
                    <img src="{{ $servicePage->cover_image_url }}" alt="{{ $servicePage->name }}">
                </div>
            @endif
        </section>
    @endunless

    @if ($servicePage->description && !$sections->contains(fn ($s) => $s->type === 'content_tabs'))
        {{-- Respaldo de solo lectura: servicios aún no migrados al bloque
             "Descripción por secciones" (ver comando service-pages:migrate-descriptions). --}}
        <section class="collection-grid-wrap">
            <p>{{ $servicePage->description }}</p>
        </section>
    @endif

    {{-- Secciones administrables desde Admin > Páginas de Servicio > {servicio} --}}
    @foreach ($sections as $section)
        @include('frontend.shop.home.sections.' . str_replace('_', '-', $section->type), ['section' => $section, 'servicePage' => $servicePage, 'previewMode' => $previewMode ?? false])
    @endforeach
</div>
@endsection
