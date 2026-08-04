{{--
    Menú genérico "mostrar/ocultar columnas" — reutilizable en cualquier
    tabla de listado del admin. Persiste la preferencia por usuario en el
    servidor (tabla user_column_preferences), vía column-visibility.js.

    Variables esperadas:
    - $tableKey: string — una de las claves registradas en
      ColumnPreferenceController::VALID_TABLE_KEYS (p. ej. 'clients.index').
    - $columnDefs: array asociativo ['clave_columna' => 'Etiqueta visible', ...]
      en el orden en que deben listarse. Las columnas "esenciales" (nombre/
      folio, acciones) no deben incluirse aquí — van fijas en la tabla, sin
      toggle, igual que Nombre/SKU en la cuadrícula de edición masiva de
      Productos.

    Este partial solo arma el botón + menú de checkboxes. La lógica de
    aplicar/ocultar columnas y guardar la preferencia vive en
    public/js/admin/column-visibility.js — cada vista que incluya este
    partial debe también invocar initColumnVisibility({...}) con su
    table_key, columnas guardadas del servidor y la URL de guardado.
--}}
@php
    $columnDefs = $columnDefs ?? [];
@endphp
<div class="col-vis-wrap" data-table-key="{{ $tableKey }}">
    <button type="button" class="button-secondary size-adjustment col-vis-btn" id="colVisBtn-{{ $tableKey }}">
        Columnas
    </button>
    <div class="col-vis-menu" id="colVisMenu-{{ $tableKey }}"
        style="display:none; position:absolute; z-index:50; background:#fff; border:1px solid #d1d5db; border-radius:6px; padding:12px; min-width:220px; box-shadow:0 4px 12px rgba(0,0,0,0.12);">
        @foreach ($columnDefs as $colKey => $colLabel)
            <label class="col-vis-item" style="display:flex; align-items:center; gap:8px; padding:4px 0;">
                <input type="checkbox" class="col-vis-checkbox" data-col-key="{{ $colKey }}" checked>
                <span>{{ $colLabel }}</span>
            </label>
        @endforeach
    </div>
</div>
