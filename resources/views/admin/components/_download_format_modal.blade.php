{{-- Modal reutilizable "Elige el formato de descarga": selector genérico de
     PDF / Excel / Hoja de cálculo para cualquier botón de descarga del admin.
     No recibe variables — la URL base de descarga la aporta el botón que lo
     abre vía el atributo data-download-url (ver public/js/admin/download-format.js).
     Inclúyelo UNA VEZ por vista que lo necesite. --}}
<div id="downloadFormatModal" class="del-confirm-overlay">
    <div class="del-confirm-box download-format-box">

        <div class="download-format-header">
            <h2 class="del-confirm-title download-format-title">Elige el formato de descarga</h2>
            <button type="button" class="download-format-close-btn" id="downloadFormatCloseBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="download-format-options">
            <button type="button" class="download-format-btn" data-format="pdf">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="download-format-icon" style="color:#DC2626">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <path d="M14 2v6h6" />
                    <text x="12" y="17.5" text-anchor="middle" font-size="6.5" font-weight="700"
                        fill="currentColor" stroke="none" font-family="Arial, Helvetica, sans-serif">PDF</text>
                </svg>
                <span>PDF</span>
            </button>
            <button type="button" class="download-format-btn" data-format="xlsx">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="download-format-icon" style="color:#16A34A">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <path d="M14 2v6h6" />
                    <rect x="8" y="12" width="8" height="7" rx="0.5" />
                    <line x1="8" y1="15.5" x2="16" y2="15.5" />
                    <line x1="11.3" y1="12" x2="11.3" y2="19" />
                    <line x1="14.6" y1="12" x2="14.6" y2="19" />
                </svg>
                <span>Excel</span>
            </button>
            <button type="button" class="download-format-btn" data-format="sheets">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="download-format-icon" style="color:#0F9D58">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <path d="M14 2v6h6" />
                    <rect x="8" y="12" width="8" height="7" rx="0.5" />
                    <line x1="8" y1="15.5" x2="16" y2="15.5" />
                    <line x1="11.3" y1="12" x2="11.3" y2="19" />
                    <line x1="14.6" y1="12" x2="14.6" y2="19" />
                </svg>
                <span>Hoja de Cálculo</span>
            </button>
        </div>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="downloadFormatCancel">Cancelar</button>
        </div>
    </div>
</div>
