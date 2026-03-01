<footer class="site-footer" role="contentinfo" itemscope itemtype="https://schema.org/Organization">
    <div class="footer-container">
        <section class="footer-top" aria-label="Información general del sitio">
            <div class="footer-grid">
                <section class="footer-brand" aria-label="Información de la empresa">
                    <a href="/" aria-label="Ir al inicio" itemprop="url" title="SIMARI Calderas - Inicio">
                        <img class="footer-logo" src="{{ Vite::asset('resources/images/logo/logo_SVG.svg') }}"
                            alt="SIMARI Calderas - soluciones térmicas industriales y residenciales" itemprop="logo"
                            loading="lazy" decoding="async">
                    </a>
                    <p itemprop="description">
                        Líderes en soluciones térmicas industriales y residenciales. Innovación, eficiencia y confianza
                        técnica desde 1995.
                    </p>
                    <address aria-label="Datos de contacto">
                        <p>
                            <a href="mailto:contacto@simari.com" itemprop="email"
                                aria-label="Enviar correo a SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <span>contacto@simari.com</span>
                            </a>
                        </p>
                        <p>
                            <a href="tel:+525512345678" itemprop="telephone" aria-label="Llamar a SIMARI Calderas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                    </path>
                                </svg>
                                <span>+52 55 1234 5678</span>
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
                        <li><a href="/soluciones/industrial">Mantenimiento industrial</a></li>
                        <li><a href="/soluciones/residencial">Calibración de equipos</a></li>
                        <li><a href="/soluciones/mantenimiento">Conversión de quemadores</a></li>
                        <li><a href="/soluciones/eficiencia">Fabricación Equipos Periféricos</a></li>
                        <li><a href="/ingenieria">Automatización de Sistemas</a></li>
                        <li><a href="/soluciones/residencial">Mantenimiento de Chillers</a></li>
                        <li><a href="/soluciones/mantenimiento">Desincrustación de Calderas</a></li>
                        <li><a href="/soluciones/eficiencia">Reparación de Secadoras</a></li>
                    </ul>
                </nav>

                <!-- Navegación: Empresa -->
                <nav class="footer-nav" aria-label="Empresa">
                    <h2>Empresa</h2>
                    <ul>
                        <li><a href="/empresa">Inicio</a></li>
                        <li><a href="/empresa">Nosotros</a></li>
                        <li><a href="/proyectos">Contacto</a></li>
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
</footer>

<!-- JSON-LD structured data for SEO -->
{{-- <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "SIMARI Calderas S.A. de C.V.",
    "url": "{{ url('/') }}",
    "logo": "{{ asset('images/logo-footer.png') }}",
    "sameAs": [
      "https://www.linkedin.com/company/simari-calderas",
      "https://www.facebook.com/simaricalderas",
      "https://twitter.com/simari_calderas",
      "https://www.instagram.com/simari_calderas"
    ],
    "contactPoint": [
      {
        "@type": "ContactPoint",
        "telephone": "+52 55 1234 5678",
        "contactType": "customer service",
        "areaServed": "MX",
        "availableLanguage": ["Spanish", "English"]
      }
    ],
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Av. Industrial 500, Parque Tecnológico",
      "addressLocality": "CDMX",
      "addressCountry": "MX"
    }
  }
  </script> --}}
