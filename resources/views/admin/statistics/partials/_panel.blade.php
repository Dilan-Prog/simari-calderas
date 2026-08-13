{{--
    Panel reutilizable de Estadísticas — recibe un array $panel con el
    shape documentado en StatisticsController (id/title/subtitle/modes/
    rows/axis/series/...). El render real (donut/bars/table/line/columns/
    rank/stacked) lo hace resources/js/admin/statistics-panel.js a partir
    del JSON embebido abajo — este partial solo pinta el shell y los
    estados que dependen de PHP (disabled/foot), nunca el cuerpo del panel.
--}}
<div class="stats-panel {{ !empty($panel['span']) ? 'stats-panel--wide' : '' }}" data-panel-id="{{ $panel['id'] }}">
    <div class="stats-panel-head">
        <div class="stats-panel-titles">
            <h3 class="stats-panel-title">{{ $panel['title'] }}</h3>
            @if (!empty($panel['subtitle']))
                <div class="stats-panel-subtitle">{{ $panel['subtitle'] }}</div>
            @endif
        </div>
        @if (count($panel['modes'] ?? []) > 1)
            <div class="stats-panel-modes"></div>
        @endif
    </div>

    <div class="stats-panel-body"></div>

    @if (!empty($panel['disabled']))
        <div class="stats-panel-disabled">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><rect x="4.5" y="10.5" width="15" height="9.5" rx="2.2"/><path d="M8 10.5V8a4 4 0 018 0v2.5" stroke-linecap="round"/></svg>
            <span>{{ $panel['disabled'] }}</span>
        </div>
    @endif

    @if (!empty($panel['foot']))
        <div class="stats-panel-foot">{{ $panel['foot'] }}</div>
    @endif

    {{-- type="application/json" se lee como texto plano (scriptEl.textContent
         + JSON.parse), no se ejecuta como JS — por eso usa json_encode con
         flags HEX, no Illuminate\Support\Js::from() (ese helper genera una
         expresión JS tipo JSON.parse("...") pensada para <script> que SÍ se
         ejecuta, y rompería un JSON.parse() adicional del lado del cliente). --}}
    <script type="application/json" id="stats-panel-data-{{ $panel['id'] }}">{!! json_encode($panel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>
</div>
