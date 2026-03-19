@extends('frontend.layouts.master')

@section('styles')
    @vite('resources/css/company.css')
@endsection

@section('content')
        <section class="company-hero">
            <div class="company-hero-bg">
                <img src="https://images.unsplash.com/photo-1707330266686-109c087163eb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwZW5naW5lZXJpbmclMjBjb3Jwb3JhdGUlMjBidWlsZGluZyUyMHRlYW18ZW58MXx8fHwxNzcxNTQzMDgzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                    alt="Equipo SIMARI Calderas">
                <div class="company-hero-overlay"></div>
            </div>

            <div class="container company-hero-content">
                <h1 class="company-title-main">
                    Más de Tres Décadas <br>
                    <span class="text-red">Impulsando la Industria</span>
                </h1>
                <p class="company-hero-desc">Líderes en soluciones térmicas desde 1992.</p>
            </div>
        </section>

        <section class="company-section">
            <div class="container company-grid-history">
                <div class="company-history-text">
                    <p class="company-badge">¿Quiénes Somos?</p>
                    <h2 class="company-section-title">Historia de Crecimiento y Excelencia</h2>
                        <p class="company-section-desc">Somos una empresa hidrocálida fundada en 1992, con más de 35 años de experiencia. Comenzamos
                            con una persona y hemos crecido hasta formar una estructura sólida y profesional.</p>
                        <p class="company-section-desc">Nos dedicamos a la gestión completa de sistemas para calentar, filtrar y controlar
                            transferencias térmicas, ofreciendo soluciones eficientes a sectores como el <strong
                            >industrial, alimentario, metalmecánico, hotelero y deportivo</strong>.
                        </p>
                        <p class="company-section-desc">Nuestra experiencia nos permite realizar estudios profundos para mejorar el uso de energía y
                            reducir costos operativos tanto en la industria como en servicios comerciales.</p>
                        <p class="company-section-desc">Ofrecemos soluciones avanzadas, incluyendo la actualización de calentadores de agua
                            convencionales a sistemas de alto rendimiento. Como <strong class="text-red">Centro de
                                Servicio y Distribuidor Autorizado</strong>, brindamos atención completa para minimizar
                            tiempos de inactividad y optimizar sus gastos.</p>
                </div>

                <div class="company-history-img">
                    <div class="company-img-decor"></div>
                    <img src="https://images.unsplash.com/photo-1631583087046-13c813d34e90?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwZW5naW5lZXJpbmclMjB0ZWFtJTIwc2FmZXR5JTIwaGVsbWV0c3xlbnwxfHx8fDE3NzE1MjI0Mzd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                        alt="Ingenieros SIMARI">
                </div>
            </div>
        </section>

        <section class="company-section-pillars">
            <div class="container">
                <div class="company-section-header">
                    <p class="company-badge">Nuestros Pilares</p>
                    <h2 class="company-section-title">Valores que nos Definen</h2>
                </div>

                <div class="company-grid-values">
                    <div class="company-value-card">
                        <div class="company-icon-box icon-blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                </path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                        </div>
                        <h4 class="">Confianza</h4>
                        <p class="">Construimos relaciones duraderas basadas en la transparencia y el cumplimiento
                            de nuestros compromisos.</p>
                    </div>

                    <div class="company-value-card">
                        <div class="company-icon-box icon-red-light">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                </path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                        </div>
                        <h4 class="">Seguridad</h4>
                        <p class="">Priorizamos la integridad de su personal y sus instalaciones con protocolos
                            rigurosos.</p>
                    </div>

                    <div class="company-value-card">
                        <div class="company-icon-box icon-yellow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                                </path>
                                <circle cx="12" cy="8" r="6"></circle>
                            </svg>
                        </div>
                        <h4 class="">Especialización</h4>
                        <p class="">Nuestro equipo técnico cuenta con certificaciones internacionales y
                            capacitación continua.</p>
                    </div>

                    <div class="company-value-card">
                        <div class="company-icon-box icon-green">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z">
                                </path>
                            </svg>
                        </div>
                        <h4 class="">Integridad en el Servicio</h4>
                        <p class="">Honestidad en diagnósticos y soluciones. Nunca recomendaremos algo que no
                            necesite.</p>
                    </div>

                    <div class="company-value-card">
                        <div class="company-icon-box icon-purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5">
                                </path>
                                <path d="M9 18h6"></path>
                                <path d="M10 22h4"></path>
                            </svg>
                        </div>
                        <h4 class="">Innovación</h4>
                        <p class="">Adoptamos las últimas tecnologías para maximizar la eficiencia energética de
                            sus procesos.</p>
                    </div>
                </div>
            </div>
        </section>
@endsection
