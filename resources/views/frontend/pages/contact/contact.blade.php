 @extends('frontend.layouts.master')
@section('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/contact.css') }}">
@endsection
@section('content')
    <div class="contact-page">

    <header class="contact-header">
        <div class="contact-header-decor"></div>
        <div class="container relative z-10">
            <h1 class="contact-title-main">Contacto</h1>
            <p class="contact-subtitle">Soluciones térmicas a medida para su industria. Estamos listos para atender sus requerimientos con la máxima eficiencia técnica.</p>
        </div>
    </header>

    <section class="contact-whatsapp-banner">
        <div class="contact-banner-texture"></div>
        <div class="container relative z-10 text-center">
            <h2 class="contact-banner-title">¿Deseas una cotización inmediata?</h2>
            <p class="contact-banner-text">Contáctanos vía WhatsApp para atención prioritaria</p>
            <a href="https://wa.me/524494348018" target="_blank" rel="noopener noreferrer" class="contact-btn-whatsapp">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path></svg>
                +52 (449) 434-8018
            </a>
        </div>
    </section>

    <section class="contact-main-section">
        <div class="container">
            <div class="contact-grid-2">

                <div class="contact-info-column">
                    <div class="contact-info-header">
                        <h2 class="contact-section-title text-dark">Información de Contacto</h2>
                        <div class="contact-divider"></div>
                        <p class="contact-desc text-gray">Póngase en contacto con nuestro equipo de ingeniería y ventas.</p>
                    </div>

                    <div class="contact-info-list">
                        <div class="contact-info-item">
                            <div class="contact-icon-box bg-light text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <div>
                                <h3 class="contact-item-title text-dark">Dirección Corporativa</h3>
                                <p class="contact-item-text text-gray">Benito Juárez 118A<br>Col. Los Pocitos CP 20328<br>Jesús María, Aguascalientes</p>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon-box bg-light text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <div>
                                <h3 class="contact-item-title text-dark">Central Telefónica</h3>
                                <div class="contact-links-group">
                                    <a href="tel:+524494348018" class="contact-link">+52 (449) 434-8018</a>
                                    <a href="tel:+524498068894" class="contact-link">+52 (449) 806-8894</a>
                                </div>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon-box bg-light text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            </div>
                            <div>
                                <h3 class="contact-item-title text-dark">Recepción y Cotizaciones</h3>
                                <div class="contact-links-group">
                                    <a href="mailto:servicios@simaricalderas.com" class="contact-link font-medium">servicios@simaricalderas.com</a>
                                    <a href="tel:+524493968406" class="contact-link">+52 (449) 396-8406</a>
                                </div>
                            </div>
                        </div>

                        <div class="contact-info-item">
                            <div class="contact-icon-box bg-light text-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
                            </div>
                            <div>
                                <h3 class="contact-item-title text-dark">Facturación</h3>
                                <a href="mailto:facturacion@simaricalderas.com" class="contact-link font-medium">facturacion@simaricalderas.com</a>
                            </div>
                        </div>
                    </div>

                    <div class="contact-guarantees">
                        <div class="contact-guarantee-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <div>
                                <p class="contact-guarantee-title text-dark">Atención 24/7</p>
                                <p class="contact-guarantee-subtitle">Para emergencias</p>
                            </div>
                        </div>
                        <div class="contact-guarantee-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
                            <div>
                                <p class="contact-guarantee-title text-dark">Garantía Técnica</p>
                                <p class="contact-guarantee-subtitle">Personal certificado</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="contact-form-wrapper">
                    <h3 class="contact-form-title text-dark">Envíenos un mensaje</h3>
                    <form class="contact-form" action="#" method="POST">

                        <div class="contact-form-row">
                            <div class="contact-input-group">
                                <label>Nombre Completo</label>
                                <input type="text" name="name" placeholder="Ej. Juan Pérez" required>
                                </div>
                            <div class="contact-input-group">
                                <label>Empresa</label>
                                <input type="text" name="company" placeholder="Ej. Industrias SA">
                            </div>
                        </div>

                        <div class="contact-form-row">
                            <div class="contact-input-group">
                                <label>Correo Electrónico</label>
                                <input type="email" name="email" placeholder="nombre@empresa.com" required>
                                </div>
                            <div class="contact-input-group">
                                <label>Teléfono</label>
                                <input type="tel" name="phone" placeholder="(000) 000-0000" required>
                                </div>
                        </div>

                        <div class="contact-input-group">
                            <label>Tipo de Servicio</label>
                            <select name="serviceType" required>
                                <option value="">Seleccione un servicio...</option>
                                <option value="mantenimiento">Mantenimiento Preventivo</option>
                                <option value="calibracion">Calibración de Equipos</option>
                                <option value="conversion">Conversión de Quemadores</option>
                                <option value="automatizacion">Automatización</option>
                                <option value="ingenieria">Ingeniería a Medida</option>
                                <option value="otro">Otro</option>
                            </select>
                            </div>

                        <div class="contact-input-group">
                            <label>Mensaje</label>
                            <textarea name="message" rows="4" placeholder="Describa su requerimiento o consulta..." required></textarea>
                            </div>

                        <button type="submit" class="contact-btn-submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"></path><path d="m21.854 2.147-10.94 10.939"></path></svg>
                            Enviar Solicitud
                        </button>

                        <p class="contact-form-disclaimer">Al enviar este formulario, acepta nuestra política de privacidad y el tratamiento de sus datos personales.</p>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <div class="contact-map-placeholder">
        <div class="contact-map-overlay">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-red"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <p class="contact-map-title">Ubicación en Mapa</p>
            <p class="contact-map-subtitle">Jesús María, Aguascalientes</p>
        </div>
        </div>

</div>
@endsection
