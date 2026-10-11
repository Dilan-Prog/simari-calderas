@props(['groups' => true])
{{--
    Cascarón de calendario (lo rellena resources/js/admin/erp-production.js → initCalendar sobre [data-service-calendar]).
      Barra:   anterior / hoy / siguiente + título · selector Mes/Semana/Día · (opc.) agrupar por técnico / proyecto
      Slots:   filters (selects con data-cal-filter="campo"), legend (leyenda de colores)
    Marcadores que usa el JS: data-cal-alerts, data-cal-title, data-cal-body, data-cal-panel, data-cal-toast.
    El panel lateral muestra el evento seleccionado; en pantallas angostas la rejilla se sustituye por una agenda por día.
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush
<div class="erp-cal">
    <div class="cal-alerts" data-cal-alerts hidden></div>
    <div class="cal-toast" data-cal-toast role="status" hidden></div>

    <div class="erp-card cal-shell">
        <div class="cal-bar">
            <div class="cal-nav">
                <button type="button" class="erp-btn erp-btn--sm" data-cal-prev aria-label="Anterior">‹</button>
                <button type="button" class="erp-btn erp-btn--sm" data-cal-today>Hoy</button>
                <button type="button" class="erp-btn erp-btn--sm" data-cal-next aria-label="Siguiente">›</button>
                <strong class="cal-title" data-cal-title aria-live="polite"></strong>
            </div>
            <div class="cal-segs">
                <div class="cal-seg" role="group" aria-label="Vista">
                    <button type="button" class="cal-seg-btn is-active" data-cal-view="month" aria-pressed="true">Mes</button>
                    <button type="button" class="cal-seg-btn" data-cal-view="week" aria-pressed="false">Semana</button>
                    <button type="button" class="cal-seg-btn" data-cal-view="day" aria-pressed="false">Día</button>
                </div>
                @if ($groups)
                    <div class="cal-seg" role="group" aria-label="Agrupar" data-cal-groups hidden>
                        <button type="button" class="cal-seg-btn is-active" data-cal-group="tech" aria-pressed="true">Por técnico</button>
                        <button type="button" class="cal-seg-btn" data-cal-group="proj" aria-pressed="false">Por proyecto</button>
                    </div>
                @endif
            </div>
        </div>

        @isset($filters)<div class="cal-filterbar">{{ $filters }}</div>@endisset
        @isset($legend)<div class="cal-legend">{{ $legend }}</div>@endisset

        <div class="cal-layout">
            <div class="cal-body" data-cal-body><div class="cal-loading">Cargando…</div></div>
            <aside class="cal-panel" data-cal-panel aria-label="Detalle del servicio seleccionado"></aside>
        </div>
    </div>
</div>
