// ERP Finanzas: formularios de CXC/CXP y pantalla de pagos (aplicación a documentos).

const money = (n) => '$' + (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

/* ── Formulario de CXC / CXP: tipo de cambio solo en USD ── */
function initDocForm() {
    const currency = document.getElementById('finCurrency');
    const rateWrap = document.getElementById('finRateWrap');
    if (!currency || !rateWrap) return;
    const rate = document.getElementById('finRate');
    const sync = () => {
        const usd = currency.value === 'USD';
        rateWrap.style.display = usd ? '' : 'none';
        if (!usd && rate) rate.value = 1;
    };
    currency.addEventListener('change', sync);
    sync();

    const sub = document.getElementById('finSubtotal');
    const tax = document.getElementById('finTax');
    const total = document.getElementById('finTotal');
    if (sub && tax && total) {
        const hint = () => { total.placeholder = round2((+sub.value || 0) + (+tax.value || 0)).toFixed(2); };
        sub.addEventListener('input', hint);
        tax.addEventListener('input', hint);
        hint();
    }
}

/* ── Resumen compartido: cuánto del pago consumen los montos capturados ── */
function consumed(inputs, payRate) {
    let sum = 0;
    inputs.forEach((i) => {
        const v = parseFloat(i.value) || 0;
        const r = parseFloat(i.dataset.rate) || 1;
        sum += (v * r) / payRate;
    });
    return round2(sum);
}

/* ── Alta de pago ── */
function initPaymentCreate() {
    const form = document.getElementById('finPaymentForm');
    if (!form) return;

    const dir = document.getElementById('finDirection');
    const customer = document.getElementById('finCustomer');
    const supplier = document.getElementById('finSupplier');
    const customerWrap = document.getElementById('finCustomerWrap');
    const supplierWrap = document.getElementById('finSupplierWrap');
    const amount = document.getElementById('finAmount');
    const payCurrency = document.getElementById('finPayCurrency');
    const payRate = document.getElementById('finPayRate');
    const payRateWrap = document.getElementById('finPayRateWrap');
    const body = document.getElementById('finDocsBody');
    const fifo = document.getElementById('finFifo');
    const fifoBtn = document.getElementById('finFifoBtn');
    const sumAmount = document.getElementById('finSumAmount');
    const sumApplied = document.getElementById('finSumApplied');
    const sumFree = document.getElementById('finSumFree');
    let docs = [];
    let prefillDoc = form.dataset.prefillDoc || '';

    const rate = () => (payCurrency.value === 'USD' ? (parseFloat(payRate.value) || 0) : 1);
    const inputs = () => Array.from(body.querySelectorAll('input[data-doc]'));

    function emptyRow(text) {
        body.innerHTML = '';
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'erp-empty';
        td.style.border = '0';
        td.textContent = text;
        tr.appendChild(td);
        body.appendChild(tr);
    }

    function updateSummary() {
        const total = parseFloat(amount.value) || 0;
        const applied = consumed(inputs(), rate() || 1);
        const free = round2(total - applied);
        sumAmount.textContent = money(total) + ' ' + payCurrency.value;
        sumApplied.textContent = money(applied);
        sumFree.textContent = money(free);
        sumFree.className = free < -0.01 ? 'is-bad' : (free >= 0 && applied > 0 ? 'is-ok' : '');
    }

    function render() {
        if (!docs.length) { emptyRow('Sin documentos abiertos para esta contraparte.'); updateSummary(); return; }
        body.innerHTML = '';
        docs.forEach((d) => {
            const tr = document.createElement('tr');
            const cells = [
                () => { const a = document.createElement('strong'); a.textContent = d.number; return a; },
                () => { const s = document.createElement('span'); s.className = 'erp-due' + (d.tone === 'bad' ? ' is-overdue' : ''); s.innerHTML = '<i class="erp-dot erp-dot--' + (['ok','warn','bad'].includes(d.tone) ? d.tone : 'neu') + '"></i>'; s.appendChild(document.createTextNode(d.due_date + (d.days_overdue > 0 ? ' (+' + d.days_overdue + ' d)' : ''))); return s; },
                () => document.createTextNode(d.currency),
                () => document.createTextNode(money(d.balance)),
            ];
            cells.forEach((make, idx) => {
                const td = document.createElement('td');
                if (idx === 3) td.className = 'is-num';
                td.appendChild(make());
                tr.appendChild(td);
            });
            const td = document.createElement('td');
            td.className = 'is-num';
            const input = document.createElement('input');
            input.type = 'number';
            input.step = '0.01';
            input.min = '0';
            input.max = d.balance;
            input.name = 'alloc[' + d.id + ']';
            input.className = 'erp-input fin-apply-input';
            input.dataset.doc = d.id;
            input.dataset.rate = d.rate;
            input.dataset.balance = d.balance;
            input.addEventListener('input', updateSummary);
            td.appendChild(input);
            tr.appendChild(td);
            body.appendChild(tr);
        });
        if (prefillDoc) {
            const target = body.querySelector('input[data-doc="' + prefillDoc + '"]');
            if (target) {
                target.value = target.dataset.balance;
                if (amount && !amount.value) {
                    const r = (parseFloat(target.dataset.rate) || 1) / (rate() || 1);
                    amount.value = round2(parseFloat(target.dataset.balance) * r).toFixed(2);
                }
            }
            prefillDoc = '';
        }
        updateSummary();
    }

    async function load() {
        const out = dir.value === 'out';
        const party = (out ? supplier : customer).value;
        if (!party) { docs = []; emptyRow('Selecciona un cliente o proveedor para ver sus documentos abiertos.'); updateSummary(); return; }
        emptyRow('Cargando…');
        try {
            const url = form.dataset.openUrl + '?direction=' + encodeURIComponent(dir.value) + '&party=' + encodeURIComponent(party);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            docs = res.ok ? await res.json() : [];
        } catch (e) {
            docs = [];
        }
        render();
    }

    function distributeFifo() {
        let remaining = parseFloat(amount.value) || 0;
        const pr = rate() || 1;
        inputs().forEach((i) => {
            const bal = parseFloat(i.dataset.balance);
            const r = parseFloat(i.dataset.rate) || 1;
            if (remaining <= 0.004) { i.value = ''; return; }
            const need = round2((bal * r) / pr);
            if (need <= remaining + 0.01) {
                i.value = bal.toFixed(2);
                remaining = round2(Math.max(0, remaining - need));
            } else {
                i.value = round2((remaining * pr) / r).toFixed(2);
                remaining = 0;
            }
        });
        updateSummary();
    }

    function syncDirection() {
        const out = dir.value === 'out';
        customerWrap.style.display = out ? 'none' : '';
        supplierWrap.style.display = out ? '' : 'none';
        customer.disabled = out;
        supplier.disabled = !out;
        load();
    }

    function syncCurrency() {
        payRateWrap.style.display = payCurrency.value === 'USD' ? '' : 'none';
        if (payCurrency.value !== 'USD') payRate.value = 1;
        updateSummary();
    }

    dir.addEventListener('change', syncDirection);
    customer.addEventListener('change', load);
    supplier.addEventListener('change', load);
    amount.addEventListener('input', updateSummary);
    payRate.addEventListener('input', updateSummary);
    payCurrency.addEventListener('change', syncCurrency);
    fifoBtn.addEventListener('click', () => { if (fifo) fifo.checked = false; distributeFifo(); });
    if (fifo) {
        fifo.addEventListener('change', () => {
            inputs().forEach((i) => { i.disabled = fifo.checked; });
        });
    }
    form.addEventListener('submit', () => {
        // Con FIFO activo el servidor distribuye; no se envían montos manuales.
        if (fifo && fifo.checked) inputs().forEach((i) => { i.disabled = true; });
    });

    syncCurrency();
    syncDirection();
}

/* ── Pantalla de pago: aplicar saldo disponible ── */
function initPaymentApply() {
    const form = document.getElementById('finApplyForm');
    if (!form) return;
    const payRate = parseFloat(form.dataset.rate) || 1;
    const free = parseFloat(form.dataset.free) || 0;
    const sumApplied = document.getElementById('finSumApplied');
    const inputs = Array.from(form.querySelectorAll('input[name^="alloc["]'));
    const fifo = document.getElementById('finFifo');

    const update = () => {
        const used = consumed(inputs, payRate);
        sumApplied.textContent = money(used);
        sumApplied.className = used > free + 0.01 ? 'is-bad' : '';
    };
    inputs.forEach((i) => i.addEventListener('input', update));
    if (fifo) {
        fifo.addEventListener('change', () => inputs.forEach((i) => { i.disabled = fifo.checked; if (fifo.checked) i.value = ''; }));
    }
    update();
}

/* ── Acciones en lote (barra oscura de erp-ui.js) ── */
function initBatchActions() {
    // CXC/CXP: registrar cobro/pago para los documentos marcados (misma contraparte).
    document.querySelectorAll('[data-fin-batch-pay]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const checked = Array.from(document.querySelectorAll('.erp-row-check:checked'));
            if (!checked.length) return;
            const parties = new Set(checked.map((c) => c.dataset.party));
            if (parties.size > 1) {
                window.alert('Selecciona documentos de una sola contraparte para registrar el pago.');
                return;
            }
            const params = new URLSearchParams({ direction: btn.dataset.direction, [btn.dataset.partyParam]: checked[0].dataset.party, doc: checked[0].value });
            window.location.href = btn.dataset.url + '?' + params.toString();
        });
    });

    // Pagos: PATCH por cada id marcado sobre las rutas existentes (conciliar / desconciliar) y recarga.
    document.querySelectorAll('[data-fin-batch-patch]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const ids = typeof window.erpSelectedIds === 'function' ? window.erpSelectedIds() : [];
            if (!ids.length) return;
            if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
            document.querySelectorAll('[data-fin-batch-patch]').forEach((b) => { b.disabled = true; });
            let failed = 0;
            for (const id of ids) {
                try {
                    const body = new URLSearchParams({ _method: 'PATCH', _token: token });
                    const res = await fetch(btn.dataset.urlTemplate.replace('__ID__', id), {
                        method: 'POST', body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                    });
                    if (!res.ok) failed++;
                } catch (e) {
                    failed++;
                }
            }
            if (failed) window.alert('No se pudieron procesar ' + failed + ' de ' + ids.length + ' pagos.');
            window.location.reload();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initBatchActions();
    initDocForm();
    initPaymentCreate();
    initPaymentApply();
});
