@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/preventive_main.css') }}">
@endsection
@section('content')
    <section class="section-preventive">
        <div class="container">
            <h1 class="preventive-title">Mantenimiento Idustrial Preventivo y Correctivo</h1>
            <p>Maximice la eficiencia operativa y reduzca paros no programados con nuestro servicio técnico especializado en calderas, 
                generadores de vapor y sistemas térmicos industriales.</p>
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
        <h2>¿En qué consiste el servicio de Mantenimiento Industrial?</h2>
        <p>Nuestro servicio de mantenimiento industrial especializado garantiza 
            la continuidad operativa de sus sistemas térmicos mediante intervenciones preventivas
            programadas y correctivas de emergencia. Aplicamos protocolos técnicos certificados que 
            aseguran el cumplimiento normativo, la seguridad del personal y la optimización del consumo 
            energético en calderas pirotubulares, acuotubulares, generadores de vapor e intercambiadores 
            de calor.</p>
        <div class = "title-list-information">
            <h3>Aplicaciones Industriales</h3>

            
            <ul class = "list-information">
                <li>Hoteles y complejos hoteleros</li>
                <li>Hospitales y centros de salud</li>
                <li>Lavanderías industriales</li>
                <li>Plantas manufactureras</li>
                <li>Procesamiento químico</li>
                <li>Industria alimentaria</li>
                
            </ul>
        </div>
    </section>
    <section class="section-includes-information">
        <h2>¿Qué Incluye el Servicio de Mantenimiento?</h2>
        <div class = "information-divs">
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" 
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench text-white">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 
                6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                <h3>Inspección Técnica Completa</h3>
                <p>Evaluación exhaustiva de componentes críticos: 
                    tubos, quemadores, controles de nivel, manómetros, 
                    válvulas de seguridad y sistemas de combustión.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-clock text-white"><circle cx="12" cy="12" r="10"></circle><polyline 
                    points="12 6 12 12 16 14"></polyline></svg></div>
                <i class="icono"></i>
                <h3>Lubricación y Ajustes</h3>
                <p>Lubricación de rodamientos, ajuste de tensión en correas, 
                    calibración de presostatos y verificación de alineación de 
                    bombas centrífugas.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" 
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="lucide lucide-shield text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 
                    20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 
                    0 1 1 1z"></path></svg></div>
                <h3>Análisis de Combustión</h3>
                <p>Medición de eficiencia de combustión, análisis de gases, ajuste 
                    de relación aire-combustible y optimización de rendimiento térmico.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up 
                    text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline>
                </svg></div>
                <h3>Limpieza de Componentes</h3>
                <p>Limpieza de superficies de transferencia de calor, eliminación 
                    de hollín, inspección de refractarios y limpieza de filtros 
                    de combustible.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide 
                    lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4">
                    </path></svg></div>
                <h3>Reemplazo de Refacciones</h3>
                <p>Sustitución de empaques, juntas, electrodos de ignición, 
                    fotoceldas y componentes desgastados con refacciones originales 
                    certificadas.</p>
            </div>
            <div>
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users 
                    text-white"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path 
                    d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                <h3>Informe Técnico Detallado</h3>
                <p>Documentación fotográfica, reporte de hallazgos, recomendaciones 
                    técnicas y registro de parámetros operativos para trazabilidad.</p>
            </div>
        </div>
    </section>
    <section class="section-benefits-operations">
        <h2>Beneficios Operativos Comprobados</h2>
        <div class = "benefits-div">
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up 
                    text-white"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
                </div>
                <h3>Ahorro Energético del 15-25%</h3>
                <p>
                    Reducción significativa en consumo 
                    de combustible mediante optimización de combustión y 
                    limpieza regular de superficies de intercambio térmico.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield 
                    text-white"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 
                    6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></div>
                <h3>Reducción de Fallas del 70%</h3>
                <p>
                    Disminución drástica de paros no programados gracias 
                    a la detección temprana de anomalías y reemplazo preventivo 
                    de componentes críticos.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock 
                    text-white"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                <h3>Extensión de Vida Útil +40%</h3>
                <p>
                    Prolongación significativa de la vida operativa del equipo 
                    mediante mantenimiento predictivo y uso de refacciones originales 
                    certificadas.
                </p>
            </div>
            <div class = "benefits-div-person">
                <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide 
                    lucide-circle-check-big text-white"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4">
                    </path></svg></div>
                <h3>Cumplimiento Normativo 100%</h3>
                <p>
                    Garantía de cumplimiento con NOM-020-STPS, normas ambientales 
                    y certificaciones de seguridad industrial requeridas por autoridades.
                </p>
            </div>
        </div>
    </section>
    <section class="section-we-work">

        <h2>Nuestro Proceso de Trabajo</h2>
        <div class = "work-div">
            <div>
                <div class = "icon"><span>1</span></div>
                <h3>Diagnóstico Inicial</h3>
                <p>
                    Inspección visual, pruebas de funcionamiento, 
                    medición de parámetros y evaluación del estado general 
                    del sistema térmico.
                </p>
            </div>
            <div>
                <div class = "icon"><span>2</span></div>
                <h3>Intervención Técnica</h3>
                <p>
                    Ejecución de actividades de mantenimiento preventivo o 
                    correctivo según protocolo técnico y plan de trabajo autorizado.
                </p>
            </div>
            <div>
                <div class = "icon"><span>3</span></div>
                <h3>Pruebas de Validación</h3>
                <p>
                    Verificación de presiones, temperaturas, análisis de 
                    combustión y pruebas de seguridad para garantizar operación 
                    óptima.
                </p>
            </div>
            <div>
                <div class = "icon"><span>4</span></div>
                <h3>Entrega de Informe</h3>
                <p>
                    Documentación completa de trabajos realizados, evidencias
                    fotográficas, recomendaciones y programa de seguimiento.
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
                    Tres décadas de trayectoria en ingeniería térmica industrial 
                    y mantenimiento especializado de calderas.
                </p>
            </div>
            <div>
                <div>24/7</div>
                <h3>Soporte Técnico</h3>
                <p>
                    Disponibilidad permanente para emergencias, asistencia remota 
                    y atención inmediata a fallas críticas.
                </p>
            </div>
            <div>
                <div>500+</div>
                <h3>Clientes Industriales</h3>
                <p>
                    Confianza de empresas líderes en hotelería, salud, manufactura 
                    y servicios industriales de alto desempeño.
                </p>
            </div>
        </div>
    </section>
    <section class="section-protect-investment">
        <h2>Proteja su Inversión Industrial</h2>
        <p>No espere a que una falla crítica paralice sus operaciones. Nuestros técnicos 
            especializados están listos para implementar un programa de mantenimiento que 
            garantice la continuidad de sus procesos productivos en hoteles, hospitales, 
            fábricas y lavanderías industriales.</p>
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
