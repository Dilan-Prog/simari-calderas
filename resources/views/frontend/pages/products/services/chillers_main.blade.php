@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/chillers_main.css') }}">
@endsection
@section('content')
    <section class="section-preventive">
        <div class="container">
            <h1 class="preventive-title">Mantenimiento Especializado de Chillers Industriales</h1>
            <p>Servicio técnico preventivo y correctivo para enfriadores de agua industriales, chillers de expansión directa y sistemas 
                HVAC críticos con garantía de continuidad operativa.</p>
            <div>
                <button class="button-primary">
                    Solicitar cotizacion
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>
    </section>
    <section class="section-information">
        <h2>¿En qué consiste el servicio de Mantenimiento de Chillers?</h2>
        <p>El mantenimiento especializado de chillers industriales garantiza eficiencia energética, 
            confiabilidad operativa y prolongación de vida útil mediante inspecciones programadas de compresores, 
            evaporadores, condensadores, sistemas de control, circuitos refrigerantes y torres de enfriamiento. 
            Implementamos protocolos técnicos que incluyen análisis de aceite lubricante, detección de fugas de refrigerante 
            con equipos electrónicos, medición de presiones de succión y descarga, verificación de aislamiento eléctrico de 
            motores y limpieza química de intercambiadores con productos biodegradables certificados.</p>
        <div class = "title-list-information">
            <h3>Aplicaciones Industriales</h3>

            
            <ul class = "list-information">
                <li>Hoteles y complejos de hospitalidad</li>
                <li>Hoteles y complejos de hospitalidad</li>
                <li>Centros comerciales y oficinas</li>
                <li>Industria plástica y farmacéutica</li>
                <li>Data centers y cuartos de servidores</li>
                <li>Plantas de proceso con enfriamiento crítico</li>
                
            </ul>
        </div>
    </section>
    <section class="section-includes-information">
        <h2>¿Qué Incluye el Servicio de Mantenimiento de Chillers?</h2>
        <div class = "information-divs">
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-snowflake text-white"><path d="m10 20-1.25-2.5L6 18"></path><path d="M10 4 8.75 6.5 6 6">
                        </path><path d="m14 20 1.25-2.5L18 18"></path><path d="m14 4 1.25 2.5L18 6"></path><path d="m17 21-3-6h-4">
                            </path><path d="m17 3-3 6 1.5 3"></path><path d="M2 12h6.5L10 9"></path><path d="m20 10-1.5 2 1.5 2"></path>
                            <path d="M22 12h-6.5L14 15"></path><path d="m4 10 1.5 2L4 14"></path><path d="m7 21 3-6-1.5-3"></path><path 
                            d="m7 3 3 6h4"></path></svg></div>
                <h3>Inspección de Compresores</h3>
                <p>Verificación de presiones, temperatura de aceite, aislamiento eléctrico, rodamientos 
                    y vibraciones con equipos especializados.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide 
                    lucide-wind text-white"><path d="M12.8 19.6A2 2 0 1 0 14 16H2"></path><path d="M17.5 8a2.5 2.5 0 1 1 2 4H2"></path>
                    <path d="M9.8 4.4A2 2 0 1 1 11 8H2"></path></svg></div>
                <h3>Limpieza de Intercambiadores</h3>
                <p>Limpieza química de evaporadores y condensadores, 
                    eliminación de incrustaciones calcáreas y lodos biológicos.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-shield text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 
                    20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 
                    0 1 1 1z"></path></svg></div>
                <h3>Detección de Fugas</h3>
                <p>Búsqueda electrónica de fugas de refrigerante, reparación de sellos, carga de gas y vacío con bomba certificada.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    ="lucide lucide-thermometer text-white"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path></svg>
                </div>
                <h3>Análisis de Refrigerante</h3>
                <p>Medición de sobrecalentamiento, subenfriamiento, análisis de aceite y 
                    verificación de niveles de carga óptimos.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-trending-up text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                    <polyline points="16 7 22 7 22 13"></polyline></svg></div>
                <h3>Calibración de Controles</h3>
                <p>Ajuste de termostatos, presostatos, válvulas de expansión, 
                    controles de capacidad y sistemas de deshielo.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                    <path d="m9 11 3 3L22 4"></path></svg></div>
                <h3>Reporte Técnico</h3>
                <p>Informe detallado con parámetros operativos, evidencias fotográficas, 
                    recomendaciones y programa preventivo.</p>
            </div>
        </div>
    </section>
    <section class="section-benefits-operations">
        <h2>Beneficios Operativos Comprobados</h2>
        <div class = "benefits-div">
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-trending-up text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                    <polyline points="16 7 22 7 22 13"></polyline></svg></div>
                <h3>Ahorro Energético del 20-30%</h3>
                <p>
                    Intercambiadores limpios y presiones 
                    óptimas reducen consumo eléctrico del compresor significativamente.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-shield text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 
                    4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                <h3>Prevención de Fallas Críticas</h3>
                <p>
                    Detección temprana de fugas, desgastes y 
                    anomalías evita paros no programados costosos en temporada alta.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide 
                    lucide-snowflake text-white"><path d="m10 20-1.25-2.5L6 18"></path><path d="M10 4 8.75 6.5 6 6"></path><path 
                    d="m14 20 1.25-2.5L18 18"></path><path d="m14 4 1.25 2.5L18 6"></path><path d="m17 21-3-6h-4"></path><path 
                    d="m17 3-3 6 1.5 3"></path><path d="M2 12h6.5L10 9"></path><path d="m20 10-1.5 2 1.5 2"></path><path d="M22 12h-6.5L14 15">
                        </path><path d="m4 10 1.5 2L4 14"></path><path d="m7 21 3-6-1.5-3"></path><path d="m7 3 3 6h4"></path></svg></div>
                <h3>Extensión de Vida Útil +50%</h3>
                <p>
                    Mantenimiento preventivo duplica la vida operativa de compresores, evaporadores y componentes electrónicos.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide 
                    lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path>
                </svg></div>
                <h3>Cumplimiento Ambiental</h3>
                <p>
                    Control de fugas de refrigerante cumple con Protocolo de Montreal y 
                    normativas ambientales vigentes.Cumplimiento Ambiental
                </p>
            </div>
        </div>
    </section>
    <section class="section-we-work">

        <h2>Nuestro Proceso de Trabajo</h2>
        <div class = "work-div">
            <div>
                <div class = "icon"><span>1</span></div>
                <h3>Diagnóstico Completo</h3>
                <p>
                    Inspección visual, medición de parámetros operativos, detección de fugas y evaluación del estado general del sistema.
                </p>
            </div>
            <div>
                <div class = "icon"><span>2</span></div>
                <h3>Intervención Técnica</h3>
                <p>
                    Limpieza de intercambiadores, lubricación de componentes, ajuste de controles y reemplazo de refacciones críticas.
                </p>
            </div>
            <div>
                <div class = "icon"><span>3</span></div>
                <h3>Pruebas de Operación</h3>
                <p>
                    Verificación de presiones, temperaturas, amperajes, análisis de eficiencia y pruebas de seguridad certificadas.
                </p>
            </div>
            <div>
                <div class = "icon"><span>4</span></div>
                <h3>Entrega de Informe</h3>
                <p>
                    Documentación fotográfica, registro de hallazgos, 
                    recomendaciones técnicas y programa de mantenimiento preventivo.
                </p>
            </div>
        </div>
    </section>
    <section class="section-why-choose">
        <h2>Por qué elegir a SIMARI CALDERAS</h2>
        <div class = "choose-div">
            <div>
                <div> 30+</div>
                <h3>Años de Experiencia</h3>
                <p>
                    Especialistas certificados en refrigeración industrial y aire acondicionado comercial de gran capacidad.
                </p>
            </div>
            <div>
                <div>24/7</div>
                <h3>Servicio de Emergencia</h3>
                <p>
                    Disponibilidad permanente para fallas críticas, atención inmediata y tiempos de respuesta garantizados.
                </p>
            </div>
            <div>
                <div>300+</div>
                <h3>Chillers Mantenidos</h3>
                <p>
                    Sistemas de enfriamiento industrial operando con nuestros programas de mantenimiento preventivo especializado.
                </p>
            </div>
        </div>
    </section>
    <section class="section-protect-investment">
        <h2>Proteja su Sistema de Enfriamiento Industrial</h2>
        <p>Un chiller sin mantenimiento consume hasta 40% más energía y tiene 3 veces más probabilidad de fallas críticas. 
            Implemente un programa preventivo profesional que garantice confort y continuidad operativa en hoteles, hospitales, 
            oficinas y plantas industriales.</p>
        <div class="buttons">
            <button class="request">Solicitar Cotización Gratuita<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg></button>
            <button class="call"> Llamar Ahora</button>
        </div>
    </section>
@endsection
