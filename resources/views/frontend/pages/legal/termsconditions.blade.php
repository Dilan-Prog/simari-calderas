@extends('frontend.layouts.master')

@section('styles')
    @vite(['resources/css/privacy-notice.css'])
@endsection

@section('content')
<section class="simari-privacy-body" aria-label="Contenido de términos y condiciones">

    {{-- HERO con look Tailwind --}}
    <section class="simari-privacy-presentation simari-hero">
        <div class="simari-hero__bg" aria-hidden="true"></div>

        <div class="presentation-container simari-hero__container">
            <div class="simari-hero__badge" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="lucide lucide-scale">
                    <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                    <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                    <path d="M7 21h10"></path>
                    <path d="M12 3v18"></path>
                    <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
                </svg>
            </div>

            <h1 class="simari-hero__title">Términos y Condiciones</h1>

            <p class="presentation-subcontent-title simari-hero__subtitle">
                Condiciones generales de uso del sitio web y contratación de servicios de SIMARI Calderas
            </p>

            <p class="presentation-subcontent-date simari-hero__date">
                Última actualización: 28 de febrero de 2026
            </p>
        </div>
    </section>

    <div class="simari-privacy-body__inner">
        <div class="simari-privacy-body__stack">
            <article class="simari-privacy-article" aria-label="Términos y Condiciones de SIMARI">

                {{-- ALERT tipo warning --}}
                <section class="simari-privacy-card simari-alert" aria-labelledby="sec-importante">
                    <div class="simari-privacy-card__row">
                        <div class="simari-privacy-card__badge simari-alert__icon" aria-hidden="true">
                            <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                        </div>

                        <div class="simari-privacy-card__content">
                            <h2 class="simari-privacy-card__title" id="sec-importante">Importante</h2>
                            <div class="simari-privacy-card__text">
                                <p>
                                    Al utilizar nuestro sitio web y servicios, usted acepta estos términos y condiciones en su totalidad.
                                    Le recomendamos leer cuidadosamente toda la información antes de realizar cualquier transacción comercial.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- CARDS (reutilizando tus clases) --}}
                {{-- Aquí van tus secciones simari-privacy-card exactamente como ya te las dejé --}}
                {{-- ... (Aceptación, Uso del sitio, PI, Productos, Cotizaciones, etc.) --}}

                {{-- CTA final oscuro --}}
                <section class="simari-privacy-cta simari-cta--dark" aria-labelledby="sec-cta-terms">
                    <h2 class="simari-privacy-cta__title" id="sec-cta-terms">
                        <svg class="simari-privacy-icon simari-privacy-icon--lg simari-privacy-icon--primary"
                             xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                        Tu confianza es nuestra prioridad
                    </h2>

                    <p class="simari-privacy-cta__text">
                        En SIMARI nos comprometemos a proporcionar equipos de la más alta calidad y un servicio excepcional.
                        Estos términos están diseñados para proteger tanto tus derechos como los nuestros.
                    </p>

                    <div class="simari-privacy-cta__grid">
                        <div class="simari-privacy-contact">
                            <svg class="simari-privacy-icon simari-privacy-icon--sm simari-privacy-icon--primary"
                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>

                            <div class="simari-privacy-contact__body">
                                <p class="simari-privacy-contact__label">Correo electrónico</p>
                                <p class="simari-privacy-contact__value">
                                    <a class="simari-privacy-link" href="mailto:info@simaricalderas.com">
                                        info@simaricalderas.com
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="simari-privacy-contact">
                            <svg class="simari-privacy-icon simari-privacy-icon--sm simari-privacy-icon--primary"
                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>

                            <div class="simari-privacy-contact__body">
                                <p class="simari-privacy-contact__label">Teléfono</p>
                                <p class="simari-privacy-contact__value">
                                    <a class="simari-privacy-link" href="tel:+525512345678">+52 (55) 1234-5678</a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="simari-cta__footer">
                        © 2026 SIMARI Calderas S.A. de C.V. - Todos los derechos reservados
                    </div>
                </section>

            </article>
        </div>
    </div>
</section>
@endsection