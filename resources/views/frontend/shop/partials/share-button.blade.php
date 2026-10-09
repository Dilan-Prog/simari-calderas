<div class="product-share" x-data="shareMenu(@js($url), @js($title))" @keydown.escape.window="open = false"
     x-effect="document.body.classList.toggle('eq-no-scroll', open && window.matchMedia('(max-width: 720px)').matches)">
    <button type="button" class="product-share__btn" @click="toggle()" :aria-expanded="open" aria-haspopup="menu" aria-label="Compartir esta página">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.7l6.8-4M8.6 13.3l6.8 4" stroke-linecap="round"/></svg>
        <span class="product-share__label">Compartir</span>
    </button>
    <div class="product-share__backdrop" x-show="open" x-cloak @click="open = false"></div>
    <div class="product-share__menu" x-show="open" x-cloak @click.outside="open = false" role="menu" aria-label="Compartir">
        <div class="product-share__sheet-title">Compartir</div>
        <a role="menuitem" :href="links.whatsapp" target="_blank" rel="noopener nofollow" @click="open = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.3L3 21z" stroke-linejoin="round"/></svg><span>WhatsApp</span>
        </a>
        <a role="menuitem" :href="links.facebook" target="_blank" rel="noopener nofollow" @click="open = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z" stroke-linejoin="round"/></svg><span>Facebook</span>
        </a>
        <a role="menuitem" :href="links.x" target="_blank" rel="noopener nofollow" @click="open = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4l16 16M20 4L4 20" stroke-linecap="round"/></svg><span>X</span>
        </a>
        <a role="menuitem" :href="links.email" @click="open = false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6" stroke-linejoin="round"/></svg><span>Correo</span>
        </a>
        <button type="button" role="menuitem" @click="copy()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9" stroke-linecap="round"/></svg><span x-text="copied ? '¡Enlace copiado!' : 'Copiar enlace'">Copiar enlace</span>
        </button>
    </div>
    <span class="sr-only" role="status" aria-live="polite" x-text="copied ? 'Enlace copiado' : ''"></span>
</div>
