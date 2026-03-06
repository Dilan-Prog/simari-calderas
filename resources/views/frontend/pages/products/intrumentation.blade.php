@extends('frontend.layouts.master')

@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/products-instrumentation.css') }}">
@endsection
@section('content')

    <body>
        {{-- main section of the page --}}
        <section class="main-instrumentation-section">
            <div class="container instrumentation">
                {{-- TOP MESSAGE --}}
                <div class="top-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-gauge" style="color: rgb(198, 40, 40);">
                        <path d="m12 14 4-4"></path>
                        <path d="M3.34 19a10 10 0 1 1 17.32 0"></path>
                    </svg>
                    <div>
                        <h3>INSTRUMENTACIÓN INDUSTRIAL</h3>
                        <p>Medición • Control • Monitoreoa</p>
                    </div>
                </div>
                {{-- TEXT MAIN SECTION --}}
                <h1>
                    Instrumentos
                    <br>
                    de
                    <span class="primary-span-instrumentation">Presición</span>
                    <br>
                    <span class="secundary-span-instrumentation">Industrial</span>
                </h1>
                <p class="description-text-instrumentation">
                    Soluciones completas de medición y control para procesos térmicos e industriales. Manómetros,
                    transmisores,
                    sensores, analizadores y sistemas de automatización.
                </p>

                {{-- CARTS --}}
                <div class="carts-main-instrumentation">
                    <article class="metric-card-instrumentation">
                        <header>
                            <div class="super-especial-text-red calibration">CENAM</div>
                        </header>
                        <p class="metric-card__label">Certificados</p>
                    </article>

                    <article class="metric-card-instrumentation">
                        <header>
                            <div class="super-especial-text-red calibration">±0.25%</div>
                        </header>
                        <p class="metric-card__label">Precisión</p>
                    </article>

                    <article class="metric-card-instrumentation">
                        <header>
                            <div class="super-especial-text-red calibration">1-3 años</div>
                        </header>
                        <p class="metric-card__label">Precisión</p>
                    </article>

                    <article class="metric-card-instrumentation">
                        <header>
                            <div class="super-especial-text-red calibration">200+ SKU</div>
                        </header>
                        <p class="metric-card__label">Garantía</p>
                    </article>
                </div>

                {{-- BUTTONS --}}
                <div class="buttons-container-industrial">
                    <button class="button-primary calibration">Solicitar Catálogo Técnico
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none">
                            <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <button class="button-secondary calibration">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>Asesoría Técnica</button>
                </div>
            </div>
        </section>

        {{-- COLOR CARDS CONTAINER --}}
        <section class="cards-instrumentation-section">
            <div class="container instrumentation">
                <div class="aplication-text-instrumentation">
                    <h2>Catálogo de Instrumentación</h2>
                    <p>6 familias principales con más de 200 productos en stock</p>
                </div>

                <div class="cards-instrumentation">

                    <!-- CARD 1 -->
                    <article class="card-instrumentation card">

                        <div class="card-instrumentation-header blue">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-gauge text-white mb-4">
                                    <path d="m12 14 4-4"></path>
                                    <path d="M3.34 19a10 10 0 1 1 17.32 0"></path>
                                </svg>
                            </div>
                            <h3>Medición de Presión</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Manómetros Análogos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-300 PSI</p>
                                    <p><span>Precisión:</span>
                                        ±0.2%</p>
                                </div>

                                <p class="especific-card-p-instrumentation">✓ Calderas, tuberías</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Manómetros Digitales</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-600 PSI</p>
                                    <p><span>Precisión:</span>
                                        ±0.5%</p>
                                </div>

                                <p>✓ Procesos críticos</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Transmisores 4-20mA</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-1000 PSI</p>
                                    <p><span>Precisión:</span>
                                        ±0.25%</p>
                                </div>

                                <p>✓ Control automatico</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Presostatos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Ajustable</p>
                                    <p><span>Precisión:</span>
                                        Switch</p>
                                </div>

                                <p>✓ Alarma, seguridad</p>
                            </div>

                            <button class="button-primary calibration card blue">Ver Detalles</button>
                        </div>
                    </article>


                    <!-- CARD 2 -->
                    <article class="card-instrumentation card">

                        <div class="card-instrumentation-header red">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-thermometer text-white mb-4">
                                    <path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>
                                </svg>
                            </div>
                            <h3>Medición de Temperatura</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Termómetros Bimetálicos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-400°C</p>
                                    <p><span>Precisión:</span>
                                        ±2°C</p>
                                </div>

                                <p>✓ Lectura local</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Termopares Tipo K</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        -200 a 1200°C</p>
                                    <p><span>Precisión:</span>
                                        ±1°C</p>
                                </div>

                                <p>✓ Alta temperatura</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>RTD Pt100</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        -200 a 600°C</p>
                                    <p><span>Precisión:</span>
                                        ±0.15°C</p>
                                </div>

                                <p>✓ Máxima precisión</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Termostatos Digitales</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-300°C</p>
                                    <p><span>Precisión:</span>
                                        ±0.5°C</p>
                                </div>

                                <p>✓ Control ON/OFF</p>
                            </div>

                            <button class="button-primary calibration card red">Ver Detalles</button>
                        </div>
                    </article>

                    <!-- CARD 3 -->
                    <article class="card-instrumentation card">

                        <div class="card-instrumentation-header green">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-droplets text-white mb-4">
                                    <path
                                        d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z">
                                    </path>
                                    <path
                                        d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                    </path>
                                </svg>
                            </div>
                            <h3>Medición de Nivel</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Visores de Nivel</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Visual</p>
                                    <p><span>Precisión:</span>
                                        Directa</p>
                                </div>

                                <p>✓ Tanques, calderas</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Flotadores Magnéticos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-6 metros</p>
                                    <p><span>Precisión:</span>
                                        ±5mm</p>
                                </div>

                                <p>✓ Nivel continuo</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Sensores Ultrasónicos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-15 metros</p>
                                    <p><span>Precisión:</span>
                                        ±3mm</p>
                                </div>

                                <p>✓ Sin contacto</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Interruptores de Nivel</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        ON/OFF</p>
                                    <p><span>Precisión:</span>
                                        Switch</p>
                                </div>

                                <p>✓ Alarmas</p>
                            </div>

                            <button class="button-primary calibration card green">Ver Detalles</button>
                        </div>
                    </article>

                    <!-- CARD 4 -->
                    <article class="card-instrumentation card">

                        <div class="card-instrumentation-header purple">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-wind text-white mb-4">
                                    <path d="M12.8 19.6A2 2 0 1 0 14 16H2"></path>
                                    <path d="M17.5 8a2.5 2.5 0 1 1 2 4H2"></path>
                                    <path d="M9.8 4.4A2 2 0 1 1 11 8H2"></path>
                                </svg>
                            </div>
                            <h3>Medición de Flujo</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Rotámetros</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        1-100 LPM</p>
                                    <p><span>Precisión:</span>
                                        ±5%</p>
                                </div>

                                <p>✓ Lectura visual</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Turbinas</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        10-1000 LPM</p>
                                    <p><span>Precisión:</span>
                                        ±1%</p>
                                </div>

                                <p>✓ Líquidos limpios</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Magnéticos</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        1-10000 LPM</p>
                                    <p><span>Precisión:</span>
                                        ±0.5%</p>
                                </div>

                                <p>✓ Agua, conductivos</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Vórtex</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Vapor, gas</p>
                                    <p><span>Precisión:</span>
                                        ±1%</p>
                                </div>

                                <p>✓ Alta temperatura</p>
                            </div>

                            <button class="button-primary calibration card purple">Ver Detalles</button>
                        </div>
                    </article>

                    <!-- CARD 5 -->
                    <article class="card-instrumentation card">

                        <div class="card-instrumentation-header yellow">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-activity text-white mb-4">
                                    <path
                                        d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2">
                                    </path>
                                </svg>
                            </div>
                            <h3>Análisis de Agua</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Medidores pH</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-14 pH</p>
                                    <p><span>Precisión:</span>
                                        ±0.02</p>
                                </div>

                                <p>✓ Control químico</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Conductivímetros</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-2000 µS</p>
                                    <p><span>Precisión:</span>
                                        ±1%</p>
                                </div>

                                <p>✓ TDS, salinidad</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Medidores ORP</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        ±2000 mV</p>
                                    <p><span>Precisión:</span>
                                        ±5mV</p>
                                </div>

                                <p>✓ Oxidación-reducción</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Analizadores O₂ Disuelto</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        0-600 PSI</p>
                                    <p><span>Precisión:</span>
                                        ±0.1</p>
                                </div>

                                <p>✓ Desgasificación</p>
                            </div>

                            <button class="button-primary calibration card red">Ver Detalles</button>
                        </div>
                    </article>

                    <!-- CARD 6 -->
                    <article class="card-instrumentation card">
                        <div class="card-instrumentation-header pink">
                            <div class="icon-circle-instrumentation">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-chart-column text-white mb-4">
                                    <path d="M3 3v16a2 2 0 0 0 2 2h16"></path>
                                    <path d="M18 17V9"></path>
                                    <path d="M13 17V5"></path>
                                    <path d="M8 17v-3"></path>
                                </svg>
                            </div>
                            <h3>Control y Automatización</h3>
                        </div>

                        <div class="card-instrumentation-body">

                            <div class="item-instrumentation">
                                <h4>Controladores PIDs</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Universal</p>
                                    <p><span>Precisión:</span>
                                        Digital</p>
                                </div>

                                <p>✓ Temperatura, nivel</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>Registradores</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Multi-canal</p>
                                    <p><span>Precisión:</span>
                                        Histórico</p>
                                </div>

                                <p>✓ Trazabilidad</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>PLCs Industriales</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        Modular</p>
                                    <p><span>Precisión:</span>
                                        Lógica</p>
                                </div>

                                <p>✓ Automatización total</p>
                            </div>

                            <hr>

                            <div class="item-instrumentation">
                                <h4>HMI Pantallas Táctiles</h4>

                                <div class="item-instrumentation-info">
                                    <p><span>Rango:</span>
                                        7-15 pulgadas</p>
                                    <p><span>Precisión:</span>
                                        Interfaz</p>
                                </div>

                                <p>✓ Operación/monitoreo</p>
                            </div>

                            <button class="button-primary calibration card pink">Ver Detalles</button>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- HORIZONTAL BLACK CARDS --}}
        <section class="instrumentation-section">
            <div class="container black-cards-instrumentation"> <!-- CARD -->
                <div class="aplication-text-instrumentation">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-triangle-alert mx-auto mb-6"
                        style="color: rgb(198, 40, 40);">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                    <h2>Aplicaciones Críticas por Sistema</h2>
                    <p>Instrumentación obligatoria y recomendada según normatividad vigente</p>
                </div>
                {{-- BLACK CARD 1 --}}
                <div>
                    <article class="instrumentation-card">
                        <!-- LEFT SIDE -->
                        <div class="instrumentation-info">
                            <div class="instrumentation-header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-radio"
                                    style="color: rgb(198, 40, 40);">
                                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path>
                                    <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path>
                                    <path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path>
                                </svg>
                                <div class="principal-text-black-cards">
                                    <h3>Cuarto de Calderas</h3>
                                    <span class="instrumentation-badge alta">ALTA</span>
                                </div>
                            </div>

                            <p class="instrumentation-note">📋 NOM-020-STPS obligatorio</p>

                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="instrumentation-required">

                            <h4>INSTRUMENTACIÓN REQUERIDA:</h4>

                            <div class="instrumentation-tags">

                                <p class="instrumentation-tag">Manómetro de vapor</p>
                                <p class="instrumentation-tag">Manómetro de gas</p>
                                <p class="instrumentation-tag">Termómetro chimenea</p>
                                <p class="instrumentation-tag">Control nivel agua</p>
                                <p class="instrumentation-tag">Analizador combustión</p>

                            </div>

                        </div>

                    </article>

                    {{-- BLACK CARD 2 --}}
                    <article class="instrumentation-card">
                        <!-- LEFT SIDE -->
                        <div class="instrumentation-info">
                            <div class="instrumentation-header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-radio"
                                    style="color: rgb(198, 40, 40);">
                                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path>
                                    <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path>
                                    <path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path>
                                </svg>
                                <div class="principal-text-black-cards">
                                    <h3>Tratamiento de Agua</h3>
                                    <span class="instrumentation-badge media">MEDIA-ALTA</span>
                                </div>
                            </div>

                            <p class="instrumentation-note">📋 Depende del proceso</p>

                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="instrumentation-required">

                            <h4>INSTRUMENTACIÓN REQUERIDA:</h4>

                            <div class="instrumentation-tags">

                                <p class="instrumentation-tag">Medidor pH</p>
                                <p class="instrumentation-tag">Conductivímetro</p>
                                <p class="instrumentation-tag">Medidor ORP</p>
                                <p class="instrumentation-tag">Bomba dosificadora</p>
                                <p class="instrumentation-tag">Totalizador de flujo</p>

                            </div>

                        </div>

                    </article>


                    <article class="instrumentation-card">
                        <!-- LEFT SIDE -->
                        <div class="instrumentation-info">
                            <div class="instrumentation-header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-radio"
                                    style="color: rgb(198, 40, 40);">
                                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path>
                                    <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path>
                                    <path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path>
                                </svg>
                                <div class="principal-text-black-cards">
                                    <h3>Procesos Térmicos</h3>
                                    <span class="instrumentation-badge alta">ALTA</span>
                                </div>
                            </div>

                            <p class="instrumentation-note">📋 Trazabilidad FDA/HACCP</p>

                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="instrumentation-required">

                            <h4>INSTRUMENTACIÓN REQUERIDA:</h4>

                            <div class="instrumentation-tags">

                                <p class="instrumentation-tag">Termopares múltiples</p>
                                <p class="instrumentation-tag">Controlador PID</p>
                                <p class="instrumentation-tag">Registrador temperatura</p>
                                <p class="instrumentation-tag">Transmisor presión</p>
                                <p class="instrumentation-tag">Válvulas control</p>

                            </div>

                        </div>

                    </article>


                    <article class="instrumentation-card">
                        <!-- LEFT SIDE -->
                        <div class="instrumentation-info">
                            <div class="instrumentation-header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-radio"
                                    style="color: rgb(198, 40, 40);">
                                    <path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"></path>
                                    <path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"></path>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"></path>
                                    <path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"></path>
                                </svg>
                                <div class="principal-text-black-cards">
                                    <h3>Cuarto de Calderas</h3>
                                    <span class="instrumentation-badge media">MEDIA</span>
                                </div>
                            </div>

                            <p class="instrumentation-note">📋 NOM-008-ENER</p>

                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="instrumentation-required">

                            <h4>HVAC Industrial</h4>

                            <div class="instrumentation-tags">

                                <p class="instrumentation-tag">Termostatos</p>
                                <p class="instrumentation-tag">Humidistatos</p>
                                <p class="instrumentation-tag">Presostatos diferenciales</p>
                                <p class="instrumentation-tag">Sensores CO₂</p>
                                <p class="instrumentation-tag">Actuadores modulantes</p>

                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="benefitsSection-servProyectIndus instrumentation">
            <div class="container instrumentation">
                <div class="aplication-text-instrumentation ">
                    <h2>
                        Servicio Técnico Especializado
                    </h2>

                    <p>Más que venta de equipos: solución integral</p>
                </div>

                <div class="benefitsGrid-servProyectIndus">

                    <!-- Benefit Item 1 -->
                    <div class="benefitCard-servProyectIndus instrumentation">
                        <div class="benefitIcon-servProyectIndus instrumentation">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-circle-check-big"
                                style="color: rgb(198, 40, 40);">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                        </div>

                        <div class="benefitContent-servProyectIndus">
                            <h3 class="benefitHeading-servProyectIndus">Precisión Certificada</h3>
                            <p class="benefitText-servProyectIndus">
                                Todos nuestros instrumentos cuentan con certificados de
                                calibración trazables a estándares nacionales e internacionales (CENAM, NIST).
                            </p>
                        </div>
                    </div>

                    <!-- Benefit Item 2 -->
                    <div class="benefitCard-servProyectIndus instrumentation">
                        <div class="benefitIcon-servProyectIndus instrumentation">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-shield" style="color: rgb(198, 40, 40);">
                                <path
                                    d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                </path>
                            </svg>
                        </div>

                        <div class="benefitContent-servProyectIndus">
                            <h3 class="benefitHeading-servProyectIndus">Normatividad Cumplida</h3>
                            <p class="benefitText-servProyectIndus">
                                Instrumentación que cumple NOM-020-STPS (calderas), NOM-001-SEDE (eléctrica) y estándares
                                ASME, ISA, IEC según aplicación.
                            </p>
                        </div>
                    </div>

                    <!-- Benefit Item 3 -->
                    <div class="benefitCard-servProyectIndus instrumentation">
                        <div class="benefitIcon-servProyectIndus instrumentation">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-zap" style="color: rgb(198, 40, 40);">
                                <path
                                    d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                </path>
                            </svg>
                        </div>

                        <div class="benefitContent-servProyectIndus">
                            <h3 class="benefitHeading-servProyectIndus">Instalación Profesional</h3>
                            <p class="benefitText-servProyectIndus">
                                Técnicos especializados en instrumentación industrial. Instalamos, calibramos y validamos
                                in-situ. Capacitación incluida.
                            </p>
                        </div>
                    </div>

                    <!-- Benefit Item 4 -->
                    <div class="benefitCard-servProyectIndus instrumentation">
                        <div class="benefitIcon-servProyectIndus instrumentation">

                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-wrench" style="color: rgb(198, 40, 40);">
                                <path
                                    d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                                </path>
                            </svg>
                        </div>

                        <div class="benefitContent-servProyectIndus">
                            <h3 class="benefitHeading-servProyectIndus">Servicio Post-Venta</h3>
                            <p class="benefitText-servProyectIndus">
                                Calibración periódica, reparación de equipos, stock de refacciones. Contratos de
                                mantenimiento preventivo disponibles.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        {{-- WORLS LEADING BRANDS SECTION --}}
        <section class="world-leading-brands">
            <div class="container instrumentation">
                <div class="wordl-leanding-position">
                    <div class="aplication-text-instrumentation second">
                        <h2>
                            Representamos Marcas Líderes Mundiales
                        </h2>
                    </div>

                    <div class="carts-main-instrumentation leading up">
                        <article class="metric-card-instrumentation calibration">
                            <h3>Winters</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Ashcroft</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Dwyer</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Omega</h3>
                        </article>
                    </div>

                    <div class="carts-main-instrumentation leading down">
                        <article class="metric-card-instrumentation calibration">
                            <h3>Endress+Hauser</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Yokogawa</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Rosemount</h3>
                        </article>

                        <article class="metric-card-instrumentation calibration">
                            <h3>Siemens</h3>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="firstContent-servProyectIndus instrumentation">
            <div class="text-servProyectIndus">
                <div class="aplication-text-instrumentation second">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-gauge mx-auto mb-8">
                        <path d="m12 14 4-4"></path>
                        <path d="M3.34 19a10 10 0 1 1 17.32 0"></path>
                    </svg>
                </div>
                <h2 class="title-servProyectIndus red">
                    ¿Necesitas Asesoría Técnica?
                </h2>

                <p class="description-servProyectIndus second">
                    Nuestros ingenieros te ayudarán a seleccionar la instrumentación correcta según tu proceso, rangos de
                    operación y normatividad aplicable.
                </p>

                {{-- Buttons declaration --}}
                <div class="buttonsContainer-servProyectIndus">

                    <button class="button-primary calibration color-white">
                        Solicitar Visita Tecnica
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button class="button-secondary calibration color-red">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                            </path>
                        </svg>
                        449 434 8018
                    </button>

                </div>
            </div>
        </section>
    </body>
@endsection
