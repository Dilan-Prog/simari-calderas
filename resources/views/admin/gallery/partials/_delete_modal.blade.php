{{-- Delete Modal --}}
<div id="galDeleteOverlay" class="del-confirm-overlay" style="z-index:100000">
    <div class="del-confirm-box" style="max-width:400px;text-align:center;padding:28px 24px 24px;">
        <div style="width:52px;height:52px;border-radius:50%;background:#fdecec;color:#c81e1e;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div style="font-size:15.5px;font-weight:800;margin-bottom:8px;">¿Eliminar esta imagen?</div>
        <div style="font-size:13.5px;color:#4b5563;line-height:1.5;margin-bottom:8px;">
            <span id="galDeleteName">La imagen</span> se eliminará permanentemente.
        </div>
        <div style="font-size:12.5px;color:#9ca3af;line-height:1.5;margin-bottom:22px;">
            Si está en uso en alguna sección del sitio, dejará de mostrarse.
        </div>
        <div style="display:flex;gap:10px;">
            <button type="button" class="button-secondary size-adjustment" style="flex:1;" onclick="galCloseDelete()">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="galDeleteConfirm"
                style="flex:1;background:#c81e1e;border-color:#c81e1e;">Eliminar</button>
        </div>
    </div>
</div>
