{{-- Escanear el QR. Se abre justo después de crear una cuenta "WhatsApp Web",
     o desde el botón "Reconectar" de una fila existente con session_status
     'disconnected'. Movido desde
     resources/views/admin/whatsapp-accounts/partials/_modal_qr_scan.blade.php.
     El JS (partials/_whatsapp_scripts.blade.php) hace polling a
     admin.whatsapp-accounts.qr-status cada ~3s: primera llamada dispara
     startSession() en el backend, las siguientes solo consultan sessionStatus()
     -- ambas devuelven {status, qr}, y el QR puede rotar mientras siga
     'qr_pending' porque los códigos de Baileys expiran. --}}
<div id="whatsappQrScanModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="waQrScanTitle">Escanea el código QR</h2>
                <p class="del-confirm-desc">
                    Abre WhatsApp en tu teléfono → Ajustes → Dispositivos vinculados → Vincular un dispositivo.
                </p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel"
                id="closeWhatsappQrScanModal">✕</button>
        </div>

        <div class="ap-modal-body"
            style="display:flex; flex-direction:column; align-items:center; gap:14px; padding:10px 0 18px; min-height:260px; justify-content:center;">

            <div id="waQrScanLoading" style="text-align:center;">
                <p style="color:#6b7280; font-size:13px;">Generando código QR…</p>
            </div>

            <img id="waQrScanImage" src="" alt="Código QR de WhatsApp"
                style="display:none; width:240px; height:240px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;">

            <div id="waQrScanSuccess" style="display:none; text-align:center;">
                <p style="color:#3cbe40; font-weight:600; font-size:14px; margin:0;">✓ Conectado correctamente</p>
                <p style="color:#6b7280; font-size:12px; margin-top:6px;">Esta ventana se cerrará automáticamente…
                </p>
            </div>

            <p id="waQrScanError" style="display:none; color:#dc2626; font-size:13px; text-align:center;"></p>
        </div>

        <div class="ap-modal-footer">
            <button type="button" class="button-secondary size-adjustment"
                id="closeWhatsappQrScanModalBtn">Cerrar</button>
        </div>
    </div>
</div>
