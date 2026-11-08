@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/about.css', 'resources/js/frontend/shop/base.js'];
    $metaTitle = 'Nosotros — Equiterm Industries';
    $metaDescription = 'Empresa mexicana con sede en Aguascalientes, especializada en sistemas de vapor, agua caliente y tratamiento de agua para plantas industriales, alimentos, hoteles y hospitales.';
    $canonicalUrl = route('about');

    // Fotos: se pegan como URL en Configuración del Sitio > Página Nosotros. Si el campo está vacío se usa la
    // foto por defecto de public/images/about/<slot>.jpg (con "_" -> "-"); si tampoco existe, un recuadro neutro.
    $photo = function (string $slot): string {
        $custom = trim((string) \App\Models\Setting::get('about.image_' . $slot, ''));
        if ($custom !== '') {
            return $custom;
        }
        $file = 'images/about/' . str_replace('_', '-', $slot) . '.jpg';

        return file_exists(public_path($file)) ? asset($file) : '';
    };

    $whatsappUrl = 'https://wa.me/' . \App\Models\Setting::get('footer.phone_link', '5214494577320')
        . '?text=' . urlencode('Hola, me gustaría información sobre sus servicios.');

    $areas = [
        ['slot' => 'area_1', 'alt' => 'Caldera industrial en cuarto de máquinas', 'title' => 'Mantenimiento de calderas y calentadores',
         'icon' => '<rect x="4" y="6" width="16" height="13" rx="2"></rect><path d="M8 6V3h8v3M8 12h8M8 15.5h5"></path><circle cx="17" cy="15.5" r="1"></circle>'],
        ['slot' => 'area_2', 'alt' => 'Quemador de una caldera industrial', 'title' => 'Calibración y conversión de quemadores',
         'icon' => '<path d="M12 3c2.5 3 4.5 5.5 4.5 8.5a4.5 4.5 0 0 1-9 0c0-1.6.7-3 1.8-4.2.3 1.4 1 2.2 1.9 2.7C11 7.8 11.3 5.4 12 3z"></path><path d="M5 21h14"></path>'],
        ['slot' => 'area_3', 'alt' => 'Equipos de tratamiento de agua', 'title' => 'Tratamiento de agua con la línea Caldesim',
         'icon' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"></path><path d="M9.5 14.5a2.5 2.5 0 0 0 2.5 2.5"></path>'],
    ];

    $steps = [
        ['text' => 'Evaluamos las condiciones reales de operación', 'icon' => '<path d="M3 12a9 9 0 1 1 18 0"></path><path d="M12 12l4-4M7 16h10"></path>'],
        ['text' => 'Documentamos los hallazgos', 'icon' => '<path d="M6 3h9l4 4v14H6z"></path><path d="M14 3v5h5M9 13h7M9 17h5"></path>'],
        ['text' => 'Explicamos qué requiere atención y por qué', 'icon' => '<path d="M4 5h16v11H9l-5 4z"></path><path d="M8 9.5h8M8 12.5h5"></path>'],
    ];

    $sectors = [
        ['slot' => 'sector_1', 'alt' => 'Planta industrial', 'title' => 'Plantas industriales',
         'icon' => '<path d="M3 21V10l5 3V10l5 3V6h3l1-3h2l1 3v15H3z"></path><path d="M7 17h2M12 17h2"></path>'],
        ['slot' => 'sector_2', 'alt' => 'Tuberías de acero inoxidable de una línea de proceso', 'title' => 'Empresas de alimentos',
         'icon' => '<path d="M7 3v8a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2V3M9 13v8M17 21V3c-2 1-3 4-3 8h3"></path>'],
        ['slot' => 'sector_3', 'alt' => 'Edificios en una zona urbana', 'title' => 'Hoteles',
         'icon' => '<path d="M3 19V6M3 15h18v4M21 15v-3a3 3 0 0 0-3-3h-7v6"></path><circle cx="7" cy="11.5" r="1.8"></circle>'],
        ['slot' => 'sector_4', 'alt' => 'Equipo de control en un área técnica limpia', 'title' => 'Hospitales',
         'icon' => '<rect x="3" y="3" width="18" height="18" rx="2.5"></rect><path d="M12 8v8M8 12h8"></path>'],
    ];

    $values = [
        ['title' => 'Seguridad', 'text' => 'Cada intervención cuida a las personas y a los equipos antes que a los tiempos.',
         'icon' => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path>'],
        ['title' => 'Transparencia', 'text' => 'Decimos lo que encontramos y explicamos por qué, con evidencia documentada.',
         'icon' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>'],
        ['title' => 'Rigor técnico', 'text' => 'Trabajamos con base en las condiciones reales de operación, no en supuestos.',
         'icon' => '<path d="M14.5 6.5a4 4 0 0 0-5.3 5.3L4 17v3h3l5.2-5.2a4 4 0 0 0 5.3-5.3l-2.5 2.5-2.5-.5-.5-2.5z"></path>'],
        ['title' => 'Compromiso', 'text' => 'Acompañamos al cliente más allá del servicio para que su sistema opere mejor cada año.',
         'icon' => '<path d="M12 3a9 9 0 1 0 9 9"></path><path d="M21 3v6h-6"></path><path d="M21 3l-7 7"></path>'],
    ];
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('canonical', $canonicalUrl)
@section('og_title', $metaTitle)
@section('og_description', $metaDescription)
@section('og_url', $canonicalUrl)

{{-- Foto con respaldo: si no hay URL configurada se pinta un recuadro neutro del mismo tamaño. --}}
@php
    $renderPhoto = function (string $slot, string $alt, string $class = '') use ($photo): string {
        $src = $photo($slot);
        if ($src !== '') {
            return '<img class="eq-about__img ' . e($class) . '" src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">';
        }
        return '<div class="eq-about__img eq-about__img--empty ' . e($class) . '" role="img" aria-label="' . e($alt) . '">'
            . '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="9" cy="11" r="1.6"></circle><path d="m21 16-5-5-8 8"></path></svg></div>';
    };
@endphp

@section('content')
<div class="eq-shop-about">
    {{-- Quiénes somos --}}
    <section class="eq-about__hero">
        <div class="eq-about__wrap">
            <nav class="eq-about__crumbs" aria-label="Ruta de navegación">
                <a href="{{ route('home') }}">Inicio</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Nosotros</span>
            </nav>
            <div class="eq-about__hero-grid">
                <div class="eq-about__hero-copy">
                    <div class="eq-about__bar" aria-hidden="true"></div>
                    <h1>Quiénes somos</h1>
                    <p>En Equiterm Industries somos una empresa mexicana con sede en Aguascalientes, especializada en sistemas de vapor, agua caliente y tratamiento de agua. Atendemos a plantas industriales, empresas de alimentos, hoteles y hospitales donde la continuidad del servicio depende del buen funcionamiento de sus equipos.</p>
                </div>
                <div class="eq-about__mosaic">
                    <div class="eq-about__mosaic-main">
                        {!! $renderPhoto('hero', 'Técnico revisando una caldera en campo') !!}
                    </div>
                    {!! $renderPhoto('hero_2', 'Tuberías y válvulas de una sala de calderas') !!}
                    {!! $renderPhoto('hero_3', 'Motor de quemador en revisión') !!}
                </div>
            </div>
        </div>
    </section>

    {{-- Qué hacemos --}}
    <section class="eq-about__section" aria-labelledby="h-que">
        <div class="eq-about__wrap">
            <div class="eq-about__split">
                <h2 id="h-que" class="eq-about__h2">Qué hacemos</h2>
                <p class="eq-about__lead">Nuestro trabajo abarca el mantenimiento de calderas y calentadores, la calibración y conversión de quemadores y el tratamiento de agua con nuestra línea Caldesim. En cada servicio evaluamos las condiciones reales de operación, documentamos los hallazgos y explicamos al cliente qué requiere atención y por qué.</p>
            </div>
            <ul class="eq-about__areas">
                @foreach ($areas as $area)
                    <li class="eq-about__area">
                        <div class="eq-about__area-photo">{!! $renderPhoto($area['slot'], $area['alt']) !!}</div>
                        <div class="eq-about__area-body">
                            <span class="eq-about__icon eq-about__icon--soft" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c23f00" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $area['icon'] !!}</svg></span>
                            <h3>{{ $area['title'] }}</h3>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- En cada servicio --}}
    <section class="eq-about__method" aria-labelledby="h-metodo">
        <div class="eq-about__wrap eq-about__method-grid">
            <div class="eq-about__method-photo">{!! $renderPhoto('metodo', 'Dos especialistas evaluando una planta con una tableta') !!}</div>
            <div>
                <h2 id="h-metodo">En cada servicio</h2>
                <ol class="eq-about__steps">
                    @foreach ($steps as $step)
                        <li>
                            <span class="eq-about__icon eq-about__icon--brand" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#141516" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $step['icon'] !!}</svg></span>
                            <span>{{ $step['text'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Sectores --}}
    <section class="eq-about__section" aria-labelledby="h-sec">
        <div class="eq-about__wrap">
            <h2 id="h-sec" class="eq-about__h2 eq-about__h2--sm">Sectores que atendemos</h2>
            <ul class="eq-about__sectors">
                @foreach ($sectors as $sector)
                    <li>
                        <div class="eq-about__sector-photo">{!! $renderPhoto($sector['slot'], $sector['alt']) !!}</div>
                        <div class="eq-about__sector-label">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#141516" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $sector['icon'] !!}</svg>
                            <span>{{ $sector['title'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Misión y visión --}}
    <section class="eq-about__mv" aria-label="Misión y visión">
        <div class="eq-about__wrap eq-about__mv-grid">
            <article class="eq-about__card">
                <span class="eq-about__icon eq-about__icon--lg eq-about__icon--brand" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#141516" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"></circle><circle cx="12" cy="12" r="4.5"></circle><circle cx="12" cy="12" r="1" fill="#141516"></circle></svg></span>
                <h2 class="eq-about__h2 eq-about__h2--sm">Misión</h2>
                <p>Ayudar a nuestros clientes a mantener un suministro confiable de vapor y agua caliente. Trabajamos para prevenir fallas, aprovechar mejor el combustible y prolongar la vida útil de sus equipos mediante mantenimiento especializado, control de la combustión y tratamiento de agua.</p>
            </article>
            <article class="eq-about__card">
                <span class="eq-about__icon eq-about__icon--lg eq-about__icon--dark" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
                <h2 class="eq-about__h2 eq-about__h2--sm">Visión</h2>
                <p>Ser un referente en México en el cuidado y la modernización de sistemas térmicos industriales, reconocido por la calidad del trabajo en campo, la claridad de nuestras recomendaciones y la confianza que construimos con cada cliente.</p>
            </article>
        </div>
    </section>

    {{-- Valores --}}
    <section class="eq-about__section eq-about__section--flush" aria-labelledby="h-val">
        <div class="eq-about__wrap">
            <h2 id="h-val" class="eq-about__h2 eq-about__h2--md">Valores</h2>
            <ul class="eq-about__values">
                @foreach ($values as $value)
                    <li>
                        <span class="eq-about__icon eq-about__icon--soft" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c23f00" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $value['icon'] !!}</svg></span>
                        <h3>{{ $value['title'] }}</h3>
                        <p>{{ $value['text'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Cierre --}}
    <section class="eq-about__section" aria-labelledby="h-cierre">
        <div class="eq-about__wrap eq-about__cta">
            <h2 id="h-cierre">Un suministro confiable de vapor y agua caliente.</h2>
            <div class="eq-about__cta-actions">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener nofollow" class="eq-about__btn eq-about__btn--primary" data-ad-track="whatsapp_click" data-ad-element="nosotros">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.3-.4.8-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.4-.2z"></path></svg>
                    Escríbenos por WhatsApp
                </a>
                <a href="{{ route('service-pages.hub') }}" class="eq-about__btn eq-about__btn--outline">
                    Ver servicios
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
