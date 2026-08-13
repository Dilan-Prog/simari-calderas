import Chart from 'chart.js/auto';

/**
 * Motor de render del panel reutilizable del módulo Estadísticas.
 *
 * Cada `.stats-panel[data-panel-id]` trae su configuración+datos ya
 * resueltos por el servicio de dominio correspondiente, embebidos como
 * `<script type="application/json" id="stats-panel-data-{id}">` (mismo
 * patrón que resources/js/admin/dashboard-grid.js usa con renderChart()).
 * Cambiar de modo (donut/bars/table/line/columns/rank/stacked) NUNCA
 * dispara una petición al servidor — todas las representaciones se pintan
 * del mismo payload ya cargado. El modo elegido se recuerda en
 * localStorage por panel (no hay tabla nueva en BD para esto).
 */

const COLORS = {
    ok: '#16a34a', warn: '#d97706', err: '#dc2626',
    info: '#3b82f6', violet: '#a855f7', brand: '#ff6213', gray: '#9CA3AF',
    // Claves de segmento usadas por paneles rank que también declaran modos
    // bars/table (ej. VentasStatsService::rankingClientesPanel) — sin esto,
    // rowsWithColor() cae a gray para ambos segmentos y se pierde la
    // distinción visual fuera del modo rank.
    'Empresa (B2B)': '#ff6213', 'Particular': '#3b82f6',
};

const MODE_LABEL = {
    donut: 'Dona', bars: 'Barra horizontal', table: 'Tabla',
    line: 'Línea', columns: 'Barra vertical', rank: 'Ranking', stacked: 'Barra apilada',
};

function money(n) {
    return '$' + Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
}

function fmtValue(v, isMoney) {
    return isMoney ? money(v) : String(v);
}

function modeStorageKey(panelId) {
    return 'statistics_panel_mode_' + panelId;
}

function getStoredMode(panelId, modes) {
    try {
        const stored = window.localStorage.getItem(modeStorageKey(panelId));
        if (stored && modes.indexOf(stored) !== -1) return stored;
    } catch (e) { /* localStorage no disponible, usar default */ }
    return modes[0];
}

function setStoredMode(panelId, mode) {
    try { window.localStorage.setItem(modeStorageKey(panelId), mode); } catch (e) { /* noop */ }
}

function getStoredRankCfg(panelId) {
    const defaults = { limit: 10, order: 'desc', sort: 'value', filter: 'Todos' };
    try {
        const stored = window.localStorage.getItem('statistics_panel_rank_' + panelId);
        if (!stored) return defaults;
        const parsed = JSON.parse(stored);
        // Merge campo por campo con validación de tipo — un valor guardado
        // con forma vieja/incompleta/corrupta no debe filtrar el ranking a 0
        // filas silenciosamente (ej. filter=undefined !== 'Todos').
        return {
            limit: [10, 20].indexOf(parsed.limit) !== -1 ? parsed.limit : defaults.limit,
            order: parsed.order === 'asc' || parsed.order === 'desc' ? parsed.order : defaults.order,
            sort: parsed.sort === 'label' || parsed.sort === 'value' ? parsed.sort : defaults.sort,
            filter: typeof parsed.filter === 'string' ? parsed.filter : defaults.filter,
        };
    } catch (e) { /* localStorage corrupto o JSON inválido, usar default */ }
    return defaults;
}

function setStoredRankCfg(panelId, cfg) {
    try { window.localStorage.setItem('statistics_panel_rank_' + panelId, JSON.stringify(cfg)); } catch (e) { /* noop */ }
}

class StatisticsPanel {
    constructor(el) {
        this.el = el;
        this.id = el.dataset.panelId;
        this.body = el.querySelector('.stats-panel-body');
        this.modesWrap = el.querySelector('.stats-panel-modes');
        this.chart = null;

        const dataScript = document.getElementById('stats-panel-data-' + this.id);
        try {
            this.config = dataScript ? JSON.parse(dataScript.textContent) : { modes: ['table'], rows: [] };
        } catch (e) {
            // JSON inválido embebido (ej. json_encode() devolvió false por
            // UTF-8 inválido en un campo de texto libre capturado por el
            // usuario) — degrada este panel a error en vez de tirar la
            // excepción hacia afuera y abortar el resto de initAll().
            this.config = { modes: ['table'], rows: [], state: 'error' };
        }

        this.mode = getStoredMode(this.id, this.config.modes && this.config.modes.length ? this.config.modes : ['table']);
        this.rankCfg = getStoredRankCfg(this.id);

        this.renderModeButtons();
        this.render();
    }

    renderModeButtons() {
        if (!this.modesWrap || !this.config.modes) return;
        this.modesWrap.innerHTML = '';
        this.config.modes.forEach((m) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'stats-mode-btn' + (m === this.mode ? ' is-active' : '');
            btn.title = MODE_LABEL[m] || m;
            btn.dataset.mode = m;
            btn.innerHTML = this.modeIcon(m);
            btn.addEventListener('click', () => {
                if (this.mode === m) return;
                this.mode = m;
                setStoredMode(this.id, m);
                this.renderModeButtons();
                this.render();
            });
            this.modesWrap.appendChild(btn);
        });
    }

    modeIcon(kind) {
        const icons = {
            donut: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>',
            bars: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h13M4 12h9M4 18h16"/></svg>',
            table: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 5.5h18v13H3zM3 10h18M9.5 10v8.5"/></svg>',
            line: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 17l5-6 4 3 7-8"/></svg>',
            columns: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>',
            rank: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h4M4 12h4M4 18h4M11 6h9M11 12h9M11 18h9"/></svg>',
            stacked: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 8.5h18v7H3zM12 8.5v7"/></svg>',
        };
        return icons[kind] || icons.bars;
    }

    destroyChart() {
        if (this.chart) { this.chart.destroy(); this.chart = null; }
    }

    render() {
        this.destroyChart();
        const c = this.config;

        if (c.state === 'empty') return this.renderEmpty();
        if (c.state === 'error') return this.renderError();
        if (c.state === 'loading') return this.renderLoading();

        switch (this.mode) {
            case 'donut': return this.renderDonut();
            case 'line': return this.renderLine();
            case 'columns': return this.renderColumns();
            case 'bars': return this.renderBars();
            case 'rank': return this.renderRank();
            case 'stacked': return this.renderStacked();
            default: return this.renderTable();
        }
    }

    rowsWithColor() {
        return (this.config.rows || []).map((r) => ({
            label: r[0], value: r[1], color: COLORS[r[2]] || COLORS.gray,
        }));
    }

    renderEmpty() {
        this.body.innerHTML =
            '<div class="stats-panel-state">' +
            '<div class="stats-panel-state-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V6M4 19h16M8 16V11M12 16V8M16 16v-3" stroke-linecap="round"/></svg></div>' +
            '<div class="stats-panel-state-title">Sin datos en el rango seleccionado</div>' +
            '<div class="stats-panel-state-text">Amplía el periodo o quita filtros para ver resultados.</div>' +
            '</div>';
    }

    renderLoading() {
        this.body.innerHTML =
            '<div class="stats-panel-skeleton">' +
            '<div class="stats-skeleton-line" style="width:82%"></div>' +
            '<div class="stats-skeleton-line" style="width:64%"></div>' +
            '<div class="stats-skeleton-line" style="width:74%"></div>' +
            '<div class="stats-panel-state-text">Consultando…</div>' +
            '</div>';
    }

    renderError() {
        this.body.innerHTML =
            '<div class="stats-panel-state">' +
            '<div class="stats-panel-state-icon stats-panel-state-icon--err"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v5M12 17h.01M10.3 3.9L2.5 18a1.8 1.8 0 001.6 2.7h15.8a1.8 1.8 0 001.6-2.7L13.7 3.9a1.8 1.8 0 00-3.4 0z" stroke-linecap="round" stroke-linejoin="round"/></svg></div>' +
            '<div class="stats-panel-state-title">No se pudo cargar el panel</div>' +
            '<div class="stats-panel-state-text">Los demás paneles siguen funcionando con normalidad.</div>' +
            '</div>';
    }

    renderDonut() {
        const rows = this.rowsWithColor();
        const total = rows.reduce((a, r) => a + r.value, 0);
        this.body.innerHTML =
            '<div class="stats-donut-wrap">' +
            '<div class="stats-donut-canvas-wrap"><canvas></canvas></div>' +
            '<div class="stats-donut-legend">' +
            rows.map((r) => {
                const pct = total ? Math.round((r.value / total) * 100) : 0;
                return '<div class="stats-legend-row"><span class="stats-legend-dot" style="background:' + r.color + '"></span>' +
                    '<span class="stats-legend-label">' + this.escape(r.label) + '</span>' +
                    '<span class="stats-legend-value">' + this.escape(fmtValue(r.value, this.config.money)) + '</span>' +
                    '<span class="stats-legend-pct">' + pct + '%</span></div>';
            }).join('') +
            '</div></div>';

        const canvas = this.body.querySelector('canvas');
        this.chart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: rows.map((r) => r.label),
                datasets: [{ data: rows.map((r) => r.value), backgroundColor: rows.map((r) => r.color), borderWidth: 0 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: { legend: { display: false } },
            },
        });
    }

    renderBars() {
        const rows = this.rowsWithColor();
        const max = Math.max(1, ...rows.map((r) => r.value));
        const total = rows.reduce((a, r) => a + r.value, 0);
        this.body.innerHTML = '<div class="stats-bars-wrap">' +
            rows.map((r) => {
                const pct = this.config.noPct || !total ? '' : Math.round((r.value / total) * 100) + '%';
                const width = (r.value / max * 100).toFixed(1) + '%';
                return '<div class="stats-bar-row">' +
                    '<div class="stats-bar-head"><span class="stats-bar-label">' + this.escape(r.label) + '</span>' +
                    '<span class="stats-bar-value">' + this.escape(fmtValue(r.value, this.config.money)) + '</span>' +
                    '<span class="stats-bar-pct">' + pct + '</span></div>' +
                    '<div class="stats-bar-track"><div class="stats-bar-fill" style="width:' + width + ';background:' + r.color + '"></div></div>' +
                    '</div>';
            }).join('') + '</div>';
    }

    renderTable() {
        const rows = this.rowsWithColor();
        const total = rows.reduce((a, r) => a + r.value, 0);
        this.body.innerHTML = '<div class="stats-table-wrap">' +
            '<div class="stats-table-head"><div>' + this.escape(this.config.colLabel || 'Concepto') + '</div>' +
            '<div class="stats-table-num">' + this.escape(this.config.colValue || 'Valor') + '</div>' +
            (this.config.noPct ? '' : '<div class="stats-table-num">%</div>') + '</div>' +
            rows.map((r) => {
                const pct = this.config.noPct || !total ? '' : Math.round((r.value / total) * 100) + '%';
                return '<div class="stats-table-row">' +
                    '<div class="stats-table-cell"><span class="stats-legend-dot" style="background:' + r.color + '"></span>' + this.escape(r.label) + '</div>' +
                    '<div class="stats-table-num">' + this.escape(fmtValue(r.value, this.config.money)) + '</div>' +
                    (this.config.noPct ? '' : '<div class="stats-table-num stats-table-num--muted">' + pct + '</div>') + '</div>';
            }).join('') + '</div>';
    }

    renderLine() { this.renderTrend('line'); }
    renderColumns() { this.renderTrend('bar'); }

    renderTrend(chartType) {
        const c = this.config;
        this.body.innerHTML = '<div class="stats-trend-wrap"><canvas></canvas></div>';
        const canvas = this.body.querySelector('canvas');
        const datasets = [{
            label: 'Periodo actual',
            data: c.series || [],
            borderColor: COLORS.brand,
            backgroundColor: chartType === 'bar' ? COLORS.brand : 'rgba(255,98,19,0.10)',
            fill: chartType === 'line',
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 0,
            pointHoverRadius: 4,
            borderRadius: chartType === 'bar' ? 5 : 0,
        }];
        if (c.compareSeries && c.compareSeries.length) {
            datasets.push({
                label: c.compareLabel || 'Periodo anterior',
                data: c.compareSeries,
                borderColor: '#D1D5DB',
                backgroundColor: '#D1D5DB',
                borderDash: chartType === 'line' ? [5, 4] : undefined,
                borderWidth: 2,
                pointRadius: 0,
                borderRadius: chartType === 'bar' ? 5 : 0,
            });
        }
        this.chart = new Chart(canvas, {
            type: chartType,
            data: { labels: c.axis || [], datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: datasets.length > 1 } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (v) => (c.unit === 'M' ? '$' + v + 'M' : v) },
                    },
                },
            },
        });
    }

    renderStacked() {
        const rows = this.rowsWithColor();
        const total = rows.reduce((a, r) => a + r.value, 0);
        this.body.innerHTML = '<div class="stats-stacked-wrap">' +
            '<div class="stats-stacked-cards">' +
            rows.map((r) => {
                const pct = total ? Math.round((r.value / total) * 100) + '%' : '0%';
                return '<div class="stats-stacked-card">' +
                    '<div class="stats-stacked-card-label"><span class="stats-legend-dot" style="background:' + r.color + '"></span>' + this.escape(r.label) + '</div>' +
                    '<div class="stats-stacked-card-value">' + this.escape(fmtValue(r.value, true)) + ' <span class="stats-stacked-card-pct">' + pct + '</span></div>' +
                    '</div>';
            }).join('') +
            '<div class="stats-stacked-card stats-stacked-card--total"><div class="stats-stacked-card-label">' + this.escape(this.config.totalLabel || 'Total') + '</div>' +
            '<div class="stats-stacked-card-value">' + this.escape(fmtValue(total, true)) + '</div></div>' +
            '</div>' +
            '<div class="stats-stacked-bar">' +
            rows.map((r) => {
                const pct = total ? (r.value / total * 100).toFixed(1) : 0;
                return '<div class="stats-stacked-bar-seg" style="width:' + pct + '%;background:' + r.color + '">' + (pct >= 8 ? Math.round(pct) + '%' : '') + '</div>';
            }).join('') +
            '</div></div>';
    }

    renderRank() {
        const c = this.config;
        const allRows = (c.rows || []).map((r) => ({ label: r[0], value: r[1], seg: r[2] || '' }));
        let rows = c.filters && this.rankCfg.filter !== 'Todos'
            ? allRows.filter((r) => r.seg === this.rankCfg.filter)
            : allRows.slice();

        rows.sort((a, b) => {
            if (this.rankCfg.sort === 'label') {
                return this.rankCfg.order === 'desc' ? b.label.localeCompare(a.label) : a.label.localeCompare(b.label);
            }
            return this.rankCfg.order === 'desc' ? b.value - a.value : a.value - b.value;
        });

        const totalRows = rows.length;
        const shown = rows.slice(0, this.rankCfg.limit);
        const max = Math.max(1, ...shown.map((r) => r.value));

        const filterOptions = c.filters ? c.filters.map((f) => '<option value="' + this.escapeAttr(f) + '"' + (f === this.rankCfg.filter ? ' selected' : '') + '>' + this.escape(f) + '</option>').join('') : '';

        this.body.innerHTML =
            '<div class="stats-rank-wrap">' +
            '<div class="stats-rank-toolbar">' +
            '<div class="stats-rank-limits">' +
            [10, 20].map((n) => '<button type="button" class="stats-rank-limit-btn' + (this.rankCfg.limit === n ? ' is-active' : '') + '" data-limit="' + n + '">Top ' + n + '</button>').join('') +
            '</div>' +
            '<button type="button" class="stats-rank-order-btn" data-action="toggle-order">' + (this.rankCfg.order === 'desc' ? 'Mayor → menor' : 'Menor → mayor') + '</button>' +
            (c.filters ? '<select class="stats-rank-filter">' + '<option value="Todos"' + (this.rankCfg.filter === 'Todos' ? ' selected' : '') + '>Todos</option>' + filterOptions + '</select>' : '') +
            '<div class="stats-rank-shown">' + shown.length + ' de ' + totalRows + '</div>' +
            '</div>' +
            '<div class="stats-rank-head"><div>#</div><div data-sort="label">' + this.escape(c.colLabel || 'Concepto') + '</div>' +
            (c.segLabel ? '<div>' + this.escape(c.segLabel) + '</div>' : '<div></div>') +
            '<div data-sort="value" class="stats-table-num">' + this.escape(c.colValue || 'Valor') + '</div><div class="stats-table-num">Peso</div></div>' +
            '<div class="stats-rank-rows">' +
            shown.map((r, i) => {
                return '<div class="stats-rank-row"><div class="stats-rank-pos">' + (i + 1) + '</div>' +
                    '<div class="stats-rank-label">' + this.escape(r.label) + '</div>' +
                    '<div>' + (r.seg ? '<span class="stats-rank-seg">' + this.escape(r.seg) + '</span>' : '') + '</div>' +
                    '<div class="stats-table-num">' + this.escape(fmtValue(r.value, c.money)) + '</div>' +
                    '<div class="stats-rank-weight"><div class="stats-rank-weight-fill" style="width:' + (r.value / max * 100).toFixed(1) + '%"></div></div>' +
                    '</div>';
            }).join('') +
            '</div></div>';

        const wrap = this.body.querySelector('.stats-rank-wrap');
        wrap.querySelectorAll('[data-limit]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.rankCfg.limit = parseInt(btn.dataset.limit, 10);
                setStoredRankCfg(this.id, this.rankCfg);
                this.renderRank();
            });
        });
        const orderBtn = wrap.querySelector('[data-action="toggle-order"]');
        if (orderBtn) orderBtn.addEventListener('click', () => {
            this.rankCfg.order = this.rankCfg.order === 'desc' ? 'asc' : 'desc';
            setStoredRankCfg(this.id, this.rankCfg);
            this.renderRank();
        });
        const filterSel = wrap.querySelector('.stats-rank-filter');
        if (filterSel) filterSel.addEventListener('change', (e) => {
            this.rankCfg.filter = e.target.value;
            setStoredRankCfg(this.id, this.rankCfg);
            this.renderRank();
        });
        wrap.querySelectorAll('[data-sort]').forEach((cell) => {
            cell.addEventListener('click', () => {
                const key = cell.dataset.sort;
                this.rankCfg.order = (this.rankCfg.sort === key && this.rankCfg.order === 'desc') ? 'asc' : 'desc';
                this.rankCfg.sort = key;
                setStoredRankCfg(this.id, this.rankCfg);
                this.renderRank();
            });
        });
    }

    escape(str) {
        const div = document.createElement('div');
        div.textContent = String(str === undefined || str === null ? '' : str);
        return div.innerHTML;
    }

    // escape() solo es seguro como contenido de nodo de texto (no escapa
    // comillas). Único uso real: value="..." de <option> en renderRank().
    escapeAttr(str) {
        return this.escape(str).replace(/"/g, '&quot;');
    }
}

function initAll() {
    // for...of + try/catch por elemento, no forEach: una excepción sin
    // capturar en un panel (ej. render() con datos inesperados) aborta el
    // callback de forEach entero y deja sin inicializar todos los paneles
    // que vengan después en el DOM — aquí un panel roto no afecta al resto.
    for (const el of document.querySelectorAll('.stats-panel[data-panel-id]')) {
        if (el.dataset.statsInit) continue;
        el.dataset.statsInit = '1';
        try {
            new StatisticsPanel(el);
        } catch (e) {
            const body = el.querySelector('.stats-panel-body');
            if (body) {
                body.innerHTML = '<div class="stats-panel-state"><div class="stats-panel-state-title">No se pudo cargar el panel</div></div>';
            }
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

export default StatisticsPanel;
