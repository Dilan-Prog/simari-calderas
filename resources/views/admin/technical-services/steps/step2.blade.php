<form id="ts-wizard-form"
      data-step="2"
      data-save-url="{{ route('admin.technical-services.save-step', [$service, 2]) }}"
      data-step-url="{{ route('admin.technical-services.step', [$service, '__STEP__']) }}"
      data-index-url="{{ route('admin.technical-services.index') }}"
      data-search-tech-url="{{ route('admin.technical-services.search-technicians') }}">

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
                <button type="button"
                        onclick="TechnicalServices.addTechnician({{ $tech->id }}, '{{ addslashes($tech->name) }}', '{{ addslashes($tech->email ?? '') }}')"
                        class="ts-btn" style="height:32px;font-size:0.75rem">
                    <div class="ts-tech-avatar" style="width:20px;height:20px;font-size:0.5625rem">
                        {{ mb_strtoupper(mb_substr($tech->name,0,1)) }}
                    </div>
                    {{ $tech->name }}
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
                <div class="ts-assigned-item" data-tech-id="{{ $tech->id }}">
                    <div class="ts-tech-avatar">{{ mb_strtoupper(mb_substr($tech->name,0,1)) }}</div>
                    <div style="flex:1">
                        <div class="ts-assigned-item__name">{{ $tech->name }}</div>
                        <div class="ts-assigned-item__role">{{ $tech->email ?? '' }}</div>
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
