@extends('frontend.layouts.master')

@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/servProyectIndus.css') }}">
@endsection

@section('content')
    <section class="first-content-servCalibation proyectIndus">
        {{-- main text --}}
        <div class="textServCalibration proyectIndus">
            <h1>Proyectos Industriales Integrales Llave en Mano</h1>
            <p>Diseño, suministro, instalación y puesta en marcha de salas de calderas completas, cuartos de máquinas y
                sistemas térmicos industriales con garantía total.
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

    {{-- SECOND --}}
    <section class="second-sectionProyectIndus">
        <h2>¿En qué consisten los Proyectos Industriales Integrales?</h2>
        <p>Desarrollamos proyectos llave en mano que abarcan desde la ingeniería conceptual hasta la capacitación del
            personal operativo, incluyendo diseño de salas de calderas, cálculos de demanda térmica, dimensionamiento de
            equipos, ingeniería de detalle, suministro de maquinaria certificada, construcción de obra civil, instalación
            mecánica, montaje eléctrico, automatización con PLC/SCADA, pruebas de arranque, gestión de permisos y
            capacitación técnica especializada. Aplicamos estándares de gestión PMI, cronogramas Gantt y entregables
            documentados en cada etapa del proyecto.</p>
        <div class="firstcontainer-servProyectIndust">
            <h3>Aplicaciones Industriales:</h3>

            <div class="accordion__content-servProyectIndus">
                <div class="accordion__grid-servProyectIndus">
                    <div>
                        <ul>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Hoteles y desarrollos turísticos
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Lavanderías industriales
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Industria alimentaria y bebidas
                            </li>

                        </ul>
                    </div>

                    <div>
                        <ul>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Hospitales y clínicas privadas
                            </li>
                            <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Plantas manufactureras
                            </li>
                            <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-circle-check-big text-[#C62828] flex-shrink-0 mt-1">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>Procesamiento químico y petroquímico
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </section>

    {{-- THIRD --}}
    <section class="section-servProyectIndus">
        <h2 class="title-servProyectIndus">
            ¿Qué Incluyen Nuestros Proyectos Integrales?
        </h2>
        <div class="grid-servProyectIndus">

            <!-- Card 1 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-building2 lucide-building-2 text-white">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M10 6h4"></path>
                        <path d="M10 10h4"></path>
                        <path d="M10 14h4"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Ingeniería de Detalle</h3>
                <p class="cardText-servProyectIndus">
                    Cálculos térmicos, dimensionamiento de equipos, ingeniería hidráulica, eléctrica,
                    estructural y especificaciones técnicas completas.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-cog text-white">
                        <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
                        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                        <path d="M12 2v2"></path>
                        <path d="M12 22v-2"></path>
                        <path d="m17 20.66-1-1.73"></path>
                        <path d="M11 10.27 7 3.34"></path>
                        <path d="m20.66 17-1.73-1"></path>
                        <path d="m3.34 7 1.73 1"></path>
                        <path d="M14 12h8"></path>
                        <path d="M2 12h2"></path>
                        <path d="m20.66 7-1.73 1"></path>
                        <path d="m3.34 17 1.73-1"></path>
                        <path d="m17 3.34-1 1.73"></path>
                        <path d="m11 13.73-4 6.93"></path>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Suministro de Equipos</h3>
                <p class="cardText-servProyectIndus">
                    Calderas, quemadores, bombas, válvulas, instrumentación, controles y accesorios
                    de marcas certificadas con garantía de fábrica.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-shield text-white">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Construcción y Montaje</h3>
                <p class="cardText-servProyectIndus">
                    Obra civil, estructuras metálicas, instalación mecánica,
                    montaje eléctrico y acabados según normas de seguridad industrial.
                </p>
            </div>

            <!-- Card 4 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-cog text-white">
                        <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"></path>
                        <path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"></path>
                        <path d="M12 2v2"></path>
                        <path d="M12 22v-2"></path>
                        <path d="m17 20.66-1-1.73"></path>
                        <path d="M11 10.27 7 3.34"></path>
                        <path d="m20.66 17-1.73-1"></path>
                        <path d="m3.34 7 1.73 1"></path>
                        <path d="M14 12h8"></path>
                        <path d="M2 12h2"></path>
                        <path d="m20.66 7-1.73 1"></path>
                        <path d="m3.34 17 1.73-1"></path>
                        <path d="m17 3.34-1 1.73"></path>
                        <path d="m11 13.73-4 6.93"></path>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Automatización</h3>
                <p class="cardText-servProyectIndus">
                    Sistemas de control con PLC, pantallas HMI, supervisión SCADA
                    y monitoreo remoto con registro histórico de variables.
                </p>
            </div>

            <!-- Card 5 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                        <polyline points="16 7 22 7 22 13"></polyline>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Puesta en Marcha</h3>
                <p class="cardText-servProyectIndus">
                    Arranque supervisado, pruebas de funcionamiento,
                    calibración de equipos y ajuste de parámetros operativos óptimos.
                </p>
            </div>

            <!-- Card 6 -->
            <div class="card-servProyectIndus">
                <div class="iconBox-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-users text-white">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 class="cardTitle-servProyectIndus">Capacitación</h3>
                <p class="cardText-servProyectIndus">
                    Entrenamiento técnico al personal, manuales de operación,
                    programas de mantenimiento y soporte post-garantía.
                </p>
            </div>

        </div>
    </section>

    {{-- QUARTER --}}
    <section class="benefitsSection-servProyectIndus">

        <h2 class="benefitsTitle-servProyectIndus">
            Beneficios Operativos Comprobados
        </h2>

        <div class="benefitsGrid-servProyectIndus">

            <!-- Benefit Item 1 -->
            <div class="benefitCard-servProyectIndus">
                <div class="benefitIcon-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                        <polyline points="16 7 22 7 22 13"></polyline>
                    </svg>
                </div>
                <div class="benefitContent-servProyectIndus">
                    <h3 class="benefitHeading-servProyectIndus">Único Responsable</h3>
                    <p class="benefitText-servProyectIndus">
                        Llave en mano elimina problemas de coordinación entre múltiples
                        contratistas con un solo interlocutor responsable.
                    </p>
                </div>
            </div>

            <!-- Benefit Item 2 -->
            <div class="benefitCard-servProyectIndus">
                <div class="benefitIcon-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-shield text-white">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                        </path>
                    </svg>
                </div>
                <div class="benefitContent-servProyectIndus">
                    <h3 class="benefitHeading-servProyectIndus">Garantía Total</h3>
                    <p class="benefitText-servProyectIndus">
                        Garantía integral de equipos, instalación y funcionamiento
                        con soporte técnico post-entrega incluido.
                    </p>
                </div>
            </div>

            <!-- Benefit Item 3 -->
            <div class="benefitCard-servProyectIndus">
                <div class="benefitIcon-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-building2 lucide-building-2 text-white">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
                        <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path>
                        <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path>
                        <path d="M10 6h4"></path>
                        <path d="M10 10h4"></path>
                        <path d="M10 14h4"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                </div>
                <div class="benefitContent-servProyectIndus">
                    <h3 class="benefitHeading-servProyectIndus">Cumplimiento de Plazos</h3>
                    <p class="benefitText-servProyectIndus">
                        Gestión profesional de proyectos con cronogramas realistas
                        y entrega en tiempo acordado contractualmente.
                    </p>
                </div>
            </div>

            <!-- Benefit Item 4 -->
            <div class="benefitCard-servProyectIndus">
                <div class="benefitIcon-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="lucide lucide-circle-check-big text-white">
                        <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>
                </div>
                <div class="benefitContent-servProyectIndus">
                    <h3 class="benefitHeading-servProyectIndus">Cumplimiento Normativo</h3>
                    <p class="benefitText-servProyectIndus">
                        Diseños conformes a códigos ASME, NOM-020-STPS y normas
                        ambientales con gestión de permisos incluida.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- PART FIVE D: --}}
    <section class="processSection-servProyectIndus">

        <h2 class="processTitle-servProyectIndus">
            Nuestro Proceso de Trabajo
        </h2>

        <div class="processGrid-servProyectIndus">

            <!-- Step 1 -->
            <div class="processItem-servProyectIndus">
                <div class="processNumber-servProyectIndus">1</div>
                <h3 class="processHeading-servProyectIndus">Ingeniería</h3>
                <p class="processText-servProyectIndus">
                    Levantamiento de campo, cálculos térmicos, diseño de sistemas y
                    elaboración de planos ejecutivos aprobados.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="processItem-servProyectIndus">
                <div class="processNumber-servProyectIndus">2</div>
                <h3 class="processHeading-servProyectIndus">Suministro</h3>
                <p class="processText-servProyectIndus">
                    Adquisición de equipos certificados, fabricación de componentes
                    especiales y logística de entrega coordinada.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="processItem-servProyectIndus">
                <div class="processNumber-servProyectIndus">3</div>
                <h3 class="processHeading-servProyectIndus">Construcción</h3>
                <p class="processText-servProyectIndus">
                    Obra civil, instalación mecánica, montaje eléctrico,
                    automatización y pruebas de funcionamiento integral.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="processItem-servProyectIndus">
                <div class="processNumber-servProyectIndus">4</div>
                <h3 class="processHeading-servProyectIndus">Entrega</h3>
                <p class="processText-servProyectIndus">
                    Puesta en marcha, capacitación al personal, entrega de manuales
                    y inicio de garantía total del proyecto.
                </p>
            </div>
        </div>
    </section>
    {{-- PART SIX --}}
    {{-- PART SIX --}}
    <section class="whyChoose-servProyectIndus">

        <h2 class="whyChoose-title-servProyectIndus">
            Por qué elegir a SIMARI CALDERAS
        </h2>

        <div class="whyChoose-container-servProyectIndus">

            {{-- STEP 1 --}}
            <article class="whyChoose-item-servProyectIndus">
                <div class="super-especial-text-red">30+</div>
                <h3 class="whyChoose-heading-servProyectIndus">Años de Experiencia</h3>
                <p class="whyChoose-text-servProyectIndus">
                    Trayectoria comprobada en desarrollo de proyectos industriales llave en mano de alta complejidad
                    técnica.
                </p>
            </article>

            {{-- STEP 2 --}}
            <article class="whyChoose-item-servProyectIndus">
                <div class="super-especial-text-red">100+</div>
                <h3 class="whyChoose-heading-servProyectIndus">Proyectos Ejecutados</h3>
                <p class="whyChoose-text-servProyectIndus">
                    Salas de calderas, cuartos de máquinas y sistemas térmicos instalados en México y Latinoamérica.
                </p>
            </article>

            {{-- STEP 3 --}}
            <article class="whyChoose-item-servProyectIndus">
                <div class="super-especial-text-red">100%</div>
                <h3 class="whyChoose-heading-servProyectIndus">Satisfacción Cliente</h3>
                <p class="whyChoose-text-servProyectIndus">
                    Proyectos entregados en tiempo, dentro de presupuesto y con garantía total de funcionamiento operativo.
                </p>
            </article>
        </div>
    </section>
    {{-- SEVEN PART --}}
    <section class="firstContent-servProyectIndus">
        <div class="text-servProyectIndus">

            <h2 class="title-servProyectIndus">
                Desarrolle su Proyecto Industrial con Expertos
            </h2>

            <p class="description-servProyectIndus">
                Un proyecto mal ejecutado genera sobrecostos permanentes y problemas operativos crónicos. Confíe en
                especialistas que entregan proyectos llave en mano para hoteles, hospitales, lavanderías y plantas
                industriales con garantía total.
            </p>

            {{-- Buttons declaration --}}
            <div class="buttonsContainer-servProyectIndus">

                <button class="button-primary">
                    Solicitar Propuesta
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none">
                        <path d="M4.99866 11.9966H18.9948" stroke="white" stroke-width="1.99945" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M11.9967 4.99854L18.9948 11.9966L11.9967 18.9947" stroke="white" stroke-width="1.99945"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>

                <button class="button-primary secundary buttonSecondary-servProyectIndus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
                    Llamar Ahora
                </button>

            </div>
        </div>
    </section>
@endsection
