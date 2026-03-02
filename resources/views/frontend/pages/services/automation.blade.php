@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/automation.css') }}">
@endsection
@section('content')
    <section class="hero-section">
        <div class="hero-image-overlay">
            <img src="https://images.unsplash.com/photo-1763296479464-fe8bee23eb65?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGNvbnRyb2wlMjBwYW5lbHxlbnwxfHx8fDE3NzIxNjA1ODZ8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="Automatización">
        </div>

        <div class="hero-content">
            <h1 class="hero-title">Automatización de Sistemas Térmicos Industriales</h1>
            <p class="hero-subtitle">Modernización inteligente con PLC, HMI y SCADA para control remoto total</p>

            <button class="button-primary" onclick="window.location.href='/contacto'">
                Solicitar Cotización
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </section>
    <section class="automation-info-section">
        <div class="automation-container">
            <div class="automation-grid">

                <div class="info-card">
                    <h2 class="info-title">¿En qué consiste la Automatización?</h2>
                    <p class="info-description">Automatización industrial mediante PLC, HMI y sistemas SCADA permite supervisión remota en tiempo real, registro histórico de variables críticas y optimización de ciclos de operación.</p>
                    <p class="info-subdescription">Implementamos arquitecturas de control con redundancia, protecciones programadas y conectividad IoT para monitoreo desde dispositivos móviles.</p>
                </div>

                <div class="features-list">
                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Calderas industriales</span>
                    </div>

                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Salas múltiples</span>
                    </div>

                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Plantas de vapor</span>
                    </div>

                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Distribución térmica</span>
                    </div>

                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Torres de enfriamiento</span>
                    </div>

                    <div class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                            <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                            <path d="m9 11 3 3L22 4"></path>
                        </svg>
                        <span>Cuartos de máquinas</span>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <section class="includes-section">
        <div class="includes-container">
            <h2 class="section-title-dark">¿Qué Incluye la Automatización?</h2>



            <div class="includes-grid">

                <div class="card-plc">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu card-icon-large">
                        <rect width="16" height="16" x="4" y="4" rx="2"></rect>
                        <rect width="6" height="6" x="9" y="9" rx="1"></rect>
                        <path d="M15 2v2"></path><path d="M15 20v2"></path>
                        <path d="M2 15h2"></path><path d="M2 9h2"></path>
                        <path d="M20 15h2"></path><path d="M20 9h2"></path>
                        <path d="M9 2v2"></path><path d="M9 20v2"></path>
                    </svg>
                    <h3 class="card-title-large">Control con PLC Industrial</h3>
                    <p class="card-text-large">Programación de lógicas de control con PLC Siemens, Allen-Bradley o Schneider con redundancia y respaldo de energía.</p>

                    <ul class="plc-list">
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path>
                            </svg>
                            <span>Lógicas programadas personalizadas</span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path>
                            </svg>
                            <span>Respaldo de energía UPS</span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path>
                            </svg>
                            <span>Redundancia de controladores</span>
                        </li>
                    </ul>
                </div>

                <div class="card-basic">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor card-icon-red">
                        <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                        <line x1="8" x2="16" y1="21" y2="21"></line>
                        <line x1="12" x2="12" y1="17" y2="21"></line>
                    </svg>
                    <h3 class="card-title-dark">Pantalla HMI Táctil</h3>
                    <p class="card-text-gray">Interfaz gráfica intuitiva con sinópticos animados, tendencias en tiempo real y registro de eventos históricos.</p>
                </div>

                <div class="card-basic">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield card-icon-red">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                    </svg>
                    <h3 class="card-title-dark">Protecciones Programadas</h3>
                    <p class="card-text-gray">Enclavamientos de seguridad, cortes automáticos y fallas de flama programables.</p>
                </div>

                <div class="card-bottom">
                    <div class="bottom-grid">
                        <div class="bottom-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap card-icon-white">
                                <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                            </svg>
                            <h3 class="bottom-title">Monitoreo Remoto</h3>
                            <p class="bottom-text">Acceso web y app móvil con alertas inteligentes por SMS.</p>
                        </div>
                        <div class="bottom-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up card-icon-white">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                            <h3 class="bottom-title">Registro Histórico</h3>
                            <p class="bottom-text">Base de datos de parámetros y consumos energéticos.</p>
                        </div>
                        <div class="bottom-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users card-icon-white">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <h3 class="bottom-title">Capacitación</h3>
                            <p class="bottom-text">Entrenamiento técnico y soporte remoto permanente.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <section class="benefits-section">
        <div class="benefits-container">
            <h2 class="benefits-title">Beneficios Operativos Garantizados</h2>

            <div class="benefits-grid">

                <div class="benefit-card">
                    <div class="benefit-number">25%</div>
                    <h3 class="benefit-heading">Ahorro Energético</h3>
                    <p class="benefit-text">Optimización automática de encendidos según demanda real.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-number">80%</div>
                    <h3 class="benefit-heading">Reducción de Fallas</h3>
                    <p class="benefit-text">Diagnóstico predictivo previene fallas catastróficas 24/7.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-number">100%</div>
                    <h3 class="benefit-heading">Control Remoto Total</h3>
                    <p class="benefit-text">Supervisión desde dispositivos móviles sin presencia física.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-number">∞</div>
                    <h3 class="benefit-heading">Trazabilidad Documental</h3>
                    <p class="benefit-text">Registros automáticos para ISO 9001 y auditorías.</p>
                </div>

            </div>
        </div>
    </section>
    <section class="process-section">
        <div class="process-container">
            <h2 class="process-main-title">Proceso de Implementación</h2>

            <div class="process-grid">

                <div class="process-step">
                    <div class="process-card">
                        <div class="step-number">01</div>
                        <h3 class="step-title">Análisis</h3>
                        <p class="step-text">Levantamiento de variables y definición de funcionalidades.</p>
                    </div>
                    <div class="step-arrow"></div>
                </div>

                <div class="process-step">
                    <div class="process-card">
                        <div class="step-number">02</div>
                        <h3 class="step-title">Programación</h3>
                        <p class="step-text">Desarrollo de lógicas y diseño de interfaces HMI.</p>
                    </div>
                    <div class="step-arrow"></div>
                </div>

                <div class="process-step">
                    <div class="process-card">
                        <div class="step-number">03</div>
                        <h3 class="step-title">Instalación</h3>
                        <p class="step-text">Montaje de equipos, cableado y configuración de red.</p>
                    </div>
                    <div class="step-arrow"></div>
                </div>

                <div class="process-step">
                    <div class="process-card">
                        <div class="step-number">04</div>
                        <h3 class="step-title">Puesta en Marcha</h3>
                        <p class="step-text">Arranque supervisado y capacitación del personal.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <section class="stats-cta-section">
        <div class="stats-cta-container">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">30+</div>
                    <h3 class="stat-title">Años Experiencia</h3>
                </div>

                <div class="stat-card">
                    <div class="stat-number">150+</div>
                    <h3 class="stat-title">Proyectos Automatizados</h3>
                </div>

                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <h3 class="stat-title">Soporte Técnico</h3>
                </div>
            </div>

            <div class="cta-content">
                <h2 class="cta-title">Modernice sus Sistemas Térmicos</h2>
                <p class="cta-description">La operación manual genera ineficiencias y falta de trazabilidad. Implemente automatización industrial que optimice procesos con control remoto total.</p>

                <div class="cta-buttons">
                    <button class="button-primary boton-especial" onclick="window.location.href='/contacto'">
                        Solicitar Cotización
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>

                    <button class="button-secondary" onclick="window.location.href='tel:+524494348018'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        Llamar Ahora
                    </button>
                </div>
            </div>

        </div>
    </section>
@endsection
