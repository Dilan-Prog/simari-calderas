<div class="quote-modal" x-data x-show="$store.shop.quoteOpen" x-cloak>
    <div class="quote-modal__backdrop" @click="$store.shop.closeQuote()"></div>
    <div class="quote-modal__panel">
        <template x-if="!$store.shop.quoteSubmitted">
            <div>
                <div class="quote-modal__header">
                    <div>Completa tus datos</div>
                    <button type="button" @click="$store.shop.closeQuote()">×</button>
                </div>
                <form @submit.prevent="$store.shop.quoteSubmitted = true">
                    <input type="text" placeholder="Nombre completo" required>
                    <input type="text" placeholder="Empresa">
                    <input type="email" placeholder="Correo electrónico" required>
                    <input type="text" placeholder="Teléfono" required>
                    <textarea rows="3" placeholder="Detalles adicionales del proyecto (opcional)"></textarea>
                    <button type="submit">Enviar solicitud de cotización</button>
                </form>
            </div>
        </template>
        <template x-if="$store.shop.quoteSubmitted">
            <div class="quote-modal__success">
                <div class="quote-modal__success-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="quote-modal__success-title">¡Solicitud enviada!</div>
                <p>Un ingeniero de Equiterm se pondrá en contacto contigo en menos de 24 horas para afinar tu cotización.</p>
                <button type="button" @click="$store.shop.closeQuote()">Cerrar</button>
            </div>
        </template>
    </div>
</div>
