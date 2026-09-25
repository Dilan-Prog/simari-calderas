{{-- Sección deshabilitada a propósito -- era un mockup con favoritos de
     demostración, sin ninguna lógica real detrás. Se deja visible en el
     menú como "Próximamente" (ver sidebar.blade.php) en vez de quitarla
     del todo, para no perder el trabajo de diseño ya hecho. --}}
<section x-show="section === 'favoritos'" x-cloak>
    <h1 class="portal-title">Favoritos</h1>
    <div class="portal-card">
        <div class="portal-empty">
            <p>Muy pronto vas a poder guardar tus productos favoritos desde aquí.</p>
        </div>
    </div>
</section>
