@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/automation.css') }}">
@endsection
@section('content')
    <div class="automation-page">

        <section class="auto-hero">
            <div class="auto-overlay"></div>
            <div class="container auto-hero-content">
                <h1 class="auto-title-main">Automatización de Sistemas Térmicos Industriales</h1>
                <p class="auto-subtitle">Modernización inteligente con PLC, HMI y SCADA para control remoto total</p>
                <a href="/contacto" class="btn-primary auto-btn-hero">
                    Solicitar Cotización
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </section>

        <section class="auto-section bg-white">
            <div class="container">
                <div class="auto-grid-consiste">
                    <div class="auto-dark-card">
                        <h2 class="auto-title-section text-white">¿En qué consiste la Automatización?</h2>
                        <p class="auto-text-large mb-15">Automatización industrial mediante PLC, HMI y sistemas SCADA
                            permite supervisión remota en tiempo real, registro histórico de variables críticas y
                            optimización de ciclos de operación.</p>
                        <p class="auto-text-normal text-gray">Implementamos arquitecturas de control con redundancia,
                            protecciones programadas y conectividad IoT para monitoreo desde dispositivos móviles.</p>
                    </div>
                    <div class="auto-list-items">
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Calderas industriales</span></div>
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Salas múltiples</span></div>
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Plantas de vapor</span></div>
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Distribución térmica</span></div>
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Torres de enfriamiento</span></div>
                        <div class="auto-list-item"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg><span>Cuartos de máquinas</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="auto-section bg-light-gray">
            <div class="container">
                <h2 class="auto-title-section text-center text-dark mb-40">¿Qué Incluye la Automatización?</h2>
                <div class="auto-grid-incluye">
                    <div class="auto-card-red span-row-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="mb-20">
                            <rect width="16" height="16" x="4" y="4" rx="2"></rect>
                            <rect width="6" height="6" x="9" y="9" rx="1"></rect>
                            <path d="M15 2v2"></path>
                            <path d="M15 20v2"></path>
                            <path d="M2 15h2"></path>
                            <path d="M2 9h2"></path>
                            <path d="M20 15h2"></path>
                            <path d="M20 9h2"></path>
                            <path d="M9 2v2"></path>
                            <path d="M9 20v2"></path>
                        </svg>
                        <h3>Control con PLC Industrial</h3>
                        <p>Programación de lógicas de control con PLC Siemens, Allen-Bradley o Schneider con redundancia y
                            respaldo de energía.</p>
                        <ul class="auto-check-list">
                            <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg> Lógicas programadas personalizadas</li>
                            <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg> Respaldo de energía UPS</li>
                            <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg> Redundancia de controladores</li>
                        </ul>
                    </div>
                    <div class="auto-card-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="text-red mb-15">
                            <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                            <line x1="8" x2="16" y1="21" y2="21"></line>
                            <line x1="12" x2="12" y1="17" y2="21"></line>
                        </svg>
                        <h3 class="text-dark">Pantalla HMI Táctil</h3>
                        <p class="text-gray">Interfaz gráfica intuitiva con sinópticos animados, tendencias en tiempo real
                            y registro de eventos históricos.</p>
                    </div>
                    <div class="auto-card-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="text-red mb-15">
                            <path
                                d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                            </path>
                        </svg>
                        <h3 class="text-dark">Protecciones Programadas</h3>
                        <p class="text-gray">Enclavamientos de seguridad, cortes automáticos y fallas de flama
                            programables.</p>
                    </div>
                    <div class="auto-card-dark span-col-2">
                        <div class="auto-grid-3">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="mb-15">
                                    <path
                                        d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z">
                                    </path>
                                </svg>
                                <h3>Monitoreo Remoto</h3>
                                <p class="text-gray-light">Acceso web y app móvil con alertas inteligentes por SMS.</p>
                            </div>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="mb-15">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                    <polyline points="16 7 22 7 22 13"></polyline>
                                </svg>
                                <h3>Registro Histórico</h3>
                                <p class="text-gray-light">Base de datos de parámetros y consumos energéticos.</p>
                            </div>
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="mb-15">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <h3>Capacitación</h3>
                                <p class="text-gray-light">Entrenamiento técnico y soporte remoto permanente.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="auto-section bg-dark">
            <div class="container">
                <h2 class="auto-title-section text-center text-white mb-40">Beneficios Operativos Garantizados</h2>
                <div class="auto-grid-2">
                    <div class="auto-glass-card">
                        <div class="auto-glass-number">25%</div>
                        <h3>Ahorro Energético</h3>
                        <p>Optimización automática de encendidos según demanda real.</p>
                    </div>
                    <div class="auto-glass-card">
                        <div class="auto-glass-number">80%</div>
                        <h3>Reducción de Fallas</h3>
                        <p>Diagnóstico predictivo previene fallas catastróficas 24/7.</p>
                    </div>
                    <div class="auto-glass-card">
                        <div class="auto-glass-number">100%</div>
                        <h3>Control Remoto Total</h3>
                        <p>Supervisión desde dispositivos móviles sin presencia física.</p>
                    </div>
                    <div class="auto-glass-card">
                        <div class="auto-glass-number">∞</div>
                        <h3>Trazabilidad Documental</h3>
                        <p>Registros automáticos para ISO 9001 y auditorías.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="auto-section bg-white">
            <div class="container">
                <h2 class="auto-title-section text-center text-dark mb-40">Proceso de Implementación</h2>
                <div class="auto-grid-4">
                    <div class="auto-process-card has-arrow">
                        <div class="auto-process-step">01</div>
                        <h3>Análisis</h3>
                        <p>Levantamiento de variables y definición de funcionalidades.</p>
                    </div>
                    <div class="auto-process-card has-arrow">
                        <div class="auto-process-step">02</div>
                        <h3>Programación</h3>
                        <p>Desarrollo de lógicas y diseño de interfaces HMI.</p>
                    </div>
                    <div class="auto-process-card has-arrow">
                        <div class="auto-process-step">03</div>
                        <h3>Instalación</h3>
                        <p>Montaje de equipos, cableado y configuración de red.</p>
                    </div>
                    <div class="auto-process-card">
                        <div class="auto-process-step">04</div>
                        <h3>Puesta en Marcha</h3>
                        <p>Arranque supervisado y capacitación del personal.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="auto-section bg-dark auto-cta-section">
            <div class="container">
                <div class="auto-stats-grid mb-40">
                    <div class="auto-stat-box">
                        <div class="auto-stat-number">30+</div>
                        <h3>Años Experiencia</h3>
                    </div>
                    <div class="auto-stat-box">
                        <div class="auto-stat-number">150+</div>
                        <h3>Proyectos Automatizados</h3>
                    </div>
                    <div class="auto-stat-box">
                        <div class="auto-stat-number">24/7</div>
                        <h3>Soporte Técnico</h3>
                    </div>
                </div>

                <div class="auto-cta-content text-center">
                    <h2 class="auto-title-section text-white mb-20">Modernize sus Sistemas Térmicos</h2>
                    <p class="auto-text-large text-gray-light mb-40">La operación manual genera ineficiencias y falta de
                        trazabilidad. Implemente automatización industrial que optimice procesos con control remoto total.
                    </p>

                    <div class="auto-btn-group">
                        <a href="/contacto" class="btn-primary">
                            Solicitar Cotización
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                        <a href="tel:+524494348018" class="btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg>
                            Llamar Ahora
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection
