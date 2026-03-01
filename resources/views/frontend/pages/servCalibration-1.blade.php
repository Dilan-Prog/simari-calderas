@extends('frontend.layouts.master')

@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/servCalibration.css') }}">
@endsection

@section('content')
    {{-- Main section --}}
    <section class="first-content-servCalibation">
        {{-- main text --}}
        <div class="textServCalibration">
            <h1>Calibración de Equipos y Quemadores Industriales</h1>
            <p>Optimización precisa de parámetros de combustión y
                calibración certificada de instrumentos para máxima
                eficiencia energética.
            </p>
            {{-- Button declaration --}}
            <button class="button-primary">Solicitar Cotizacion
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>
    </section>

    {{-- second section --}}
    <section class="second-content-ServCalibration">
        <div class="second-text-ServCalibration">
            <h2>¿En qué consiste el servicio de Calibración?</h2>
            <p>La calibración profesional de quemadores industriales y
                equipos de medición garantiza combustión eficiente, reducción de emisiones
                contaminantes y precisión en el control de procesos térmicos críticos. </p>

            <p>Realizamos ajustes técnicos de relación aire-combustible, presiones de operación,
                temperatura de flama y sincronización de controles de seguridad.</p>
        </div>

        <div class="red-table">
            <h2>Aplicaciones</h2>
            <ul class="applications__list">
                <li class="applications__item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Quemadores industriales
                </li>
                <li class="applications__item"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Manómetros y transmisores</li>
                <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Sensores de temperatura</li>
                <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Válvulas de seguridad</li>
                <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Controles de nivel</li>
                <li class="applications__item"> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>Analizadores de gases</li>
            </ul>
    </section>

    {{-- THIRD SECTION --}}
    <section class="accordion">
        <h2>Información Detallada</h2>
        <!-- FIRST TAB -->
        <article class="accordion__item active">
            <button class="accordion__header">
                {{-- SYMBOL --}}
                <div class="accordion__icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-target text-white">
                        <circle cx="12" cy="12" r="10"></circle>
                        <circle cx="12" cy="12" r="6"></circle>
                        <circle cx="12" cy="12" r="2"></circle>
                    </svg>
                </div>

                <h3 class="accordion__title">
                    ¿Qué Incluye el Servicio?
                </h3>
                <div class="accordion__toggle" aria-label="Toggle"></div>
            </button>

            {{-- Contents of the first tab --}}
            <div class="accordion__content">
                <div class="accordion__grid">
                    <div>
                        <h4>Ajuste de Quemadores</h4>
                        <p>Optimización de relación aire-combustible, ajuste de presiones de gas y sincronización de
                            sistemas modulantes.</p>

                        <h4>Calibración de Instrumentos</h4>
                        <p>Verificación con patrones certificados de manómetros, termómetros y transmisores.</p>

                        <h4>Certificados Trazables</h4>
                        <p>Emisión de certificados con trazabilidad a CENAM y cumplimiento NOM-008-SCFI.</p>
                    </div>

                    <div>
                        <h4>Análisis de Combustión</h4>
                        <p>Medición de O2, CO, CO2, NOx, temperatura de gases y cálculo de eficiencia térmica.</p>

                        <h4>Pruebas de Seguridad</h4>
                        <p>Verificación de válvulas, sistemas de corte y protecciones térmicas.</p>

                        <h4>Informe Técnico</h4>
                        <p>Reporte con parámetros antes/después y recomendaciones técnicas.</p>
                    </div>

                </div>
            </div>
        </article>

        {{-- SECOND TAB  --}}
        <article class="accordion__item">
            <button class="accordion__header">
                <div class="accordion__icon-box">
                    {{-- SYMBOL --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                        <polyline points="16 7 22 7 22 13"></polyline>
                    </svg>
                </div>

                <h3 class="accordion__title">
                    Beneficios Operativos
                </h3>
                <div class="accordion__toggle" aria-label="Toggle"></div>
            </button>

            {{-- Contents of the second tab --}}
            <div class="accordion__content">
                <ul class="first_list">
                    <li class="list_element">
                        <div class="accordion__icon-box2">
                            12%
                        </div>
                        <div class="list_text">
                            <h4>Ahorro de Combustible</h4>
                            <p>Combustión optimizada reduce consumo mediante ajuste preciso de exceso de aire.</p>
                        </div>
                    </li>

                    <li class="list_element">
                        <div class="accordion__icon-box2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-shield text-white">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                </path>
                            </svg>
                        </div>
                        <div class="list_text">
                            <h4>Reducción de Emisiones</h4>
                            <p>Disminución de CO, NOx y partículas para cumplimiento ambiental.</p>
                        </div>
                    </li>

                    <li class="list_element">
                        <div class="accordion__icon-box2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-gauge text-white">
                                <path d="m12 14 4-4"></path>
                                <path d="M3.34 19a10 10 0 1 1 17.32 0"></path>
                            </svg>
                        </div>
                        <div class="list_text">
                            <h4>Precisión en Mediciones</h4>
                            <p>Control exacto de procesos críticos y decisiones basadas en datos reales.</p>
                        </div>
                    </li>

                    <li class="list_element">
                        <div class="accordion__icon-box2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-circle-check-big text-white">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>
                        <div class="list_text">
                            <h4>Cumplimiento Normativo</h4>
                            <p>Certificados trazables exigidos por NOM-020-STPS e ISO 9001.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </article>


        {{-- THIRD TAB --}}
        <article class="accordion__item">
            <button class="accordion__header">
                {{-- SYMBOL --}}
                <div class="accordion__icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-settings text-white">
                        <path
                            d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
                        </path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <h3 class="accordion__title">
                    Proceso de Trabajo
                </h3>
                <div class="accordion__toggle" aria-label="Toggle"></div>
            </button>

            {{-- Contents of the third tab --}}
            <div class="accordion__content">
                <div class="accordion__grid">
                    <ul class="first_list">
                        <li class="list_element">
                            <div class="accordion__icon-box2">
                                1
                            </div>
                            <div class="list_text">
                                <h4>Diagnóstico Previo</h4>
                                <p>Análisis de combustión inicial y revisión de instrumentos existentes.</p>
                            </div>
                        </li>

                        <li class="list_element">
                            <div class="accordion__icon-box2">
                                3
                            </div>
                            <div class="list_text">
                                <h4>Validación</h4>
                                <p>Medición final y verificación de eficiencia alcanzada.</p>
                            </div>
                        </li>
                    </ul>

                    <ul class="first_list">
                        <li class="list_element">
                            <div class="accordion__icon-box2">
                                2
                            </div>
                            <div class="list_text">
                                <h4>Calibración Técnica</h4>
                                <p>Ajuste con patrones certificados y verificación de seguridad.</p>
                            </div>
                        </li>

                        <li class="list_element">
                            <div class="accordion__icon-box2">
                                4
                            </div>
                            <div class="list_text">
                                <h4>Certificación</h4>
                                <p>Emisión de certificados trazables y programación anual.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </article>

        <!-- FOURTH TAB -->
        <article class="accordion__item">
            <button class="accordion__header">
                {{-- SYMBOL --}}
                <div class="accordion__icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-shield text-white">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                </div>

                <h3 class="accordion__title">
                    Por qué SIMARI CALDERAS
                </h3>
                <div class="accordion__toggle" aria-label="Toggle"></div>
            </button>

            {{-- Contents of the first tab --}}
            <div class="accordion__content">
                <div class="article-container-fourth-servCalibration">
                    <article class="especial-card-text-servCalibration">
                        <div class="super-especial-text-red">30+</div>
                        <h4>Años Experiencia</h4>
                        <p>Especialistas certificados en calibración.</p>
                    </article>

                    <article class="especial-card-text-servCalibration">
                        <div class="super-especial-text-red">100%</div>
                        <h4>Trazabilidad</h4>
                        <p>Patrones con trazabilidad a CENAM.</p>
                    </article>

                    <article class="especial-card-text-servCalibration">
                        <div class="super-especial-text-red">24/7</div>
                        <h4>Soporte</h4>
                        <p>Disponibilidad permanente.</p>
                    </article>
                </div>
            </div>
        </article>
    </section>

    {{-- SUB SECTION --}}
    <section class="first-content-servCalibation sub">
        <div class="textServCalibration sub">
            <h2>Optimice su Eficiencia Energética</h2>
            <p>Quemadores descalibrados generan desperdicios permanentes. Implemente calibración certificada profesional.
            </p>
            {{-- Buttons declaration --}}
            <div class="buttons-container-servCalibration">
                <button class="button-primary">Solicitar Calibracion
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none">
                        <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button class="button-primary secundary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-phone">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>Llamar Ahora
                </button>
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('.accordion__header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                const accordion = item.parentElement;
                // Close all other tabs
                accordion.querySelectorAll('.accordion__item').forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                // Toggle the current tab state
                item.classList.toggle('active');
            });
        });
    </script>
@endsection
