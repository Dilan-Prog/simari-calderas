@php
    /** @var array $row  id, kind, name, unit, limit_min, limit_max, product_id, product_label, planned_daily_dose, start_week, end_week, added_by, entries_count */
    $kind = $row['kind'] ?? 'parameter';
    $fromCustomer = ($row['added_by'] ?? 'staff') === 'customer';
    $entriesCount = (int) ($row['entries_count'] ?? 0);
    $n = fn ($v) => ($v === null || $v === '') ? '' : (float) $v + 0;
@endphp
<div class="mon-item {{ $fromCustomer ? 'is-customer' : '' }}" data-item data-kind="{{ $kind }}" data-entries="{{ $entriesCount }}">
    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

    <div class="mon-item-top">
        <select name="items[{{ $i }}][kind]" class="erp-select" data-kind-select aria-label="Tipo de elemento">
            <option value="parameter" @selected($kind === 'parameter')>Parámetro del agua</option>
            <option value="chemical" @selected($kind === 'chemical')>Químico</option>
        </select>
        @if ($fromCustomer)<span class="mon-badge mon-badge--cust">Agregado por el cliente</span>@endif
        @if ($entriesCount > 0)<span class="mon-badge">{{ $entriesCount }} {{ $entriesCount === 1 ? 'registro' : 'registros' }}</span>@endif
        <span style="margin-left:auto;display:flex;gap:4px;">
            <button type="button" class="erp-btn erp-btn--sm" data-item-up title="Subir" aria-label="Subir">↑</button>
            <button type="button" class="erp-btn erp-btn--sm" data-item-down title="Bajar" aria-label="Bajar">↓</button>
            <button type="button" class="erp-btn erp-btn--sm erp-btn--danger" data-item-remove>Quitar</button>
        </span>
    </div>

    <div class="mon-item-grid">
        <label class="erp-field c3">Nombre *
            <input type="text" name="items[{{ $i }}][name]" class="erp-input" maxlength="120" required value="{{ $row['name'] ?? '' }}" data-name placeholder="Ej. pH, Dureza, Inhibidor de corrosión">
        </label>
        <label class="erp-field">Unidad
            <input type="text" name="items[{{ $i }}][unit]" class="erp-input" maxlength="20" value="{{ $row['unit'] ?? '' }}" data-unit placeholder="ppm, L, kg">
        </label>
        <label class="erp-field">Semana inicio *
            <input type="number" name="items[{{ $i }}][start_week]" class="erp-input" min="1" required value="{{ $row['start_week'] ?? 1 }}" data-start>
        </label>
        <label class="erp-field">Semana fin *
            <input type="number" name="items[{{ $i }}][end_week]" class="erp-input" min="1" required value="{{ $row['end_week'] ?? 1 }}" data-end>
        </label>

        <label class="erp-field c2" data-only="parameter">Límite mínimo
            <input type="number" step="any" name="items[{{ $i }}][limit_min]" class="erp-input" value="{{ $n($row['limit_min'] ?? null) }}" placeholder="Sin mínimo">
        </label>
        <label class="erp-field c2" data-only="parameter">Límite máximo
            <input type="number" step="any" name="items[{{ $i }}][limit_max]" class="erp-input" value="{{ $n($row['limit_max'] ?? null) }}" placeholder="Sin máximo">
        </label>

        <div class="erp-field c3 mon-rel" data-only="chemical">
            <span>Producto del catálogo (opcional)</span>
            <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $row['product_id'] ?? '' }}" data-product-id>
            <input type="text" class="erp-input" autocomplete="off" placeholder="Buscar por nombre o SKU…" value="{{ $row['product_label'] ?? '' }}" data-product-input>
            <div class="mon-suggest" data-product-list hidden></div>
        </div>
        <label class="erp-field c2" data-only="chemical">Dosis diaria planeada
            <input type="number" step="any" min="0" name="items[{{ $i }}][planned_daily_dose]" class="erp-input" value="{{ $n($row['planned_daily_dose'] ?? null) }}" placeholder="Por día">
        </label>
    </div>
</div>
