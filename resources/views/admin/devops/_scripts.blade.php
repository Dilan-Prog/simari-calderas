<script>
    (function () {
        const sqlInput = document.getElementById('devopsSqlInput');
        const runBtn = document.getElementById('devopsRunBtn');
        const resultsEl = document.getElementById('devopsResults');
        const confirmModal = document.getElementById('devopsConfirmModal');
        const confirmPreview = document.getElementById('devopsConfirmSqlPreview');
        const confirmCancel = document.getElementById('devopsConfirmCancel');
        const confirmRun = document.getElementById('devopsConfirmRun');
        const executeUrl = '{{ route('admin.devops.execute') }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Clasificación puramente informativa en el cliente, para decidir si
        // mostrar el modal de confirmación antes de disparar la petición —
        // el servidor SIEMPRE re-clasifica y re-valida de forma independiente,
        // nunca confía en esto.
        function looksLikeRead(sql) {
            const normalized = sql.replace(/^(\s|--[^\n]*\n|\/\*[\s\S]*?\*\/)+/, '');
            return /^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i.test(normalized);
        }

        function renderError(message) {
            resultsEl.innerHTML = `<div class="devops-results__error">${escapeHtml(message)}</div>`;
        }

        function renderReadResult(data) {
            let html = '';
            if (data.truncated) {
                html += `<div class="devops-results__truncated">Resultados truncados a las primeras ${data.rows.length} filas.</div>`;
            }
            if (!data.columns.length) {
                html += `<p class="devops-results__empty">La consulta no devolvió filas.</p>`;
                resultsEl.innerHTML = html;
                return;
            }
            html += '<div class="devops-table-wrap"><table class="devops-table"><thead><tr>';
            data.columns.forEach(col => { html += `<th>${escapeHtml(col)}</th>`; });
            html += '</tr></thead><tbody>';
            data.rows.forEach(row => {
                html += '<tr>';
                row.forEach(val => { html += `<td>${val === null ? '<em>NULL</em>' : escapeHtml(String(val))}</td>`; });
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            resultsEl.innerHTML = html;
        }

        function renderWriteResult(data) {
            resultsEl.innerHTML = `<div class="devops-results__success">${escapeHtml(data.message)} Filas afectadas: ${data.affected}. Respaldo generado: ${escapeHtml(data.backup_file)}.</div>`;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function runQuery(confirmed) {
            const sql = sqlInput.value.trim();
            if (!sql) return;

            runBtn.disabled = true;
            runBtn.textContent = 'Ejecutando...';

            try {
                const response = await fetch(executeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ sql, confirmed: !!confirmed }),
                });
                const data = await response.json();

                if (!response.ok) {
                    if (data.requires_confirmation) {
                        confirmPreview.textContent = sql;
                        confirmModal.classList.add('active');
                        return;
                    }
                    renderError(data.message || 'Ocurrió un error al ejecutar la sentencia.');
                    return;
                }

                if (data.type === 'read') {
                    renderReadResult(data);
                } else {
                    renderWriteResult(data);
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch (err) {
                renderError('Error de conexión al ejecutar la sentencia.');
            } finally {
                runBtn.disabled = false;
                runBtn.textContent = 'Ejecutar';
            }
        }

        runBtn.addEventListener('click', () => {
            const sql = sqlInput.value.trim();
            if (!sql) return;

            if (!looksLikeRead(sql)) {
                confirmPreview.textContent = sql;
                confirmModal.classList.add('active');
                return;
            }

            runQuery(false);
        });

        confirmCancel.addEventListener('click', () => confirmModal.classList.remove('active'));
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) confirmModal.classList.remove('active');
        });

        confirmRun.addEventListener('click', () => {
            confirmModal.classList.remove('active');
            runQuery(true);
        });
    })();
</script>
