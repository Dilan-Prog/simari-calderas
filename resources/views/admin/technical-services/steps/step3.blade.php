<form id="ts-wizard-form"
      data-step="3"
      data-save-url="{{ route('admin.technical-services.save-step', [$service, 3]) }}"
      data-step-url="{{ route('admin.technical-services.step', [$service, '__STEP__']) }}"
      data-index-url="{{ route('admin.technical-services.index') }}"
      data-search-tech-url="{{ route('admin.technical-services.search-technicians') }}">

    <div class="ts-card">
        <h2 class="ts-card__title">Materiales Planificados</h2>
        <p class="ts-card__subtitle">Lista de materiales necesarios para el servicio</p>
        <div class="ts-card__divider"></div>

        <div style="overflow-x:auto">
            <table class="ts-materials-table">
                <thead>
                    <tr>
                        <th style="width:40%">MATERIAL / DESCRIPCIÓN</th>
                        <th style="width:15%">CANTIDAD</th>
                        <th style="width:15%">UNIDAD</th>
                        <th style="width:25%">NOTAS</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody id="ts-materials-tbody">
                    @forelse($plannedMaterials as $i => $mat)
                    <tr>
                        <td>
                            <input type="text" name="materials[{{ $i }}][name]"
                                   class="ts-mat-input" placeholder="Nombre del material"
                                   value="{{ $mat->name ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="materials[{{ $i }}][quantity]"
                                   class="ts-mat-input" min="0" step="any" placeholder="0"
                                   value="{{ $mat->quantity ?? '' }}">
                        </td>
                        <td>
                            <input type="text" name="materials[{{ $i }}][unit]"
                                   class="ts-mat-input" placeholder="Unidad"
                                   value="{{ $mat->unit ?? '' }}">
                        </td>
                        <td>
                            <input type="text" name="materials[{{ $i }}][notes]"
                                   class="ts-mat-input" placeholder="Notas"
                                   value="{{ $mat->notes ?? '' }}">
                        </td>
                        <td>
                            <button type="button" class="ts-action-btn ts-action-btn--danger"
                                    onclick="this.closest('tr').remove()" title="Eliminar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td>
                            <input type="text" name="materials[0][name]"
                                   class="ts-mat-input" placeholder="Nombre del material">
                        </td>
                        <td>
                            <input type="number" name="materials[0][quantity]"
                                   class="ts-mat-input" min="0" step="any" placeholder="0">
                        </td>
                        <td>
                            <input type="text" name="materials[0][unit]"
                                   class="ts-mat-input" placeholder="Unidad">
                        </td>
                        <td>
                            <input type="text" name="materials[0][notes]"
                                   class="ts-mat-input" placeholder="Notas">
                        </td>
                        <td>
                            <button type="button" class="ts-action-btn ts-action-btn--danger"
                                    onclick="this.closest('tr').remove()" title="Eliminar">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <button type="button" id="ts-add-material-row" class="ts-btn-add-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5v14"/>
            </svg>
            Agregar fila
        </button>

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
            Siguiente: Confirmar
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 14 0M13 6l6 6-6 6"/>
            </svg>
        </button>
    </div>

</form>
