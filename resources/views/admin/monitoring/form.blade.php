@extends('admin.layouts.master')
@section('title')
    {{ $program->exists ? 'Editar programa' : 'Nuevo programa' }} - Monitoreo de Clientes - Admin
@endsection
@push('styles')
    @include('admin.monitoring._styles')
@endpush
@section('content')
@php
    use App\Models\Products;

    $isEdit = $program->exists;
    $customerId = old('customer_id', $program->customer_id);

    // Filas del repetidor: de old() si hubo error de validación, si no de la BD.
    $oldItems = old('items');
    if (is_array($oldItems)) {
        $byId = $items->keyBy('id');
        $productIds = collect($oldItems)->pluck('product_id')->filter()->unique();
        $productNames = $productIds->isNotEmpty() ? Products::whereIn('id', $productIds)->pluck('name', 'id') : collect();
        $rows = collect($oldItems)->map(function ($r) use ($byId, $productNames) {
            $db = ! empty($r['id']) ? $byId->get((int) $r['id']) : null;
            return $r + [
                'added_by'      => $db?->added_by ?? 'staff',
                'entries_count' => $db?->entries_count ?? 0,
                'product_label' => ! empty($r['product_id']) ? ($productNames[(int) $r['product_id']] ?? '') : '',
            ];
        })->values()->all();
    } else {
        $rows = $items->map(fn ($it) => [
            'id' => $it->id, 'kind' => $it->kind, 'name' => $it->name, 'unit' => $it->unit,
            'limit_min' => $it->limit_min, 'limit_max' => $it->limit_max,
            'product_id' => $it->product_id, 'product_label' => $it->product?->name,
            'planned_daily_dose' => $it->planned_daily_dose,
            'start_week' => $it->start_week, 'end_week' => $it->end_week,
            'added_by' => $it->added_by, 'entries_count' => $it->entries_count ?? 0,
        ])->values()->all();
    }
    $startValue = old('start_date', $program->start_date?->format('Y-m-d'));
@endphp
<div class="erp-page mon-page">
    <x-erp.page-header
        :title="$isEdit ? 'Editar programa' : 'Nuevo programa de monitoreo'"
        desc="Define el periodo, los parámetros del agua a medir y los químicos a dosificar. Las semanas inicio/fin de cada elemento forman las barras del diagrama de Gantt."
        :crumbs="[['Producción', null], ['Monitoreo de Clientes', route('admin.monitoring.index')], [$isEdit ? $program->name : 'Nuevo programa', null]]" />

    @if ($errors->any())
        <div class="mon-flash mon-flash--err" role="alert">
            Revisa los campos:
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.monitoring.update', $program) : route('admin.monitoring.store') }}" id="monForm">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <input type="hidden" name="confirm_cascade" value="0" id="monConfirmCascade">

        <x-erp.card title="General">
            <div class="erp-form-grid">
                <label class="erp-field mon-rel" id="monCustomerBox" data-url="{{ route('admin.monitoring.customers.search') }}" style="grid-column: span 2;">Cliente *
                    <input type="hidden" name="customer_id" value="{{ $customerId }}" id="monCustomerId">
                    <input type="text" class="erp-input" id="monCustomerInput" placeholder="Buscar cliente por nombre, empresa o correo…" autocomplete="off" value="{{ $customerLabel }}">
                    <div class="mon-suggest" id="monCustomerList" hidden></div>
                    <small>Escribe al menos 2 letras y elige un cliente de la lista.</small>
                </label>
                <label class="erp-field" style="grid-column: span 2;">Nombre del programa *
                    <input type="text" name="name" class="erp-input" maxlength="150" required value="{{ old('name', $program->name) }}" placeholder="Ej. Monitoreo caldera Planta 1 – octubre">
                </label>
                <label class="erp-field">Fecha de inicio *
                    <input type="date" name="start_date" class="erp-input" required value="{{ $startValue }}">
                </label>
                <label class="erp-field">Semanas *
                    <input type="number" name="weeks" id="monWeeks" class="erp-input" min="1" max="104" required value="{{ old('weeks', $program->weeks) }}">
                </label>
                <label class="erp-field">Estado
                    <select name="status" class="erp-select">
                        @foreach ($statuses as $k => $label)
                            <option value="{{ $k }}" @selected(old('status', $program->status) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field" style="grid-column: 1 / -1;">Notas internas
                    <textarea name="notes" rows="2" class="erp-input" maxlength="5000">{{ old('notes', $program->notes) }}</textarea>
                </label>
            </div>
        </x-erp.card>

        <x-erp.card title="Elementos del programa" sub="Parámetros del agua (con rango aceptable) y químicos (con dosis diaria planeada).">
            <x-slot:actions>
                <div class="mon-presets">
                    @foreach ($presets as $key => $preset)
                        <button type="button" class="erp-btn erp-btn--sm" data-preset="{{ $key }}">+ Sugerencia: {{ $preset['label'] }}</button>
                    @endforeach
                    <button type="button" class="erp-btn erp-btn--sm" data-add-item="parameter">+ Parámetro</button>
                    <button type="button" class="erp-btn erp-btn--sm" data-add-item="chemical">+ Químico</button>
                </div>
            </x-slot:actions>

            <div id="monItems">
                @foreach ($rows as $i => $row)
                    @include('admin.monitoring._item_row', ['i' => $i, 'row' => $row])
                @endforeach
            </div>
            <p class="mon-muted" id="monItemsEmpty" @if (count($rows)) hidden @endif>
                Aún no hay elementos. Usa una sugerencia (editable) o agrega parámetros y químicos manualmente.
            </p>
            <template id="monItemTemplate">
                @include('admin.monitoring._item_row', ['i' => '__IDX__', 'row' => []])
            </template>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ $isEdit ? route('admin.monitoring.show', $program) : route('admin.monitoring.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $isEdit ? 'Guardar cambios' : 'Crear programa' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const PRESETS = @json($presets);
    const productSearchUrl = @json(route('admin.monitoring.products.search'));
    const wrap = document.getElementById('monItems');
    const tpl = document.getElementById('monItemTemplate').innerHTML;
    const empty = document.getElementById('monItemsEmpty');
    const weeksInput = document.getElementById('monWeeks');
    let counter = {{ count($rows) }} + 1000;

    const debounce = (fn, ms = 250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    const weeks = () => Math.max(1, parseInt(weeksInput.value, 10) || 1);
    const refreshEmpty = () => { empty.hidden = wrap.children.length > 0; };

    function setField(el, selector, value) {
        const f = el.querySelector(selector);
        if (f && value !== undefined && value !== null) f.value = value;
    }

    function addItem(data) {
        const idx = counter++;
        const holder = document.createElement('div');
        holder.innerHTML = tpl.replace(/__IDX__/g, idx).trim();
        const el = holder.firstElementChild;
        const kind = data.kind || 'parameter';
        el.dataset.kind = kind;
        el.querySelector('[data-kind-select]').value = kind;
        setField(el, '[data-name]', data.name || '');
        setField(el, '[data-unit]', data.unit || '');
        setField(el, '[data-start]', data.start_week || 1);
        setField(el, '[data-end]', data.end_week || weeks());
        setField(el, 'input[name$="[limit_min]"]', data.limit_min ?? '');
        setField(el, 'input[name$="[limit_max]"]', data.limit_max ?? '');
        setField(el, 'input[name$="[planned_daily_dose]"]', data.planned_daily_dose ?? '');
        wrap.appendChild(el);
        refreshEmpty();
        return el;
    }

    document.querySelectorAll('[data-add-item]').forEach((b) => b.addEventListener('click', () => {
        const el = addItem({ kind: b.dataset.addItem });
        el.querySelector('[data-name]').focus();
    }));

    document.querySelectorAll('[data-preset]').forEach((b) => b.addEventListener('click', () => {
        const preset = PRESETS[b.dataset.preset];
        if (!preset) return;
        const existing = new Set([...wrap.querySelectorAll('[data-name]')].map((i) => i.value.trim().toLowerCase()));
        let added = 0;
        preset.items.forEach((it) => {
            if (existing.has(it.name.toLowerCase())) return;
            addItem(it);
            added++;
        });
        if (typeof showCenterToast === 'function') {
            showCenterToast(added ? ('Se agregaron ' + added + ' elementos sugeridos. Edítalos a tu gusto.') : 'Esa sugerencia ya está incluida.');
        }
    }));

    wrap.addEventListener('change', (e) => {
        if (e.target.matches('[data-kind-select]')) e.target.closest('[data-item]').dataset.kind = e.target.value;
    });

    wrap.addEventListener('click', (e) => {
        const item = e.target.closest('[data-item]');
        if (!item) return;
        if (e.target.closest('[data-item-remove]')) {
            const n = parseInt(item.dataset.entries, 10) || 0;
            if (n > 0) {
                if (!confirm('Este elemento tiene ' + n + ' registro(s). Si lo quitas se eliminarán también sus registros al guardar. ¿Continuar?')) return;
                document.getElementById('monConfirmCascade').value = '1';
            }
            item.remove();
            refreshEmpty();
        } else if (e.target.closest('[data-item-up]') && item.previousElementSibling) {
            wrap.insertBefore(item, item.previousElementSibling);
        } else if (e.target.closest('[data-item-down]') && item.nextElementSibling) {
            wrap.insertBefore(item.nextElementSibling, item);
        }
    });

    // Autocompletado de producto (delegado)
    const productSearch = debounce(async (input) => {
        const box = input.closest('.mon-rel');
        const list = box.querySelector('[data-product-list]');
        const q = input.value.trim();
        if (q.length < 2) { list.hidden = true; return; }
        try {
            const res = await fetch(productSearchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const rows = await res.json();
            list.innerHTML = '';
            rows.forEach((p) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = p.name + (p.sku ? ' · ' + p.sku : '');
                b.addEventListener('click', () => {
                    const item = input.closest('[data-item]');
                    box.querySelector('[data-product-id]').value = p.id;
                    input.value = p.name;
                    const name = item.querySelector('[data-name]');
                    const unit = item.querySelector('[data-unit]');
                    if (!name.value.trim()) name.value = p.name;
                    if (!unit.value.trim() && p.unit) unit.value = p.unit;
                    list.hidden = true;
                });
                list.appendChild(b);
            });
            list.hidden = rows.length === 0;
        } catch (err) { list.hidden = true; }
    });
    wrap.addEventListener('input', (e) => {
        if (e.target.matches('[data-product-input]')) {
            e.target.closest('.mon-rel').querySelector('[data-product-id]').value = '';
            productSearch(e.target);
        }
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.mon-rel')) document.querySelectorAll('.mon-suggest').forEach((l) => { l.hidden = true; });
    });

    // Selector de cliente
    const cBox = document.getElementById('monCustomerBox');
    const cInput = document.getElementById('monCustomerInput');
    const cId = document.getElementById('monCustomerId');
    const cList = document.getElementById('monCustomerList');
    const customerSearch = debounce(async () => {
        const q = cInput.value.trim();
        if (q.length < 2) { cList.hidden = true; return; }
        try {
            const res = await fetch(cBox.dataset.url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const rows = await res.json();
            cList.innerHTML = '';
            rows.forEach((c) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = c.name + (c.company ? ' · ' + c.company : '');
                b.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    cId.value = c.id;
                    cInput.value = b.textContent;
                    cList.hidden = true;
                });
                cList.appendChild(b);
            });
            cList.hidden = rows.length === 0;
        } catch (err) { cList.hidden = true; }
    });
    cInput.addEventListener('input', () => { cId.value = ''; customerSearch(); });

    document.getElementById('monForm').addEventListener('submit', (e) => {
        if (!cId.value) {
            e.preventDefault();
            cInput.focus();
            alert('Elige un cliente de la lista de resultados.');
        }
    });
})();
</script>
@include('admin.components.center-toast')
@endpush
