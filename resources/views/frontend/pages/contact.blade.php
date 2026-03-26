@extends('frontend.layouts.master')

@section('styles')
    @vite(['resources/css/contact.css'])
@endsection

@section('content')
<!-- Hero Section -->
<header class="hero-contact">
    <div class="container">
        <div class="hero-overlay"></div>
        <div class="container hero-container">
            <h1 class="hero-title">Contacto</h1>
            <p class="hero-description">
                Soluciones térmicas a medida para su industria.
                Nuestro equipo técnico está listo para atender su requerimiento con máxima eficiencia.
            </p>
        </div>
    </div>
</header>

<!-- WhatsApp CTA Section -->
<section class="cta-section" aria-labelledby="cta-title">
    <div class="container cta-container">
        <h2 id="cta-title" class="cta-title">¿Deseas una cotización inmediata?</h2>
        <p class="cta-subtitle">Contáctanos vía WhatsApp para atención prioritaria</p>
        <a href="https://wa.me/524494348018" target="_blank" rel="noopener noreferrer" class="btn-whatsapp">
            <svg fill="#ffffff" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>whatsapp</title> <path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"></path> </g></svg>
            WhatsApp +52 (449) 434-8018
        </a>
    </div>
</section>

<!-- Contact Information Section -->
<section class="contact-info-section" aria-labelledby="info-title">
    <div class="container contact-info-container">
        <div class="cotact-info">
            <div class="info-header">
                <h2 id="info-title" class="info-title">Información de Contacto</h2>
                <div class="title-underline"></div>
                <p class="info-subtitle">Póngase en contacto con nuestro equipo de ingeniería y ventas.</p>
            </div>
    
            <div class="contact-grid">
                <!-- Dirección -->
                <article class="contact-card">
                    <div class="contact-icon-wrapper address-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="contact-content">
                        <h3>Dirección Corporativa</h3>
                        <address>
                            Benito Juárez 118A<br>
                            Col. Los Pocitos CP 20328<br>
                            Jesús María, Aguascalientes, México
                        </address>
                    </div>
                </article>
    
                <!-- Teléfono -->
                <article class="contact-card">
                    <div class="contact-icon-wrapper phone-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="contact-content">
                        <h3>Central Telefónica</h3>
                        <ul class="phone-list">
                            <li><a href="tel:+524494348018">+52 (449) 434-8018</a></li>
                            <li><a href="tel:+524498068894">+52 (449) 806-8894</a></li>
                        </ul>
                    </div>
                </article>
    
                <!-- Email Servicios -->
                <article class="contact-card">
                    <div class="contact-icon-wrapper email-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                        </svg>
                    </div>
                    <div class="contact-content">
                        <h3>Recepción y Cotizaciones</h3>
                        <ul class="email-list">
                            <li><a href="mailto:servicios@simaricalderas.com">servicios@simaricalderas.com</a></li>
                            <li><a href="tel:+524493968406">+52 (449) 396-8406</a></li>
                        </ul>
                    </div>
                </article>
    
                <!-- Email Facturación -->
                <article class="contact-card">
                    <div class="contact-icon-wrapper document-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                            <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                            <path d="M10 9H8"></path>
                            <path d="M16 13H8"></path>
                            <path d="M16 17H8"></path>
                        </svg>
                    </div>
                    <div class="contact-content">
                        <h3>Facturación</h3>
                        <a href="mailto:facturacion@simaricalderas.com">facturacion@simaricalderas.com</a>
                    </div>
                </article>
                <div class="additional-features">
                    <article class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <div>
                            <strong>Atención 24/7</strong>
                            <p>Para emergencias</p>
                        </div>
                    </article>
                    <article class="feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                        <div>
                            <strong>Garantía Técnica</strong>
                            <p>Personal certificado</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
        <div class="form-wrapper">
            <h2 id="form-title" class="form-title">Envíenos un mensaje</h2>
            
            <form class="contact-form" method="POST" action="" novalidate>
                @csrf
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nombre Completo <span aria-label="requerido">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required 
                            placeholder="Ej. Juan Pérez"
                            aria-required="true">
                    </div>
                    <div class="form-group">
                        <label for="company">Empresa</label>
                        <input 
                            type="text" 
                            id="company" 
                            name="company" 
                            placeholder="Ej. Industrias SA">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Correo Electrónico <span aria-label="requerido">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            placeholder="nombre@empresa.com"
                            aria-required="true">
                    </div>
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            placeholder="(000) 000-0000">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="serviceType">Tipo de Servicio</label>
                    <select id="serviceType" name="serviceType" required>
                        <option value="">Seleccione un servicio...</option>
                        <option value="mantenimiento">Mantenimiento Preventivo</option>
                        <option value="calibracion">Calibración de Equipos</option>
                        <option value="conversion">Conversión de Quemadores</option>
                        <option value="automatizacion">Automatización</option>
                        <option value="ingenieria">Ingeniería a Medida</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="message">Mensaje</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        rows="5" 
                        placeholder="Describa su requerimiento o consulta..."></textarea>
                </div>

                <button type="submit" class="button-primary form-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"></path>
                        <path d="m21.854 2.147-10.94 10.939"></path>
                    </svg>
                    Enviar Solicitud
                </button>

                <p class="form-privacy">Al enviar este formulario, acepta nuestra política de privacidad y el tratamiento de sus datos personales.</p>
            </form>
        </div>
    </div>
</section>


@endsection