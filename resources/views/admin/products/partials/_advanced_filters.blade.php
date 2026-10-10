{{--
    Filtros avanzados compartidos por el listado de Productos y el editor en
    lote (se incluye DENTRO del <form class="prod-toolbar"> GET de cada uno).
    Lógica de filtrado: App\Services\Products\ProductFilters.

    $filtersKey: prefijo único por pantalla (para los ids y la llave de
    localStorage de los filtros guardados).
--}}
@php
    use App\Services\Products\ProductFilters as PF;

    $filtersKey = $filtersKey ?? 'prod';
    $afBrands = \App\Models\Brand::orderBy('name')->get(['id', 'name']);
    $afSuppliers = \App\Models\Supplier::orderBy('company_name')->get(['id', 'company_name']);
    $afChips = PF::activeChips(request());
    // Chips de filtros "avanzados" = todo lo que NO es búsqueda/estado/stock/categoría
    // (esos ya tienen su control visible en la barra), para el contador del botón.
    $afAdvancedCount = collect($afChips)->reject(fn ($c) => in_array($c['param'], ['search', 'status', 'stock', 'category_id'], true))->count();
    $afSel = fn (string $p, $v) => (string) request($p) === (string) $v ? 'selected' : '';
    $afBrandSel = array_map('strval', (array) request('brand_id', []));
    $afMissingSel = (array) request('missing', []);

    // URL que quita un chip: borra el parámetro (o solo ese valor en los de lista) y vuelve a la página 1.
    $afRemoveUrl = function (array $chip) {
        $q = request()->query();
        unset($q['page']);
        if ($chip['value'] !== null && isset($q[$chip['param']]) && is_array($q[$chip['param']])) {
            $q[$chip['param']] = array_values(array_diff($q[$chip['param']], [$chip['value']]));
            if (!$q[$chip['param']]) unset($q[$chip['param']]);
        } else {
            unset($q[$chip['param']]);
        }
        return request()->url() . ($q ? '?' . http_build_query($q) : '');
    };
@endphp

<div class="prod-adv-wrap" id="{{ $filtersKey }}AdvWrap">
    <button type="button" class="prod-filter-select prod-adv-btn" id="{{ $filtersKey }}AdvBtn" aria-expanded="false">
        Más filtros @if ($afAdvancedCount) <span class="prod-adv-count">{{ $afAdvancedCount }}</span>@endif
    </button>

    <div class="prod-adv-backdrop" id="{{ $filtersKey }}AdvBackdrop"></div>
    <div class="prod-adv-panel" id="{{ $filtersKey }}AdvPanel" role="dialog" aria-modal="true" aria-labelledby="{{ $filtersKey }}AdvTitle">
        <div class="prod-adv-head">
            <h3 id="{{ $filtersKey }}AdvTitle">Más filtros</h3>
            <button type="button" class="prod-adv-close" id="{{ $filtersKey }}AdvClose" aria-label="Cerrar filtros">✕</button>
        </div>
        <div class="prod-adv-body">
        <div class="prod-adv-grid">
            {{-- Marcas como casillas (antes un <select multiple size=5> que cortaba la última opción
                 a la mitad y obligaba a hacer scroll para ver las 11 marcas). --}}
            <div class="prod-adv-field prod-adv-field--brands" role="group" aria-label="Marca (varias)">Marca (varias)
                <div class="prod-adv-brands">
                    @foreach ($afBrands as $b)
                        <label class="prod-adv-check">
                            <input type="checkbox" name="brand_id[]" value="{{ $b->id }}" @checked(in_array((string) $b->id, $afBrandSel, true))> {{ $b->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <label class="prod-adv-field">Proveedor
                <select name="supplier_id" class="prod-adv-input">
                    <option value="">Todos</option>
                    @foreach ($afSuppliers as $s)
                        <option value="{{ $s->id }}" {{ $afSel('supplier_id', $s->id) }}>{{ $s->company_name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="prod-adv-field">Moneda
                <select name="currency" class="prod-adv-input">
                    <option value="">Todas</option>
                    <option value="MXN" {{ $afSel('currency', 'MXN') }}>MXN</option>
                    <option value="USD" {{ $afSel('currency', 'USD') }}>USD</option>
                </select>
            </label>

            <label class="prod-adv-field">Disponibilidad
                <select name="availability" class="prod-adv-input">
                    <option value="">Todas</option>
                    @foreach (PF::AVAILABILITY as $k => $label)
                        <option value="{{ $k }}" {{ $afSel('availability', $k) }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="prod-adv-field">Precio
                <div class="prod-adv-range">
                    <input type="text" inputmode="decimal" name="price_min" class="prod-adv-input" placeholder="Desde" value="{{ request('price_min') }}">
                    <input type="text" inputmode="decimal" name="price_max" class="prod-adv-input" placeholder="Hasta" value="{{ request('price_max') }}">
                </div>
            </div>
            <div class="prod-adv-field">Costo
                <div class="prod-adv-range">
                    <input type="text" inputmode="decimal" name="cost_min" class="prod-adv-input" placeholder="Desde" value="{{ request('cost_min') }}">
                    <input type="text" inputmode="decimal" name="cost_max" class="prod-adv-input" placeholder="Hasta" value="{{ request('cost_max') }}">
                </div>
            </div>
            <div class="prod-adv-field">Stock (unidades)
                <div class="prod-adv-range">
                    <input type="text" inputmode="numeric" name="stock_min" class="prod-adv-input" placeholder="Desde" value="{{ request('stock_min') }}">
                    <input type="text" inputmode="numeric" name="stock_max" class="prod-adv-input" placeholder="Hasta" value="{{ request('stock_max') }}">
                </div>
            </div>

            <div class="prod-adv-field">Creado
                <div class="prod-adv-range">
                    <input type="date" name="created_from" class="prod-adv-input" value="{{ request('created_from') }}">
                    <input type="date" name="created_to" class="prod-adv-input" value="{{ request('created_to') }}">
                </div>
            </div>
            <div class="prod-adv-field">Actualizado
                <div class="prod-adv-range">
                    <input type="date" name="updated_from" class="prod-adv-input" value="{{ request('updated_from') }}">
                    <input type="date" name="updated_to" class="prod-adv-input" value="{{ request('updated_to') }}">
                </div>
            </div>

            <label class="prod-adv-field">Etiqueta
                <input type="text" name="tag" class="prod-adv-input" placeholder="Ej: oferta" maxlength="60" value="{{ request('tag') }}">
            </label>

            @foreach (PF::FLAGS as $param => [, $label])
                <label class="prod-adv-field">{{ $label }}
                    <select name="{{ $param }}" class="prod-adv-input">
                        <option value="">Indistinto</option>
                        <option value="1" {{ $afSel($param, '1') }}>Sí</option>
                        <option value="0" {{ $afSel($param, '0') }}>No</option>
                    </select>
                </label>
            @endforeach
        </div>

        <fieldset class="prod-adv-missing">
            <legend>Datos faltantes (calidad del catálogo)</legend>
            @foreach (PF::MISSING as $k => $label)
                <label class="prod-adv-check">
                    <input type="checkbox" name="missing[]" value="{{ $k }}" @checked(in_array($k, $afMissingSel, true))> {{ $label }}
                </label>
            @endforeach
        </fieldset>

        <div class="prod-adv-saved">
            <select id="{{ $filtersKey }}SavedSelect" class="prod-adv-input">
                <option value="">Filtros guardados…</option>
            </select>
            <button type="button" class="button-secondary size-adjustment" id="{{ $filtersKey }}SavedSave">Guardar filtros actuales</button>
            <button type="button" class="button-secondary size-adjustment" id="{{ $filtersKey }}SavedDelete" style="display:none">Eliminar</button>
        </div>

        </div>{{-- /.prod-adv-body --}}

        <div class="prod-adv-actions">
            <a href="{{ request()->url() }}" class="button-secondary size-adjustment">Limpiar todo</a>
            <button type="submit" class="pform-btn primary">Aplicar filtros</button>
        </div>
    </div>
</div>

@if ($afChips)
    <div class="prod-adv-chips">
        @foreach ($afChips as $chip)
            <a href="{{ $afRemoveUrl($chip) }}" class="prod-adv-chip" title="Quitar filtro">{{ $chip['label'] }} <span aria-hidden="true">×</span></a>
        @endforeach
        <a href="{{ request()->url() }}" class="prod-adv-chip prod-adv-chip--clear">Limpiar todo</a>
    </div>
@endif

<script>
    (function () {
        const key = @json($filtersKey);
        const wrap = document.getElementById(key + 'AdvWrap');
        const btn = document.getElementById(key + 'AdvBtn');
        const panel = document.getElementById(key + 'AdvPanel');
        if (!wrap || !btn || !panel) return;
        const form = wrap.closest('form');

        const backdrop = document.getElementById(key + 'AdvBackdrop');
        const closeBtn = document.getElementById(key + 'AdvClose');

        function setOpen(open) {
            panel.classList.toggle('active', open);
            if (backdrop) backdrop.classList.toggle('active', open);
            document.documentElement.classList.toggle('prod-adv-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { const first = panel.querySelector('input, select'); if (first) first.focus({ preventScroll: true }); }
            else if (document.activeElement && panel.contains(document.activeElement)) { btn.focus({ preventScroll: true }); }
        }

        btn.addEventListener('click', () => setOpen(!panel.classList.contains('active')));
        if (closeBtn) closeBtn.addEventListener('click', () => setOpen(false));
        if (backdrop) backdrop.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && panel.classList.contains('active')) setOpen(false); });

        // Enter dentro de un campo del panel aplica los filtros (comportamiento normal del form);
        // el panel nunca debe dispararse por la búsqueda con retardo, ya la maneja cada pantalla.

        // ── Filtros guardados (por navegador, en localStorage) ──
        const LS = 'productFilters:' + key;
        const sel = document.getElementById(key + 'SavedSelect');
        const saveBtn = document.getElementById(key + 'SavedSave');
        const delBtn = document.getElementById(key + 'SavedDelete');
        const read = () => { try { return JSON.parse(localStorage.getItem(LS) || '[]'); } catch (e) { return []; } };
        const write = (v) => { try { localStorage.setItem(LS, JSON.stringify(v)); } catch (e) {} };
        const IGNORED = ['page', 'per_page'];

        function currentQuery() {
            const p = new URLSearchParams(new FormData(form));
            const out = new URLSearchParams();
            p.forEach((v, k) => { if (v !== '' && v !== 'all' && !IGNORED.includes(k)) out.append(k, v); });
            return out.toString();
        }
        function render() {
            const items = read();
            sel.innerHTML = '<option value="">Filtros guardados…</option>' +
                items.map((it, i) => '<option value="' + i + '"></option>').join('');
            items.forEach((it, i) => { sel.options[i + 1].textContent = it.name; });
            delBtn.style.display = 'none';
        }
        sel.addEventListener('change', () => {
            const items = read();
            const it = items[parseInt(sel.value, 10)];
            delBtn.style.display = it ? '' : 'none';
            if (!it) return;
            const per = new URLSearchParams(location.search).get('per_page');
            const q = new URLSearchParams(it.query);
            if (per) q.set('per_page', per);
            location.href = location.pathname + '?' + q.toString();
        });
        saveBtn.addEventListener('click', () => {
            const query = currentQuery();
            if (!query) { alert('No hay filtros activos para guardar.'); return; }
            const name = (prompt('Nombre para estos filtros:') || '').trim().slice(0, 60);
            if (!name) return;
            const items = read().filter((it) => it.name !== name);
            items.push({ name, query });
            write(items.slice(-20));
            render();
        });
        delBtn.addEventListener('click', () => {
            const items = read();
            const i = parseInt(sel.value, 10);
            if (isNaN(i)) return;
            items.splice(i, 1);
            write(items);
            render();
        });
        render();
    })();
</script>
