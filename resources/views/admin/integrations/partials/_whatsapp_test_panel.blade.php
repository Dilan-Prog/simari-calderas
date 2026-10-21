{{--
    Pestaña "Probar conexión" de Integraciones > WhatsApp API (Meta Cloud API).
    Variables: $accounts (cuentas meta_cloud_api), $webhookUrl (Callback URL del webhook).
    Rutas: admin.whatsapp-accounts.test.{connection,send,webhook}
    (WhatsappConnectionTestController). Todo el JS está aquí (sin build de Vite).
--}}
<style>
    .waTest-step { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; margin-bottom: 14px; background: #fff; }
    .waTest-step h3 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #141516; display: flex; align-items: center; gap: 8px; }
    .waTest-num { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #ff6213; color: #fff; font-size: 12px; font-weight: 800; align-items: center; justify-content: center; flex: 0 0 auto; }
    .waTest-hint { margin: 0 0 12px; font-size: 12.5px; color: #6b7280; line-height: 1.5; }
    .waTest-row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .waTest-row .pform-input { flex: 1 1 220px; min-width: 0; }
    .waTest-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 10px 14px; margin-bottom: 12px; }
    .waTest-checks { list-style: none; margin: 12px 0 0; padding: 0; }
    .waTest-checks li { display: flex; gap: 10px; padding: 8px 0; border-top: 1px solid #f3f4f6; font-size: 13px; line-height: 1.45; }
    .waTest-checks li:first-child { border-top: 0; }
    .waTest-ico { flex: 0 0 22px; height: 22px; border-radius: 50%; color: #fff; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .waTest-ico.ok { background: #16a34a; } .waTest-ico.warn { background: #d97706; } .waTest-ico.fail { background: #dc2626; }
    .waTest-checks strong { display: block; color: #141516; }
    .waTest-checks span.d { color: #6b7280; word-break: break-word; }
    .waTest-result { margin-top: 12px; padding: 10px 12px; border-radius: 8px; font-size: 13px; line-height: 1.5; display: none; }
    .waTest-result.ok { display: block; background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
    .waTest-result.fail { display: block; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .waTest-sendmode label { margin-right: 14px; font-size: 13px; cursor: pointer; }
</style>

<div class="pform-panel-wrap">
    <div class="pform-panel" style="max-width:860px;">
        <h2 class="pform-panel-title">Probar conexión con Meta</h2>
        <p class="pform-hint" style="margin-bottom:14px;">
            Sigue los 4 pasos con tu app de prueba de Meta. Ninguno de estos pasos crea conversaciones en el CRM:
            solo comprueban la conexión (lo que te conteste el destinatario sí entra por el webhook normal).
        </p>

        @if ($accounts->isEmpty())
            <div class="waTest-step">
                <p class="waTest-hint" style="margin:0;">
                    Primero registra la cuenta en la pestaña <strong>Credenciales</strong> (botón "+ Nueva cuenta"):
                    Nombre, número, Phone Number ID, WhatsApp Business Account ID, token de verificación, Access Token y App Secret.
                </p>
            </div>
        @else
            <div class="pform-field" style="margin-bottom:14px;">
                <label class="pform-label" for="waTestAccount">Cuenta a probar</label>
                <select id="waTestAccount" class="pform-input" style="max-width:420px;">
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}" data-verify-token="{{ $acc->webhook_verify_token }}">
                            {{ $acc->name }}{{ $acc->phone_number ? ' · ' . $acc->phone_number : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 1. Datos para configurar el webhook en Meta --}}
            <div class="waTest-step">
                <h3><span class="waTest-num">1</span> Configura el webhook en Meta</h3>
                <p class="waTest-hint">
                    En Meta for Developers &gt; WhatsApp &gt; <strong>Configuration</strong> &gt; Webhook: pega la Callback URL y el
                    token de verificación, pulsa "Verify and save" y suscribe el campo <strong>messages</strong>.
                    La URL debe ser pública con HTTPS (en local usa un túnel tipo Cloudflare Tunnel o ngrok y pega esa URL +
                    <code>/whatsapp/webhook</code>).
                </p>
                <div class="pform-field" style="margin-bottom:10px;">
                    <label class="pform-label">Callback URL</label>
                    <div class="integr-copy-row">
                        <input type="text" class="pform-input" id="waTestCallback" value="{{ $webhookUrl }}" readonly>
                        <button type="button" class="pform-btn outline integr-copy-btn" data-value="{{ $webhookUrl }}">Copiar</button>
                    </div>
                </div>
                <div class="pform-field">
                    <label class="pform-label">Token de verificación (el de la cuenta)</label>
                    <div class="integr-copy-row">
                        <input type="text" class="pform-input" id="waTestVerifyToken" readonly placeholder="La cuenta no tiene token: edítala en Credenciales y escribe uno">
                        <button type="button" class="pform-btn outline" id="waTestCopyToken">Copiar</button>
                    </div>
                </div>
                @if (\Illuminate\Support\Str::startsWith($webhookUrl, 'http://localhost') || \Illuminate\Support\Str::contains($webhookUrl, '.test'))
                    <p style="margin:8px 0 0; font-size:12px; color:#b45309;">
                        Esta URL es de tu entorno local: Meta no puede alcanzarla. Para probar en local necesitas un túnel público;
                        en producción será <code>https://equitermindustries.com.mx/whatsapp/webhook</code>.
                    </p>
                @endif
            </div>

            {{-- 2. Verificar conexión --}}
            <div class="waTest-step">
                <h3><span class="waTest-num">2</span> Verifica la conexión</h3>
                <p class="waTest-hint">Revisa lo capturado y llama a Meta con tu token y Phone Number ID (lee el número, su calidad y tus plantillas).</p>
                <div class="waTest-row">
                    <button type="button" class="pform-btn primary" id="waTestCheckBtn">Verificar ahora</button>
                    <span id="waTestCheckState" class="waTest-hint" style="margin:0;"></span>
                </div>
                <ul class="waTest-checks" id="waTestChecks"></ul>
            </div>

            {{-- 3. Mensaje de prueba --}}
            <div class="waTest-step">
                <h3><span class="waTest-num">3</span> Envía un mensaje de prueba</h3>
                <p class="waTest-hint">
                    Con el número de prueba de Meta solo puedes escribir a teléfonos que agregaste y verificaste en WhatsApp &gt; API Setup
                    (máximo 5) y solo con plantillas; <strong>hello_world</strong> (en_US) viene lista desde el inicio.
                </p>
                <div class="waTest-grid">
                    <div class="pform-field">
                        <label class="pform-label" for="waTestTo">Número destinatario (con código de país)</label>
                        <input type="text" class="pform-input" id="waTestTo" inputmode="tel" placeholder="5214491234567">
                    </div>
                </div>
                <div class="waTest-sendmode" style="margin-bottom:10px;">
                    <label><input type="radio" name="waTestMode" value="template" checked> Plantilla</label>
                    <label><input type="radio" name="waTestMode" value="text"> Texto libre (solo si el contacto te escribió en las últimas 24 h)</label>
                </div>
                <div class="waTest-grid" id="waTestTemplateFields">
                    <div class="pform-field">
                        <label class="pform-label" for="waTestTemplate">Nombre de la plantilla</label>
                        <input type="text" class="pform-input" id="waTestTemplate" value="hello_world" maxlength="100">
                    </div>
                    <div class="pform-field">
                        <label class="pform-label" for="waTestLanguage">Idioma</label>
                        <select class="pform-input" id="waTestLanguage">
                            <option value="en_US" selected>en_US (hello_world)</option>
                            <option value="es_MX">es_MX</option>
                            <option value="es">es</option>
                            <option value="es_ES">es_ES</option>
                            <option value="en">en</option>
                        </select>
                    </div>
                </div>
                <div class="pform-field" id="waTestTextField" style="display:none; margin-bottom:10px;">
                    <label class="pform-label" for="waTestText">Mensaje</label>
                    <textarea class="pform-input" id="waTestText" rows="3" maxlength="1000" placeholder="Hola, esto es una prueba."></textarea>
                </div>
                <div class="waTest-row">
                    <button type="button" class="pform-btn primary" id="waTestSendBtn">Enviar prueba</button>
                </div>
                <div class="waTest-result" id="waTestSendResult"></div>
            </div>

            {{-- 4. Recepción --}}
            <div class="waTest-step">
                <h3><span class="waTest-num">4</span> Comprueba que el webhook recibe</h3>
                <p class="waTest-hint">
                    Escribe un mensaje al número de prueba desde un teléfono verificado. Debe aparecer aquí y en el
                    Embudo de Venta. Se actualiza solo cada 5 segundos mientras esta pestaña está abierta.
                </p>
                <div class="waTest-row">
                    <button type="button" class="pform-btn outline" id="waTestWebhookBtn">Actualizar</button>
                    <span id="waTestLastInbound" class="waTest-hint" style="margin:0;"></span>
                </div>
                <ul class="waTest-checks" id="waTestWebhookChecks"></ul>
            </div>
        @endif
    </div>
</div>

@if ($accounts->isNotEmpty())
<script>
    (function () {
        const URLS = {
            connection: @json(route('admin.whatsapp-accounts.test.connection', ['whatsappAccount' => '__ID__'])),
            send:       @json(route('admin.whatsapp-accounts.test.send', ['whatsappAccount' => '__ID__'])),
            webhook:    @json(route('admin.whatsapp-accounts.test.webhook', ['whatsappAccount' => '__ID__'])),
        };
        const $ = (id) => document.getElementById(id);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const account = () => $('waTestAccount').value;
        const url = (k) => URLS[k].replace('__ID__', encodeURIComponent(account()));

        const ICON = { ok: '✓', warn: '!', fail: '✕' };
        const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const render = (el, checks) => {
            el.innerHTML = (checks || []).map((c) =>
                '<li><span class="waTest-ico ' + esc(c.status) + '">' + ICON[c.status] + '</span><div><strong>' + esc(c.label) +
                '</strong><span class="d">' + esc(c.detail) + '</span></div></li>').join('');
        };
        const getJson = async (u) => {
            const r = await fetch(u, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        };

        const syncToken = () => {
            const opt = $('waTestAccount').selectedOptions[0];
            $('waTestVerifyToken').value = opt ? (opt.dataset.verifyToken || '') : '';
        };
        $('waTestAccount').addEventListener('change', () => {
            syncToken();
            $('waTestChecks').innerHTML = '';
            $('waTestSendResult').className = 'waTest-result';
            loadWebhook();
        });
        syncToken();

        $('waTestCopyToken').addEventListener('click', async (e) => {
            const v = $('waTestVerifyToken').value;
            if (!v) return;
            try { await navigator.clipboard.writeText(v); e.target.textContent = 'Copiado'; setTimeout(() => (e.target.textContent = 'Copiar'), 1500); } catch (_) {}
        });

        $('waTestCheckBtn').addEventListener('click', async () => {
            const btn = $('waTestCheckBtn'); const state = $('waTestCheckState');
            btn.disabled = true; state.textContent = 'Consultando a Meta…';
            try {
                const d = await getJson(url('connection'));
                render($('waTestChecks'), d.checks);
                state.textContent = d.ok ? 'Conexión lista ✓' : 'Hay pasos por corregir (revisa los ✕).';
            } catch (err) { state.textContent = 'No se pudo verificar: ' + err.message; }
            btn.disabled = false;
        });

        document.querySelectorAll('input[name="waTestMode"]').forEach((r) => r.addEventListener('change', () => {
            const text = document.querySelector('input[name="waTestMode"]:checked').value === 'text';
            $('waTestTemplateFields').style.display = text ? 'none' : '';
            $('waTestTextField').style.display = text ? '' : 'none';
        }));

        $('waTestSendBtn').addEventListener('click', async () => {
            const btn = $('waTestSendBtn'); const out = $('waTestSendResult');
            const mode = document.querySelector('input[name="waTestMode"]:checked').value;
            btn.disabled = true; out.className = 'waTest-result'; out.textContent = '';
            try {
                const r = await fetch(url('send'), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        to: $('waTestTo').value, mode,
                        template: $('waTestTemplate').value, language: $('waTestLanguage').value, text: $('waTestText').value,
                    }),
                });
                const d = await r.json().catch(() => ({}));
                if (r.ok && d.success) {
                    out.className = 'waTest-result ok';
                    out.innerHTML = 'Meta aceptó el mensaje para <strong>' + esc(d.to) + '</strong>. ID: <code>' + esc(d.message_id) +
                        '</code>. Revisa el teléfono; si no llega, mira el paso 2.';
                } else {
                    const e = d.error || {};
                    const first = d.message || (d.errors ? Object.values(d.errors)[0][0] : null);
                    out.className = 'waTest-result fail';
                    out.innerHTML = '<strong>No se envió.</strong> ' + esc(e.hint || first || e.message || 'Error desconocido') +
                        (e.message && e.hint ? '<br><small>Meta' + (e.code ? ' (' + esc(e.code) + ')' : '') + ': ' + esc(e.message) + '</small>' : '');
                }
            } catch (err) {
                out.className = 'waTest-result fail'; out.textContent = 'Error de red: ' + err.message;
            }
            btn.disabled = false;
        });

        async function loadWebhook() {
            try {
                const d = await getJson(url('webhook'));
                render($('waTestWebhookChecks'), d.checks);
                $('waTestLastInbound').textContent = d.last_inbound
                    ? 'Último mensaje recibido: ' + d.last_inbound.at + (d.last_inbound.preview ? ' — “' + d.last_inbound.preview + '”' : '')
                    : 'Aún no se ha recibido ningún mensaje.';
            } catch (_) { /* silencioso: es refresco automático */ }
        }
        $('waTestWebhookBtn').addEventListener('click', loadWebhook);
        loadWebhook();
        setInterval(() => {
            const visible = $('waTestWebhookChecks') && $('waTestWebhookChecks').offsetParent !== null;
            if (visible && !document.hidden) loadWebhook();
        }, 5000);
    })();
</script>
@endif
