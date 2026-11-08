{{-- Miniatura de una plantilla de correo: se dibuja con los colores del tema, sin imágenes. --}}
@php $layout = $theme['layout'] ?? 'standard'; @endphp
<span class="oe-thumb" style="background:{{ $theme['page'] }};">
    <span class="oe-thumb__card" style="background:{{ $theme['card'] }};border-radius:{{ $layout === 'flat' ? '0' : '5px' }};">
        @if ($layout === 'banner')
            <span class="oe-thumb__band oe-thumb__band--tall" style="background:{{ $theme['header_bg'] }};">
                <i style="background:{{ $theme['header_color'] }};width:34%;opacity:.7;"></i>
                <i style="background:{{ $theme['header_color'] }};width:72%;height:6px;"></i>
            </span>
        @elseif ($layout === 'flat')
            <span class="oe-thumb__flat" style="border-top:3px solid {{ $theme['accent'] }};">
                <i style="background:{{ $theme['heading'] }};width:30%;"></i>
            </span>
            <span class="oe-thumb__heading"><i style="background:{{ $theme['heading'] }};width:62%;"></i></span>
        @else
            <span class="oe-thumb__band" style="background:{{ $theme['header_bg'] }};">
                <i style="background:{{ $theme['header_color'] }};width:34%;"></i>
            </span>
            <span class="oe-thumb__heading"><i style="background:{{ $theme['heading'] }};width:62%;"></i></span>
        @endif
        <span class="oe-thumb__lines">
            <i style="width:92%;"></i><i style="width:78%;"></i>
        </span>
        <span class="oe-thumb__box" style="background:{{ $theme['accent_soft'] }};border-color:{{ $theme['accent_line'] }};">
            <i style="background:{{ $theme['accent'] }};width:40%;"></i>
            <i style="background:{{ $theme['accent_line'] }};width:70%;"></i>
        </span>
    </span>
</span>
