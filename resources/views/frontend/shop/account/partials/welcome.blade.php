{{-- Bienvenida tras crear la cuenta (flash 'welcome' del registro). Se puede cerrar; no vuelve a salir al recargar.
     CSS en linea (prefijo wl-) para no requerir build. Vars: $customer. --}}
@php
    $wlName = mb_convert_case(mb_strtolower(trim((string) $customer->first_name)), MB_CASE_TITLE, 'UTF-8');
    $wlHasCompany = trim((string) $customer->company) !== '';
    $wlHasAddress = $customer->customer_addresses->isNotEmpty();
    $wlSteps = [
        ['done' => true, 'title' => 'Cuenta creada', 'text' => 'Tu correo ' . $customer->email . ' ya es tu acceso.', 'cta' => null],
        ['done' => $wlHasCompany, 'title' => 'Completa tu perfil', 'text' => 'Agrega tu empresa para que tus cotizaciones y facturas salgan correctas.', 'cta' => ['Completar perfil', '#perfil']],
        ['done' => $wlHasAddress, 'title' => 'Agrega una dirección de envío', 'text' => 'Así el checkout te toma segundos y calculamos tu envío.', 'cta' => ['Agregar dirección', '#direcciones']],
        ['done' => false, 'title' => 'Explora el catálogo', 'text' => 'Calderas, calentadores y refacciones; cotiza o compra en línea.', 'cta' => ['Ver catálogo', route('catalog.index')]],
    ];
    $wlDone = collect($wlSteps)->where('done', true)->count();
@endphp

<section class="wl" x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms role="status" aria-live="polite">
    <button type="button" class="wl-close" @click="open = false" aria-label="Cerrar bienvenida">&times;</button>

    <div class="wl-hero">
        <span class="wl-badge" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
        </span>
        <div class="wl-hero-text">
            <p class="wl-eyebrow">Cuenta creada correctamente</p>
            <h2 class="wl-title">¡Bienvenido a Equiterm, {{ $wlName }}!</h2>
            <p class="wl-sub">Ya puedes cotizar, comprar y dar seguimiento a tus pedidos desde un solo lugar.</p>
        </div>
    </div>

    <div class="wl-progress" aria-label="Primeros pasos: {{ $wlDone }} de {{ count($wlSteps) }}">
        <span class="wl-progress-label">Primeros pasos · {{ $wlDone }} de {{ count($wlSteps) }}</span>
        <span class="wl-bar"><i style="width: {{ round($wlDone / count($wlSteps) * 100) }}%"></i></span>
    </div>

    <ol class="wl-steps">
        @foreach ($wlSteps as $s)
            <li class="wl-step {{ $s['done'] ? 'is-done' : '' }}">
                <span class="wl-step-ic" aria-hidden="true">
                    @if ($s['done'])
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>
                <span class="wl-step-body">
                    <strong>{{ $s['title'] }}</strong>
                    <small>{{ $s['text'] }}</small>
                </span>
                @if ($s['cta'] && ! $s['done'])
                    <a class="wl-step-cta" href="{{ $s['cta'][1] }}" @if ($s['cta'][1] === '#perfil') @click.prevent="go('perfil'); window.scrollTo({ top: 0, behavior: 'smooth' })" @endif>{{ $s['cta'][0] }} →</a>
                @elseif ($s['done'])
                    <span class="wl-step-ok">Listo</span>
                @endif
            </li>
        @endforeach
    </ol>

    <div class="wl-actions">
        <a class="wl-btn wl-btn--primary" href="{{ route('catalog.index') }}">Explorar el catálogo</a>
        <a class="wl-btn" href="#direcciones">Agregar dirección de envío</a>
        <button type="button" class="wl-btn wl-btn--ghost" @click="open = false">Cerrar</button>
    </div>
</section>

<style>
    .eq-shop-account .wl { position: relative; margin-bottom: 22px; border: 1px solid rgba(255, 98, 19, .28); border-radius: 14px; background: #fff; box-shadow: 0 8px 28px rgba(20, 21, 22, .07); overflow: hidden; }
    .eq-shop-account .wl-close { position: absolute; top: 10px; right: 12px; z-index: 2; width: 32px; height: 32px; border: 0; border-radius: 8px; background: rgba(255, 255, 255, .14); color: #fff; font-size: 22px; line-height: 1; cursor: pointer; }
    .eq-shop-account .wl-close:hover { background: rgba(255, 255, 255, .28); }
    .eq-shop-account .wl-hero { display: flex; align-items: center; gap: 16px; padding: 22px 56px 22px 24px; color: #fff; background: linear-gradient(120deg, #141516 0%, #2a1a10 55%, #ff6213 160%); }
    .eq-shop-account .wl-badge { flex: 0 0 52px; width: 52px; height: 52px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #ff6213; box-shadow: 0 0 0 6px rgba(255, 98, 19, .25); }
    .eq-shop-account .wl-eyebrow { margin: 0 0 2px; font-size: 11.5px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #ffb48a; }
    .eq-shop-account .wl-title { margin: 0; font-size: 22px; font-weight: 800; line-height: 1.2; color: #fff; }
    .eq-shop-account .wl-sub { margin: 6px 0 0; font-size: 14px; color: rgba(255, 255, 255, .82); }
    .eq-shop-account .wl-progress { display: flex; align-items: center; gap: 12px; padding: 16px 24px 4px; }
    .eq-shop-account .wl-progress-label { font-size: 12.5px; font-weight: 700; color: #4b5563; white-space: nowrap; }
    .eq-shop-account .wl-bar { flex: 1; height: 7px; border-radius: 999px; background: #f1ece8; overflow: hidden; }
    .eq-shop-account .wl-bar i { display: block; height: 100%; border-radius: inherit; background: #ff6213; }
    .eq-shop-account .wl-steps { list-style: none; margin: 0; padding: 8px 24px 4px; }
    .eq-shop-account .wl-step { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f1f1f1; }
    .eq-shop-account .wl-step:last-child { border-bottom: 0; }
    .eq-shop-account .wl-step-ic { flex: 0 0 26px; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; color: #ff6213; background: #fff1e8; border: 1px solid rgba(255, 98, 19, .35); }
    .eq-shop-account .wl-step.is-done .wl-step-ic { color: #fff; background: #178a3c; border-color: #178a3c; }
    .eq-shop-account .wl-step-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .eq-shop-account .wl-step-body strong { font-size: 14px; color: #141516; }
    .eq-shop-account .wl-step-body small { font-size: 12.5px; color: #6b7280; line-height: 1.4; overflow-wrap: anywhere; }
    .eq-shop-account .wl-step.is-done .wl-step-body strong { color: #4b5563; }
    .eq-shop-account .wl-step-cta { flex-shrink: 0; font-size: 12.5px; font-weight: 700; color: #ff6213; text-decoration: none; white-space: nowrap; }
    .eq-shop-account .wl-step-cta:hover { color: #de4a00; text-decoration: underline; }
    .eq-shop-account .wl-step-ok { flex-shrink: 0; font-size: 12px; font-weight: 700; color: #178a3c; }
    .eq-shop-account .wl-actions { display: flex; flex-wrap: wrap; gap: 10px; padding: 14px 24px 22px; }
    .eq-shop-account .wl-btn { display: inline-flex; align-items: center; height: 38px; padding: 0 16px; border: 1px solid rgba(0, 0, 0, .15); border-radius: 8px; background: #fff; color: #141516; font: inherit; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .eq-shop-account .wl-btn:hover { border-color: #ff6213; color: #de4a00; }
    .eq-shop-account .wl-btn--primary { background: #ff6213; border-color: #ff6213; color: #fff; }
    .eq-shop-account .wl-btn--primary:hover { background: #de4a00; border-color: #de4a00; color: #fff; }
    .eq-shop-account .wl-btn--ghost { border-color: transparent; color: #6b7280; margin-left: auto; }
    @media (max-width: 640px) {
        .eq-shop-account .wl-hero { padding: 18px 48px 18px 16px; gap: 12px; }
        .eq-shop-account .wl-title { font-size: 18px; }
        .eq-shop-account .wl-progress, .eq-shop-account .wl-steps, .eq-shop-account .wl-actions { padding-left: 16px; padding-right: 16px; }
        .eq-shop-account .wl-step { flex-wrap: wrap; }
        .eq-shop-account .wl-step-cta, .eq-shop-account .wl-step-ok { margin-left: 38px; }
        .eq-shop-account .wl-btn { flex: 1 1 100%; justify-content: center; }
        .eq-shop-account .wl-btn--ghost { margin-left: 0; }
    }
</style>
