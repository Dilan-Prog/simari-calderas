@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/products.css') }}">
@endsection
@section('content')
    <section class="section-product">
        <div class="container">
            <div class="premiun-title">
                {{-- etiqueta icono dentro de la etiqueta p --}}
                <p><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <g clip-path="url(#clip0_20_1189)">
                            <path
                                d="M6.37458 10.8742C6.87183 10.8742 7.34871 10.6766 7.70032 10.325C8.05192 9.97343 8.24946 9.49655 8.24946 8.99931C8.24946 7.96437 7.87448 7.49941 7.49951 6.74946C6.69556 5.14231 7.33152 3.70916 8.99941 2.24976C9.37438 4.12463 10.4993 5.92451 11.9992 7.12443C13.4991 8.32435 14.2491 9.74926 14.2491 11.2492C14.2491 11.9385 14.1133 12.6212 13.8494 13.2581C13.5856 13.895 13.1989 14.4737 12.7115 14.9612C12.224 15.4487 11.6453 15.8354 11.0084 16.0992C10.3714 16.363 9.6888 16.4988 8.99941 16.4988C8.31001 16.4988 7.62737 16.363 6.99045 16.0992C6.35353 15.8354 5.77482 15.4487 5.28734 14.9612C4.79987 14.4737 4.41318 13.895 4.14936 13.2581C3.88554 12.6212 3.74976 11.9385 3.74976 11.2492C3.74976 10.3845 4.07448 9.52877 4.49971 8.99931C4.49971 9.49655 4.69724 9.97343 5.04884 10.325C5.40045 10.6766 5.87733 10.8742 6.37458 10.8742Z"
                                stroke="white" stroke-width="1.4999" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <clipPath id="clip0_20_1189">
                                <rect width="17.9988" height="17.9988" fill="white" />
                            </clipPath>
                        </defs>
                    </svg> Serie Premiun</p>
            </div>
            <h1 class="product-title">Calderas <span>Hyperion</span></h1>
            <p>Potencia desde <strong>20 hasta 500 HP.</strong></p>
            <p class="product-subdescription">La serie Hyperion representa nuestra evolución tecnológica interna tras más de
                tres décadas de experiencia en
                campo. Diseñada para la industria que no se detiene.</p>
            <div class="button-container">
                <button class="button-primary">
                Solicitar Cotizacion <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none">
                    <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </button>
                <button class="button-secondary">
                    Ver Especificaciones
                </button>
            </div>

        </div>
    </section>
    <section class="section-product-2">
    </section>
    <section class="section-product-3">

    </section>
    <section class="section-product-4">

    </section>
@endsection
