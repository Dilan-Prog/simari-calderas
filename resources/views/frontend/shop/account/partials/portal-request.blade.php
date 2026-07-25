@if (!$customer->portal_access && !$portalRequestCompleted)
<section x-show="section === 'solicitar-portal'" x-cloak
    x-data="portalWizard(@js([
        'answerUrl' => route('shop.portal-request.answer'),
        'purchase_frequency' => $portalRequest?->purchase_frequency,
        'purchase_amount' => $portalRequest?->purchase_amount,
        'reason' => $portalRequest?->reason,
    ]))">

    <h1 class="portal-title">Solicitar acceso al portal de servicios</h1>
    <p class="portal-subtitle">
        Cuéntanos un poco sobre tu operación para habilitar tu acceso al portal de
        reportes de servicio y servicios técnicos.
    </p>

    <div class="portal-card wizard">
        {{-- Progreso --}}
        <div class="wizard__progress">
            <div class="wizard__progress-bar" :style="'width:' + progress + '%'"></div>
        </div>

        {{-- Paso 1: frecuencia --}}
        <div x-show="step === 1" x-cloak>
            <div class="wizard__question">¿Con qué frecuencia comprarías con nosotros?</div>
            <div class="wizard__options">
                <template x-for="opt in ['Mensual', 'Trimestral', 'Semestral']" :key="opt">
                    <button type="button" class="wizard__option"
                        :class="{ 'is-selected': answers.purchase_frequency === opt }"
                        @click="answer('purchase_frequency', opt)" x-text="opt"></button>
                </template>
            </div>
        </div>

        {{-- Paso 2: monto --}}
        <div x-show="step === 2" x-cloak>
            <div class="wizard__question">¿Cuál sería tu monto aproximado de compra por periodo?</div>
            <div class="wizard__options">
                <template x-for="opt in ['Menos de $10,000 MXN', '$10,000 – $50,000 MXN', '$50,000 – $200,000 MXN', 'Más de $200,000 MXN']" :key="opt">
                    <button type="button" class="wizard__option"
                        :class="{ 'is-selected': answers.purchase_amount === opt }"
                        @click="answer('purchase_amount', opt)" x-text="opt"></button>
                </template>
            </div>
        </div>

        {{-- Paso 3: razón --}}
        <div x-show="step === 3" x-cloak>
            <div class="wizard__question">¿Por qué quieres acceso al portal?</div>
            <textarea class="wizard__textarea" x-model="answers.reason" rows="3"
                placeholder="Ej: dar seguimiento a los mantenimientos de nuestras calderas..."></textarea>
            <button type="button" class="portal-btn" :disabled="!answers.reason || answers.reason.trim().length < 5"
                @click="answer('reason', answers.reason.trim())">Continuar</button>
        </div>

        {{-- Paso final: gracias + datos fiscales --}}
        <div x-show="step === 4" x-cloak>
            <div class="wizard__thanks">
                <div class="wizard__thanks-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#178a3c" stroke-width="2.4"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <strong>Gracias por la información, la estaremos analizando.</strong>
                    <p>Para terminar, completa tus datos fiscales y sube tu Constancia de Situación Fiscal.</p>
                </div>
            </div>

            @if ($errors->any() && session('portal_request_errors'))
                <div class="auth-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('shop.portal-request.finish') }}" enctype="multipart/form-data">
                @csrf

                <div class="portal-field" x-data="{ who: 'cuenta' }">
                    <label>Nombre del responsable</label>
                    <div class="wizard__radio-row">
                        <label class="wizard__radio">
                            <input type="radio" value="cuenta" x-model="who"> {{ $customer->first_name }} {{ $customer->last_name }} (titular de la cuenta)
                        </label>
                        <label class="wizard__radio">
                            <input type="radio" value="otro" x-model="who"> Otro
                        </label>
                    </div>
                    <template x-if="who === 'cuenta'">
                        <input type="hidden" name="responsible_name" value="{{ $customer->first_name }} {{ $customer->last_name }}">
                    </template>
                    <template x-if="who === 'otro'">
                        <input type="text" name="responsible_name" placeholder="Nombre completo del responsable" value="{{ old('responsible_name') }}" required>
                    </template>
                </div>

                <div class="portal-grid-2">
                    <div class="portal-field">
                        <label>RFC</label>
                        <input type="text" name="rfc" value="{{ old('rfc', $customer->rfc) }}" placeholder="XAXX010101000" required>
                    </div>
                    <div class="portal-field">
                        <label>Régimen fiscal</label>
                        <input type="text" name="tax_regime" value="{{ old('tax_regime') }}" placeholder="Ej: 601 - General de Ley Personas Morales" required>
                    </div>
                    <div class="portal-field">
                        <label>Correo de contacto</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $customer->email) }}" required>
                    </div>
                    <div class="portal-field">
                        <label>Teléfono</label>
                        <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" required>
                    </div>
                    <div class="portal-field portal-field--full">
                        <label>Constancia de Situación Fiscal (PDF o imagen, máx. 8 MB)</label>
                        <input type="file" name="fiscal_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>

                <button type="submit" class="portal-btn" style="margin-top:16px;">Enviar solicitud</button>
            </form>
        </div>
    </div>
</section>
@endif
