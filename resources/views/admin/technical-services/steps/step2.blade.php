<script>
window.__tsConfig = {
    step: 2,
    isEdit: true,
    saveUrl: "{{ route('admin.technical-services.save-step', [$service, 2]) }}",
    stepUrl: "{{ route('admin.technical-services.step', [$service, '__STEP__']) }}",
    indexUrl: "{{ route('admin.technical-services.index') }}",
    searchTechUrl: "{{ route('admin.technical-services.search-technicians') }}"
};
</script>
<form id="ts-wizard-form">

    <div class="ts-card">
        <h2 class="ts-card__title">Asignación de Técnicos</h2>
        <p class="ts-card__subtitle">Selecciona los técnicos que realizarán el servicio</p>
        <div class="ts-card__divider"></div>

        {{-- Buscador de técnicos --}}
        <div style="margin-bottom:1rem">
            <label class="ts-label" style="display:block;margin-bottom:0.5rem">Buscar técnico</label>
            <div class="ts-tech-search-wrap">
                <svg class="ts-tech-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="ts-tech-search-input" class="ts-tech-search"
                       placeholder="Nombre del técnico..." autocomplete="off">
            </div>
            <div id="ts-tech-results" class="ts-tech-results">
                {{-- Populated by JS --}}
            </div>
        </div>

        {{-- Técnicos disponibles (fallback sin JS) --}}
        <details style="margin-bottom:1rem">
            <summary style="font-size:0.8125rem;cursor:pointer;color:var(--ts-text-muted);padding:0.5rem 0">
                Ver todos los técnicos disponibles
            </summary>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding-top:0.75rem">
                @foreach($technicians as $tech)
                @php $techName = trim($tech->first_name . ' ' . $tech->last_name); @endphp
                <button type="button"
                        onclick="TechnicalServices.addTechnician({{ $tech->id }}, '{{ addslashes($techName) }}', '{{ addslashes($tech->position ?? $tech->email ?? '') }}')"
                        class="ts-btn ts-tech-fallback-btn">
                    <div class="ts-tech-avatar" style="width:20px;height:20px;font-size:0.5625rem">
                        {{ mb_strtoupper(mb_substr($techName, 0, 1)) }}
                    </div>
                    {{ $techName }}
                </button>
                @endforeach
            </div>
        </details>

        {{-- Lista de asignados --}}
        <div>
            <label class="ts-label" style="display:block;margin-bottom:0.5rem">
                Técnicos asignados
            </label>
            <div id="ts-assigned-list" class="ts-assigned-list">
                @forelse($assignedTechnicians as $tech)
                @php $techName = trim($tech->first_name . ' ' . $tech->last_name); @endphp
                <div class="ts-assigned-item">
                    <div class="ts-tech-avatar">{{ mb_strtoupper(mb_substr($techName, 0, 1)) }}</div>
                    <div style="flex:1">
                        <div class="ts-assigned-item__name">{{ $techName }}</div>
                        <div class="ts-assigned-item__role">{{ $tech->position ?? $tech->email ?? '' }}</div>
                    </div>
                    <input type="hidden" name="technician_ids[]" value="{{ $tech->id }}">
                    <button type="button" class="ts-remove-tech"
                            onclick="TechnicalServices.removeTechnician(this)" title="Quitar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @empty
                <div class="ts-assigned-empty">Ningún técnico asignado aún</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Wizard footer --}}
    <div class="ts-wizard-footer">
        <button type="button" id="ts-btn-back" class="ts-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 19-7-7 7-7M19 12H5"/>
            </svg>
            Anterior
        </button>
        <button type="button" id="ts-btn-next" class="ts-btn ts-btn--primary">
            Siguiente: Materiales
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 14 0M13 6l6 6-6 6"/>
            </svg>
        </button>
    </div>

</form>
