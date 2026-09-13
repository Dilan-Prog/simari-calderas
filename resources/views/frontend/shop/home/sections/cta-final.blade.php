@php
    $config = $section->config ?? [];

    $ctaBgUrl = !empty($config['background_image_id'])
        ? $servicePage->images->firstWhere('id', $config['background_image_id'])?->url
        : null;

    $whatsappMessage = "Hola, me interesa cotizar el servicio: {$servicePage->name} - " . route('service-page.show', $servicePage->slug);
    $whatsappUrl = 'https://wa.me/' . \App\Models\Setting::get('footer.phone_link', '5214494577320') . '?text=' . urlencode($whatsappMessage);
@endphp

@if (!empty($config['headline']) || !empty($config['subtext']))
<section class="svc-cta-final" @if ($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($ctaBgUrl)
        <div class="svc-cta-final__bg">
            <img src="{{ $ctaBgUrl }}" alt="">
        </div>
    @endif
    <div class="svc-cta-final__inner">
        @if (!empty($config['headline']))<h2>{{ $config['headline'] }}</h2>@endif
        @if (!empty($config['subtext']))<p class="svc-cta-final__subtext">{{ $config['subtext'] }}</p>@endif

        @if (!empty($config['whatsapp_text']))
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener nofollow" class="svc-whatsapp-btn" data-ad-track="quote_start">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.92 0-2.65-1.03-5.14-2.9-7.01A9.87 9.87 0 0 0 12.04 2m0 1.67a8.2 8.2 0 0 1 5.83 2.42 8.18 8.18 0 0 1 2.42 5.82c0 4.55-3.7 8.25-8.26 8.25a8.24 8.24 0 0 1-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.27-4.4c0-4.56 3.71-8.23 8.27-8.23m-4.55 4.72c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02 0 1.19.87 2.34.99 2.5.12.16 1.7 2.72 4.2 3.7 2.08.81 2.5.65 2.95.61.45-.04 1.45-.59 1.65-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.46-.28-.24-.12-1.45-.71-1.67-.79-.22-.08-.39-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.02-.37-1.94-1.19-.72-.63-1.2-1.42-1.34-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.46z"/></svg>
                <span>{{ $config['whatsapp_text'] }}</span>
            </a>
        @endif
    </div>
</section>
@endif
