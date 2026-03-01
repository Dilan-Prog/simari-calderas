@extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/services.css') }}">
@endsection
@section('content')
    <section class="encabezado">
        <div class="container">



            <h1 class="title-page">Desincrustación Química <br> de Calderas Industriales</h1>



            <p class="info"> Eliminación profesional de incrustaciones calcáreas, óxidos metálicos y depósitos que
                reducen eficiencia térmica y comprometen la seguridad operacional de calderas. </p>

            <div class=" container-buton-1">
                <button class="container-buton-primary">Solicitar Cotizacion</button>
            </div>


        </div>

    </section>



    <section class="section2">
         <div class="container">

            <h2 class="title2">¿En qué consiste el servicio de Desincrustación?</h2>

            <p class="description">La desincrustación química profesional mediante circulación de soluciones ácidas
                inhibidas remueve incrustaciones de carbonato de calcio, sulfato de calcio,
                óxidos de hierro, sílice y depósitos minerales que actúan como aislantes térmicos,
                reduciendo la transferencia de calor hasta 30% y generando sobrecalentamiento
                localizado en tubos que causa fallas catastróficas. Aplicamos protocolos
                técnicos con productos biodegradables, inhibidores de corrosión certificados,
                control de pH, temperatura y tiempos de contacto optimizados para remoción
                completa sin dañar el metal base.
            </p>

            <div class="lista">

                <h2 class="title1">Aplicaciones Industriales:</h2>
                <ul>

                    <li><span>Calderas pirotubulares con incrustación severa</span></li>
                    <li><span>Calderas acuotubulares de alta presión</span></li>
                    <li><span>Intercambiadores de calor tubulares</span> </li>
                    <li><span>Generadores de vapor industriales</span> </li>
                    <li><span>Torres de enfriamiento calcificadas</span> </li>
                    <li><span>Sistemas de agua caliente obstruidos</span> </li>

                </ul>
            </div>


        </div>

    </section>

    <section class="section3">


        <div class="container">

            <h2 class="title3">¿Qué Incluye el Servicio de Desincrustación?</h2>




            <div class="contenedor3">

                <ul>

                    <div class="recuadros" background="#C62828">

                        <div>
                            <div class="imagen">
                                <div class="w-14 h-14 bg-[#C62828] rounded-lg flex items-center justify-center mb-4">

                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                        viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-triangle-alert text-white">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3">
                                        </path>
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                                    </svg>
                                </div>
                            </div>

                            <h3>Inspección Previa</h3>

                            <p>Evaluación endoscópica de nivel de incrustación,
                                pruebas de adherencia, análisis químico de depósitos
                                y diseño del protocolo de limpieza.</p>

                        </div>
                    </div>

                    <div class="recuadros">

                        <div>
                            <div class="imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-droplets text-white">
                                    <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29
                            3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"></path>
                                    <path
                                        d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97">
                                    </path>
                                </svg>
                            </div>

                            <h3>Limpieza Química</h3>

                            <p>Circulación de soluciones desincrustantes con
                                inhibidores de corrosión, control de pH, temperatura
                                y monitoreo de disolución.</p>

                        </div>
                    </div>

                    <div class="recuadros">

                        <div>
                            <div class="imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-shield text-white">
                                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4
                            13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81
                            17 5 19 5a1 1 0 0 1 1 1z"></path>
                                </svg>
                            </div>

                            <h3>Neutralización</h3>

                            <p>Enjuague con agua desmineralizada, neutralización
                                de ácidos residuales y pasivación de superficies metálicas limpias.</p>

                        </div>
                    </div>

                    <div class="recuadros">

                        <div>
                            <div class="imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-sparkles text-white">
                                    <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5
                              9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0
                               0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437
                               1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                                    <path d="M20 3v4">
                                    </path>
                                    <path d="M22 5h-4"></path>
                                    <path d="M4 17v2"></path>
                                    <path d="M5 18H3"></path>
                                </svg>
                            </div>

                            <h3>Pruebas de Limpieza</h3>

                            <p>Inspección endoscópica final, medición de rugosidad,
                                análisis de agua de enjuague y certificación de limpieza completa.</p>

                        </div>
                    </div>

                    <div class="recuadros">



                        <div>
                            <div class="imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                    <polyline points="16 7 22 7 22 13"></polyline>
                                </svg>
                            </div>

                            <h3>Tratamiento Preventivo</h3>

                            <p>Aplicación de película protectora, implementación
                                de tratamiento químico y programa preventivo
                                para evitar reincrustación.</p>

                        </div>
                    </div>

                    <div class="recuadros">

                        <div>
                            <div class="imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-circle-check-big text-white">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                    <path d="m9 11 3 3L22 4"></path>
                                </svg>
                            </div>

                            <h3>Informe Técnico</h3>

                            <p>Documentación fotográfica antes/después,
                                análisis de depósitos, registro de parámetros
                                y certificado de limpieza.</p>

                        </div>
                    </div>
                </ul>
            </div>
        </div>
    </section>


    <section class="section4">

        <div class="container">

            <h2 class="title4">Beneficios Operativos Comprobados</h2>

            <div class="contenedor4">

                <div class="recuadro">


                    <div class="logotipo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-trending-up text-white">
                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                            <polyline points="16 7 22 7 22 13"></polyline>
                        </svg>

                    </div>

                    <div class="informacion">
                        <h3>Recuperación de Eficiencia del 20-30%</h3>
                        <p>Eliminación de capa aislante de incrustación
                            restaura transferencia de calor original, r
                            educiendo consumo de combustible dramáticamente.</p>

                    </div>

                </div>


                <div class="recuadro">


                    <div class="logotipo">


                    </div>

                    <div class="informacion">
                        <h3>Prevención de Fallas por Sobrecalentamiento</h3>
                        <p>Tubos limpios eliminan puntos calientes que causan
                            deformación, fisuras y perforaciones
                            catastróficas en calderas.</p>

                    </div>

                </div>



                <div class="recuadro">


                    <div class="logotipo">


                    </div>

                    <div class="informacion">
                        <h3>Extensión de Vida Útil +10 Años</h3>
                        <p>Limpieza profesional periódica previene
                            corrosión bajo depósito y deterioro
                            prematuro de superficies metálicas críticas.</p>

                    </div>

                </div>



                <div class="recuadro">


                    <div class="logotipo">


                    </div>

                    <div class="informacion">
                        <h3>Retorno de Inversión Inmediato</h3>
                        <p>Ahorro energético post-desincrustación
                            recupera inversión del servicio en 3-6
                            meses de operación normal.</p>

                    </div>

                </div>


            </div>



        </div>


    </section>


    <section class="seccion5">
        <div class="container">

            <h2 class="title5">Nuestro Proceso de Trabajo</h2>
            <div class="general5">


                <div class="recuadro5">

                    <div class="logotipo5">1</div>
                    <h3>Evaluación</h3>
                    <p>Inspección endoscópica, medición de espesores
                        de incrustación, análisis químico de depósitos
                        y diseño de protocolo.</p>

                </div>

                <div class="recuadro5">

                    <div class="logotipo5">2</div>
                    <h3>Circulación Ácida</h3>
                    <p>Bombeo de solución desincrustante,
                        control de pH/temperatura, monitoreo de
                        disolución y ajuste de concentración.</p>

                </div>

                <div class="recuadro5">

                    <div class="logotipo5">3</div>
                    <h3>Enjuague y Pasivación</h3>
                    <p>Neutralización química, enjuagues
                        múltiples con agua tratada y aplicación de
                        película protectora anticorrosiva.</p>

                </div>

                <div class="recuadro5">

                    <div class="logotipo5">4</div>
                    <h3>Certificación</h3>
                    <p>Inspección final endoscópica, análisis de agua final,
                        entrega de certificado y programa de tratamiento
                        preventivo.</p>

                </div>



            </div>
        </div>
    </section>


    <section>
        <div class="general">

            <div class="recuadro">

                <div class="numeros">30+</div>
                <h3>Años de Experiencia</h3>
                <p>Especialistas en limpieza química de calderas
                    con protocolos certificados y productos
                    biodegradables de última generación.</p>

            </div>


            <div class="recuadro">

                <div class="numeros">400+</div>
                <h3>Calderas Desincrustadas</h3>
                <p>Equipos térmicos industriales limpiados
                    exitosamente sin daño a superficies metálicas
                    con garantía documentada.</p>

            </div>




            <div class="recuadro">
                <div class="numeros">100%</div>
                <h3>Seguridad Operativa</h3>
                <p>Protocolos de seguridad industrial
                    certificados, personal capacitado y
                    seguros de responsabilidad civil vigentes.</p>

            </div>


        </div>



    </section>


    <section>

        <div class="general">
            <h2></h2>
            <p></p>


            <div class="botones"></div>

        </div>


    </section>
@endsection
 