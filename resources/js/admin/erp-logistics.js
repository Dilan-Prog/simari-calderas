// ERP logistics: JS del módulo (entrada de Vite).
document.addEventListener('DOMContentLoaded', () => {
    // Envío B2B: al elegir el documento de origen se recarga el formulario con los datos precargados.
    const source = document.getElementById('lg-source');
    if (source) {
        source.addEventListener('change', () => {
            const [type, id] = source.value.split(':');
            const base = source.dataset.createUrl;
            window.location.href = id ? `${base}?${type}=${encodeURIComponent(id)}` : base;
        });
    }

    // Filas clicables de listados.
    document.querySelectorAll('tr[data-href]').forEach((tr) => {
        tr.addEventListener('click', (e) => {
            if (e.target.closest('a,button,form')) return;
            window.location.href = tr.dataset.href;
        });
    });

    // Partidas de recolección: agregar / quitar renglones.
    const body = document.getElementById('lg-items-body');
    const addBtn = document.getElementById('lg-add-item');
    if (body && addBtn) {
        const reindex = () => {
            body.querySelectorAll('tr').forEach((tr, i) => {
                tr.querySelectorAll('[data-field]').forEach((el) => {
                    el.name = `items[${i}][${el.dataset.field}]`;
                });
            });
        };
        addBtn.addEventListener('click', () => {
            const clone = body.querySelector('tr').cloneNode(true);
            clone.querySelectorAll('input').forEach((el) => {
                el.value = el.dataset.field === 'quantity' ? '1' : '';
            });
            body.appendChild(clone);
            reindex();
        });
        body.addEventListener('click', (e) => {
            if (!e.target.closest('.lg-remove-item')) return;
            if (body.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
                reindex();
            }
        });
    }

    // Firma en canvas (mouse y táctil), se envía como PNG base64.
    const canvas = document.getElementById('lg-sign-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const ratio = window.devicePixelRatio || 1;
        const resize = () => {
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#111827';
        };
        resize();
        let drawing = false;
        let dirty = false;
        const pos = (e) => {
            const r = canvas.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        };
        canvas.addEventListener('pointerdown', (e) => {
            drawing = true;
            const p = pos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            canvas.setPointerCapture(e.pointerId);
        });
        canvas.addEventListener('pointermove', (e) => {
            if (!drawing) return;
            const p = pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            dirty = true;
        });
        const stop = () => { drawing = false; };
        canvas.addEventListener('pointerup', stop);
        canvas.addEventListener('pointercancel', stop);

        document.getElementById('lg-sign-clear')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            dirty = false;
        });
        document.getElementById('lg-sign-form')?.addEventListener('submit', (e) => {
            if (!dirty) {
                e.preventDefault();
                alert('Dibuja la firma antes de guardar.');
                return;
            }
            document.getElementById('lg-sign-data').value = canvas.toDataURL('image/png');
        });
    }
});
