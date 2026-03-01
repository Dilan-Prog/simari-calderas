@extends('frontend.layouts.master')
@section('styles')
    @vite(['resources/css/privacy-notice.css'])
@endsection
@section('content')
    <section class="simari-privacy-body" aria-label="Contenido del aviso">
        <section class="simari-privacy-presentation">
            <div class="presentation-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="lucide lucide-shield w-10 h-10 text-[#C62828]"
                    data-fg-d5gj105="113.257:113.10658:/src/app/pages/public/Privacy.tsx:133:15:6865:47:e:Shield::::::B8zf">
                    <path
                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                    </path>
                </svg>
                <h1>Aviso de Privacidad</h1>
                <p class="presentation-subcontent-title">
                    En SIMARI valoramos y protegemos su privacidad. Conoce cómo tratamos tus datos personales de manera responsable y transparente.
                </p>
                <p class="presentation-subcontent-date">
                    Última actualización: 28 de febrero de 2026
                </p>
            </div>
        </section>
        <div class="simari-privacy-body__inner">
            <div class="simari-privacy-body__stack">
                <article class="simari-privacy-article" aria-label="Aviso de Privacidad de SIMARI">
                    <section class="simari-privacy-card" aria-labelledby="sec-responsable">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <path
                                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                    </path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-responsable">
                                    Identidad y Domicilio del Responsable
                                </h2>
                                <div class="simari-privacy-card__text">
                                    <p>
                                        <strong>SIMARI Calderas S.A. de C.V.</strong> (en adelante “SIMARI”), con domicilio
                                        en
                                        <span class="simari-privacy-placeholder">[Dirección completa]</span>, es responsable
                                        del tratamiento de sus datos personales.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-datos">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                    <path d="M10 9H8"></path>
                                    <path d="M16 13H8"></path>
                                    <path d="M16 17H8"></path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-datos">Datos Personales Recabados</h2>
                                <div class="simari-privacy-card__text">
                                    <p>
                                        Para las finalidades señaladas en el presente aviso de privacidad, podemos recabar
                                        sus datos personales de distintas formas:
                                    </p>

                                    <ul class="simari-privacy-bullets">
                                        <li>Cuando nos los proporciona directamente</li>
                                        <li>Cuando visita nuestro sitio web o utiliza nuestros servicios en línea</li>
                                        <li>Cuando interactúa con nosotros por correo electrónico, teléfono o redes sociales
                                        </li>
                                    </ul>

                                    <p class="simari-privacy-emphasis">Los datos personales que recabamos incluyen:</p>

                                    <ul class="simari-privacy-bullets">
                                        <li>Datos de identificación: nombre completo, fecha de nacimiento</li>
                                        <li>Datos de contacto: domicilio, teléfono, correo electrónico</li>
                                        <li>Datos laborales: empresa, puesto, giro empresarial</li>
                                        <li>Datos técnicos: información sobre equipos y requerimientos industriales</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 3 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-finalidades">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-finalidades">Finalidades del Tratamiento</h2>
                                <div class="simari-privacy-card__text">
                                    <p>Sus datos personales serán utilizados para las siguientes finalidades necesarias:</p>

                                    <ul class="simari-privacy-bullets">
                                        <li>Proveer los servicios y productos solicitados</li>
                                        <li>Elaborar cotizaciones y propuestas comerciales</li>
                                        <li>Procesar órdenes de compra y facturación</li>
                                        <li>Proporcionar soporte técnico y servicio postventa</li>
                                        <li>Dar cumplimiento a obligaciones legales y fiscales</li>
                                    </ul>

                                    <p>De manera adicional, sus datos pueden ser utilizados para:</p>

                                    <ul class="simari-privacy-bullets">
                                        <li>Enviar información sobre nuevos productos y promociones</li>
                                        <li>Realizar estudios de mercado y mejora de servicios</li>
                                        <li>Invitaciones a eventos y capacitaciones técnicas</li>
                                    </ul>

                                    <p class="simari-privacy-note">
                                        Si no desea que sus datos sean tratados para estas finalidades adicionales, puede
                                        manifestarlo enviando un correo a:
                                        <span class="simari-privacy-placeholder">[email de contacto]</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 4 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-arco">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <path
                                        d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0">
                                    </path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-arco">Derechos ARCO</h2>
                                <div class="simari-privacy-card__text">
                                    <p>Usted tiene derecho a:</p>
                                    <ul class="simari-privacy-bullets">
                                        <li><strong>Acceder</strong> a sus datos personales en nuestra posesión</li>
                                        <li><strong>Rectificar</strong> sus datos cuando sean inexactos o incompletos</li>
                                        <li><strong>Cancelar</strong> sus datos cuando considere que no se requieren</li>
                                        <li><strong>Oponerse</strong> al tratamiento de sus datos para fines específicos
                                        </li>
                                    </ul>

                                    <p>
                                        Para ejercer estos derechos, deberá presentar una solicitud mediante correo
                                        electrónico a:
                                        <a class="simari-privacy-link"
                                            href="mailto:privacidad@simaricalderas.com">privacidad@simaricalderas.com</a>
                                        (o al correo que corresponda), especificando:
                                    </p>

                                    <ul class="simari-privacy-bullets">
                                        <li>Nombre completo y domicilio</li>
                                        <li>Documentos que acrediten su identidad</li>
                                        <li>Descripción clara de los datos sobre los que busca ejercer algún derecho</li>
                                        <li>Cualquier elemento que facilite la localización de sus datos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 5 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-revocacion">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <path
                                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                                    </path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-revocacion">Revocación del Consentimiento
                                </h2>
                                <div class="simari-privacy-card__text">
                                    <p>
                                        Usted puede revocar el consentimiento que nos ha otorgado para el tratamiento de sus
                                        datos personales en cualquier momento.
                                        Sin embargo, es posible que por alguna obligación legal requiramos seguir tratando
                                        sus datos.
                                    </p>
                                    <p>Para revocar su consentimiento, siga el mismo procedimiento establecido para el
                                        ejercicio de Derechos ARCO.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 6 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-transferencias">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2">
                                    </rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-transferencias">Transferencias de Datos
                                </h2>
                                <div class="simari-privacy-card__text">
                                    <p>
                                        SIMARI no realiza transferencias de datos personales a terceros, salvo aquellas
                                        necesarias para atender requerimientos de autoridades
                                        competentes, debidamente fundados y motivados.
                                    </p>
                                    <p>
                                        En caso de requerir transferir sus datos a proveedores de servicios relacionados con
                                        nuestras operaciones, se solicitará su consentimiento expreso
                                        y se garantizará que dichos terceros mantengan medidas de seguridad adecuadas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 7 -->
                    <section class="simari-privacy-card" aria-labelledby="sec-cambios">
                        <div class="simari-privacy-card__row">
                            <div class="simari-privacy-card__badge" aria-hidden="true">
                                <svg class="simari-privacy-icon simari-privacy-icon--md simari-privacy-icon--white"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" focusable="false">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                    <path d="M10 9H8"></path>
                                    <path d="M16 13H8"></path>
                                    <path d="M16 17H8"></path>
                                </svg>
                            </div>

                            <div class="simari-privacy-card__content">
                                <h2 class="simari-privacy-card__title" id="sec-cambios">Cambios al Aviso de Privacidad
                                </h2>
                                <div class="simari-privacy-card__text">
                                    <p>
                                        El presente aviso de privacidad puede sufrir modificaciones, cambios o
                                        actualizaciones por requerimientos legales,
                                        necesidades del servicio o prácticas de privacidad.
                                    </p>
                                    <p>
                                        Cualquier modificación al presente aviso será publicada en:
                                        <a class="simari-privacy-link" href="https://www.simaricalderas.com"
                                            target="_blank" rel="noopener noreferrer">
                                            www.simaricalderas.com
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- CTA CONTACT -->
                    <section class="simari-privacy-cta" aria-labelledby="sec-contacto">
                        <h2 class="simari-privacy-cta__title" id="sec-contacto">
                            <svg class="simari-privacy-icon simari-privacy-icon--lg simari-privacy-icon--primary"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            ¿Tienes dudas sobre tu privacidad?
                        </h2>

                        <p class="simari-privacy-cta__text">
                            Si tienes alguna pregunta sobre este Aviso de Privacidad o deseas ejercer tus derechos ARCO, contáctanos:
                        </p>

                        <div class="simari-privacy-cta__grid">
                            <div class="simari-privacy-contact">
                                <svg class="simari-privacy-icon simari-privacy-icon--sm simari-privacy-icon--primary"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>

                                <div class="simari-privacy-contact__body">
                                    <p class="simari-privacy-contact__label">Correo electrónico</p>
                                    <p class="simari-privacy-contact__value">
                                        <a class="simari-privacy-link" href="mailto:privacidad@simaricalderas.com">
                                            privacidad@simaricalderas.com
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="simari-privacy-contact">
                                <svg class="simari-privacy-icon simari-privacy-icon--sm simari-privacy-icon--primary"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                    </path>
                                </svg>

                                <div class="simari-privacy-contact__body">
                                    <p class="simari-privacy-contact__label">Teléfono</p>
                                    <p class="simari-privacy-contact__value">
                                        <a class="simari-privacy-link" href="tel:+525512345678">+52 (55) 1234-5678</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- /CTA -->
                </article>

            </div>
        </div>
    </section>
@endsection
