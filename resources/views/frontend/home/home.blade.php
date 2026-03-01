 @extends('frontend.layouts.master')
 @section('styles')
     <!-- (Opcional) Estilos específicos para esta página -->
     <link rel="stylesheet" href="{{ asset('css/home.css') }}" />
 @endsection
 @section('content')
     <section class="hero-section">
         <div class="hero-background">
             <img src="https://images.unsplash.com/photo-1707596830261-9c6138a6dd3b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NjM2Nzh8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYm9pbGVyfGVufDB8fHx8MTcwNzU5NjgzMHww&ixlib=rb-4.0.3&q=80&w=1080"
                 alt="Caldera industrial">
             <div class="hero-overlay"></div>
         </div>

         <div class="hero-container">
             <div class="hero-content">
                 <div class="badge">
                     <span class="dot"></span>
                     <span class="badge-text">Ingeniería Térmica Avanzada</span>
                 </div>

                 <h1 class="hero-title">
                     Soluciones Integrales<br>
                     en <span class="text-highlight">Calderas</span> y Energía
                 </h1>

                 <p class="hero-description">
                     Optimizamos sus procesos industriales con tecnología de vanguardia.
                     Eficiencia energética garantizada y soporte técnico especializado 24/7.
                 </p>

                 <div class="hero-actions">
                    <button class="button-primary">
                        Solicitar Cotización
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" style="margin-left: 8px;">
                             <path d="M5 12h14"></path>
                             <path d="m12 5 7 7-7 7"></path>
                         </svg>
                    </button>
                     {{-- <a href="/contacto" class="btn btn-primary">
                         Solicitar Cotización
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" style="margin-left: 8px;">
                             <path d="M5 12h14"></path>
                             <path d="m12 5 7 7-7 7"></path>
                         </svg>
                     </a> --}}
                    <button class="button-secondary"> Soluciones</button>
                 </div>
             </div>
         </div>
     </section>

     <section class="stats-section">
         <div class="stats-container">
             <div class="stat-item">
                 <div class="stat-number">25+</div>
                 <div class="stat-label">Años de Experiencia</div>
             </div>

             <div class="stat-item">
                 <div class="stat-number">500+</div>
                 <div class="stat-label">Proyectos Ejecutados</div>
             </div>

             <div class="stat-item">
                 <div class="stat-number">98%</div>
                 <div class="stat-label">Eficiencia Energética</div>
             </div>

             <div class="stat-item">
                 <div class="stat-number">24/7</div>
                 <div class="stat-label">Soporte Técnico</div>
             </div>
         </div>
     </section>

     <section class="solutions-section">
         <div class="container">
             <div class="text-center mb-16">
                 <span class="section-subtitle">Nuestras Soluciones</span>
                 <h2 class="section-title">Ingeniería que Impulsa su Industria</h2>
                 <p class="section-desc">
                     Diseñamos, instalamos y mantenemos sistemas térmicos de alto rendimiento
                     adaptados a las necesidades específicas de su planta.
                 </p>
             </div>

             <div class="solutions-grid">
                 <div class="solution-card">
                     <div class="card-img-box">
                         <img src="https://images.unsplash.com/photo-1707596830261-9c6138a6dd3b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1NjM2Nzh8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwYm9pbGVyfGVufDB8fHx8MTcwNzU5NjgzMHww&ixlib=rb-4.0.3&q=80&w=1080"
                             alt="Calderas Industriales">
                         <div class="card-img-overlay"></div>
                         <div class="card-icon-overlay">
                             <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path
                                     d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                                 <path d="M17 18h1" />
                                 <path d="M12 18h1" />
                                 <path d="M7 18h1" />
                             </svg>
                         </div>
                     </div>
                     <div class="card-body">
                         <h4 class="card-title">Calderas Industriales</h4>
                         <p class="card-text">Sistemas de generación de vapor y agua caliente de alta eficiencia para
                             procesos críticos.</p>
                         <a href="#" class="card-link">
                             Más Información
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M5 12h14"></path>
                                 <path d="m12 5 7 7-7 7"></path>
                             </svg>
                         </a>
                     </div>
                 </div>

                 <div class="solution-card">
                     <div class="card-img-box">
                         <img src="https://images.unsplash.com/photo-1759847552281-60e45956124d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmR1c3RyaWFsJTIwd2VsZGluZyUyMG1hbnVmYWN0dXJpbmclMjBzcGFya3N8ZW58MXx8fDE3NzE1MjI0Mzd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                             alt="Mantenimiento Integral">
                         <div class="card-img-overlay"></div>
                         <div class="card-icon-overlay">
                             <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path
                                     d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
                                 <circle cx="12" cy="12" r="3" />
                             </svg>
                         </div>
                     </div>
                     <div class="card-body">
                         <h4 class="card-title">Mantenimiento Integral</h4>
                         <p class="card-text">Programas preventivos y correctivos para asegurar la continuidad operativa y
                             seguridad.</p>
                         <a href="#" class="card-link">
                             Más Información
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M5 12h14"></path>
                                 <path d="m12 5 7 7-7 7"></path>
                             </svg>
                         </a>
                     </div>
                 </div>

                 <div class="solution-card">
                     <div class="card-img-box">
                         <img src="https://images.unsplash.com/photo-1769152683420-f4eff91cb30b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx0ZWNobmljYWwlMjBibHVlcHJpbnQlMjBzY2hlbWF0aWNzJTIwZHJhd2luZ3xlbnwxfHx8fDE3NzE1MjI0Mzd8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral"
                             alt="Eficiencia Energética">
                         <div class="card-img-overlay"></div>
                         <div class="card-icon-overlay">
                             <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path
                                     d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14H4Z" />
                             </svg>
                         </div>
                     </div>
                     <div class="card-body">
                         <h4 class="card-title">Eficiencia Energética</h4>
                         <p class="card-text">Auditorías y modernización de equipos para reducir consumo de combustible y
                             emisiones.</p>
                         <a href="#" class="card-link">
                             Más Información
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M5 12h14"></path>
                                 <path d="m12 5 7 7-7 7"></path>
                             </svg>
                         </a>
                     </div>
                 </div>
             </div>
         </div>
     </section>

     <section class="why-section">
         <div class="container why-grid">
             <div class="content-left">
                 <span class="section-subtitle">Por Qué Elegirnos</span>
                 <h2 class="why-title">Excelencia Técnica y Compromiso Total</h2>
                 <p class="why-desc">
                     En SIMARI Calderas, entendemos que la energía es el corazón de su operación.
                     Nuestro enfoque combina ingeniería de precisión con un servicio al cliente inigualable.
                 </p>

                 <div class="feature-list">
                     <div class="feature-item">
                         <div class="feature-icon">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                 <polyline points="22 4 12 14.01 9 11.01"></polyline>
                             </svg>
                         </div>
                         <div class="feature-content">
                             <h4>Certificaciones Internacionales</h4>
                             <p>Cumplimos con normativas ASME, ISO y NOM.</p>
                         </div>
                     </div>

                     <div class="feature-item">
                         <div class="feature-icon">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                 <polyline points="22 4 12 14.01 9 11.01"></polyline>
                             </svg>
                         </div>
                         <div class="feature-content">
                             <h4>Tecnología de Punta</h4>
                             <p>Monitoreo remoto y sistemas automatizados.</p>
                         </div>
                     </div>

                     <div class="feature-item">
                         <div class="feature-icon">
                             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                 <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                 <polyline points="22 4 12 14.01 9 11.01"></polyline>
                             </svg>
                         </div>
                         <div class="feature-content">
                             <h4>Respuesta Inmediata</h4>
                             <p>Equipo técnico listo para emergencias 24/7.</p>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="content-right">
                 <div class="why-image-wrapper">
                     <div class="blur-backdrop"></div>
                     <img class="why-img"
                         src="https://images.unsplash.com/photo-1631583087046-13c813d34e90?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080"
                         alt="Engineering Team">

                     <div class="floating-card">
                         <span class="floating-number">100%</span>
                         <p class="floating-text">Garantía de Satisfacción</p>
                         <p class="floating-sub">En todos nuestros servicios de mantenimiento.</p>
                     </div>
                 </div>
             </div>
         </div>
     </section>

     <section class="cta-section">
         <div class="cta-box">
             <div class="cta-pattern"></div>

             <div class="cta-content">
                 <h2 class="cta-title">¿Listo para optimizar su planta?</h2>
                 <p class="cta-text">
                     Contáctenos hoy para una evaluación gratuita de sus sistemas térmicos.
                 </p>

                 <div class="cta-buttons">
                     <button href="/contacto" class="btn-white">
                         Solicitar Asesoría
                     </button>

                     <button href="tel:+524494348018" class="btn-dark">
                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                             <path
                                 d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6 11.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                             </path>
                         </svg>
                         +52 (449) 434-8018
                        </button>
                 </div>
             </div>
         </div>
     </section>
 @endsection
 <script>
     document.getElementById("year").textContent = new Date().getFullYear();
 </script>
