{{--
    Modal "Plantillas de pipeline" (80vw x 80vh). Las 3 plantillas están
    definidas 100% client-side en pipelines-index.js (array estático,
    sin backend/tabla nueva) — este partial solo es el shell del modal;
    el grid de tarjetas lo arma el JS.
--}}
<div class="pp-modal-overlay" id="ppTemplatesOverlay">
    <div class="pp-modal pp-modal-xl" id="ppTemplatesModal">
        <div class="pp-modal-header">
            <div>
                <div class="pp-modal-title">Plantillas de pipeline</div>
                <div class="pp-modal-desc">Parte de un embudo probado y ajústalo. Puedes cambiar etapas y probabilidades antes de crear.</div>
            </div>
            <button type="button" class="pp-modal-close" id="ppTemplatesClose">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="pp-templates-body" id="ppTemplatesGrid">
            {{-- JS inyecta una tarjeta por plantilla --}}
        </div>
    </div>
</div>
