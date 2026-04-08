<footer class="site-footer" role="contentinfo" itemscope itemtype="https://schema.org/Organization">
    <div class="footer-container">
        <section class="footer-top" aria-label="Información general del sitio">
            <div class="footer-grid">
                <section class="footer-brand" aria-label="Información de la empresa">
                    <a href="{{ route('home') }}" aria-label="Ir al inicio" itemprop="url" title="SIMARI Calderas - Inicio">
                        <img class="footer-logo" src="{{ Vite::asset('resources/images/logo/logo_SVG.svg') }}"
                            alt="Industria Simari - soluciones térmicas industriales y residenciales" itemprop="logo"
                            width="268" height="60"
                            loading="lazy" decoding="async">
                    </a>
                    <p itemprop="description">
                        Líderes en soluciones térmicas industriales y residenciales. Innovación, eficiencia y confianza
                        técnica desde 1995.
                    </p>
                    <address class="footer-info-contact-address" aria-label="Datos de contacto">
                        <p>
                            <a href="mailto:administracion@industriasimari.com.mx" itemprop="email"
                                aria-label="Enviar correo a SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <span>administracion@industriasimari.com.mx</span>
                            </a>
                        </p>
                        <p>
                            <a href="tel:+524494348018" itemprop="telephone" aria-label="Llamar a Industria Simari">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                    </path>
                                </svg>
                                <span>+52 449 434 8018</span>
                            </a>
                        </p>
                        {{-- Addres Info --}}
                        {{-- <p itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path
                                    d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                </path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span itemprop="streetAddress">Av. Industrial 500, Parque Tecnológico</span>,
                            <span itemprop="addressLocality">CDMX</span>,
                            <span itemprop="addressCountry">México</span>
                        </p> --}}
                    </address>
                </section>

                <!-- Navegación: Servicios -->
                <nav class="footer-nav" aria-label="Servicios">
                    <h2>Servicios</h2>
                    <ul>
                        <li><a href="{{ route('industrial-maintenance') }}">Mantenimiento industrial</a></li>
                        <li><a href="{{ route('hydraulic-engineering') }}">Ingenieria Hidraulica</a></li>
                        <li><a href="{{ route('equipement-calibration') }}">Calibracion de equipos</a></li>
                        <li><a href="{{ route('water-treatment') }}">Tratamiento de Agua</a></li>
                        <li><a href="{{ route('automation') }}">Automatizacion</a></li>
                        <li><a href="{{ route('chiller-maintenance') }}">Mantenimiento de Chillers</a></li>
                        <li><a href="{{ route('descale-boilers') }}">Desincrustación de Calderas</a></li>
                        <li><a href="{{ route('industrial-project') }}">Proyecto Industrial</a></li>
                        <li><a href="{{ route('hair-repair')}}">Reparación de Secadoras</a></li>
                    </ul>
                </nav>

                <!-- Navegación: Empresa -->
                <nav class="footer-nav" aria-label="Empresa">
                    <h2>Empresa</h2>
                    <ul>
                        <li><a href="{{ route('home') }}">Inicio</a></li>
                        <li><a href="{{ route('company') }}">Nosotros</a></li>
                        <li><a href="{{ route('contact') }}">Contacto</a></li>
                    </ul>
                </nav>

                <!-- Newsletter + redes -->
                <section class="footer-newsletter" aria-label="Suscripción y redes sociales">
                    <h2>Catálogo de productos</h2>
                    <p>Recibe novedades sobre eficiencia energética y mantenimiento.</p>

                    <form class="form-footer" action="/suscripcion" method="post"
                        aria-label="Formulario de suscripción">
                        @csrf
                        <input id="newsletter-email" name="email" type="email" placeholder="Tu correo electrónico"
                            autocomplete="email" inputmode="email" required>
                        <button class="button_footer" type="submit">Suscribirse</button>
                    </form>

                    <nav class="social" aria-label="Redes sociales">
                        <ul class="social-list">
                            {{-- Linkedin --}}
                            {{-- <li>
                                <a href="https://www.linkedin.com/company/simari-calderas" rel="noopener noreferrer" aria-label="LinkedIn de SIMARI Calderas" itemprop="sameAs" title="LinkedIn de SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect width="4" height="12" x="2" y="9"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                                </a>
                            </li> --}}
                            <li>
                                <a href="https://www.facebook.com/simaricalderas" rel="noopener noreferrer"
                                    aria-label="Facebook de SIMARI Calderas" itemprop="sameAs"
                                    title="Facebook de SIMARI Calderas">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 20 20">
                                        <g clip-path="url(#clip0_20_1323)">
                                            <path
                                                d="M14.9978 1.66641H12.4982C11.3933 1.66641 10.3336 2.10533 9.55235 2.88662C8.77106 3.6679 8.33214 4.72755 8.33214 5.83245V8.33208H5.83252V11.6649H8.33214V18.3306H11.665V11.6649H14.1646L14.9978 8.33208H11.665V5.83245C11.665 5.61147 11.7528 5.39954 11.909 5.24329C12.0653 5.08703 12.2772 4.99925 12.4982 4.99925H14.9978V1.66641Z"
                                                stroke-width="1.66642" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_20_1323">
                                                <rect width="19.997" height="19.997" fill="white" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </a>
                            </li>
                            {{-- Teiiter --}}
                            {{-- <li>
                                <a href="https://twitter.com/simari_calderas" rel="noopener noreferrer" aria-label="X (Twitter) de SIMARI Calderas" itemprop="sameAs" title="X (Twitter) de SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                                </svg>
                                </a>
                            </li> --}}

                            {{-- Instagram --}}
                            {{-- <li>
                                <a href="https://www.instagram.com/simari_calderas" rel="noopener noreferrer" aria-label="Instagram de SIMARI Calderas" itemprop="sameAs" title="Instagram de SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                                </svg>
                                </a>
                            </li> --}}
                        </ul>
                    </nav>
                </section>

            </div>
        </section>
        <!-- Footer end terminated -->
        <section class="footer-bottom" aria-label="Legal">
            <p>
                © <span>2026 </span>
                <span itemprop="name">SIMARI Calderas S.A. de C.V.</span>
                <span> Todos los derechos reservados.</span>
            </p>
            <nav class="footer-legal" aria-label="Enlaces legales">
                <ul class="footer-legal-links">
                    <li><a href="{{ route('privacy-notice') }}">Aviso de privacidad</a></li>
                    <li><a href="{{ route('terms-of-service') }}">Términos y condiciones</a></li>
                </ul>
            </nav>
        </section>
    </div>
@auth
    @php $rol = auth()->user()->role?->name_role @endphp

    @if ($rol === 'admin')
        <a style="font-size:28px; color:white;"  href="{{ route('admin.dashboard') }}">Panel Admin</a>

    @elseif ($rol === 'employe')
        <a style="font-size:28px; color:white;" href="{{ route('employee.dashboard') }}">Mi Dashboard</a>

    @else
        <a style="font-size:28px; color:white;" href="{{ route('profile.edit') }}">Mi Cuenta</a>
    @endif
@else
    <a style="font-size:28px; color:white;" href="{{ route('login') }}">Iniciar Sesión</a>
@endauth
</footer>
<!-- =====================================================
     BOTÓN FLOTANTE DE WHATSAPP
     Ubicación: resources/views/frontend/layouts/footer.blade.php (al final)
     Estilos:   resources/css/components/navbar.css → .whatsapp-float
====================================================== -->
<!-- WhatsApp Float Button -->
<div class="wa-float wa-float--right" aria-label="Chat de WhatsApp">

    <div class="wa-tooltip" role="status" aria-live="polite">
        <p class="wa-tooltip__title">¡Hola! 👋</p>
        <p class="wa-tooltip__message">
            Hola, me interesa una cotización.
        </p>

        <div class="wa-tooltip__footer">
            <span class="wa-tooltip__online">En línea</span>
            <span class="wa-tooltip__schedule">
                Lun - Vie 8:00 a 18:00
            </span>
        </div>
    </div>

    <a
        href="https://wa.me/524494348018?text=Hola%2C%20me%20interesa%20una%20cotizaci%C3%B3n."
        target="_blank"
        rel="noopener noreferrer"
        class="wa-button"
        aria-label="Abrir chat de WhatsApp"
    >

        <svg class="wa-icon" fill="#ffffff" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>whatsapp</title> <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path> </g></svg>

        <span class="wa-notify">
            <span class="wa-notify__ping"></span>
            <span class="wa-notify__dot"></span>
        </span>

    </a>

</div>
