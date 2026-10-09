@php
    $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();
@endphp

@if ($brands->count() > 0)
<section class="brand-carousel">
    <div class="brand-carousel__label">Marcas con las que trabajamos y distribuimos</div>
    <div class="brand-carousel__track">
        @foreach ($brands as $brand)
            @php
                // URL de redirección configurada en Gestión de Marcas. Solo se
                // usa si es http(s) o ruta del sitio; una externa abre en
                // pestaña nueva. Sin URL, el logo queda sin enlace.
                $brandUrl = trim((string) $brand->redirect_url);
                $brandUrl = preg_match('#^(https?://|/)#i', $brandUrl) ? $brandUrl : null;
                $brandExternal = $brandUrl
                    && preg_match('#^https?://#i', $brandUrl)
                    && parse_url($brandUrl, PHP_URL_HOST) !== parse_url(url('/'), PHP_URL_HOST);
            @endphp
            @if ($brandUrl)
                <a href="{{ $brandUrl }}" class="brand-carousel__item brand-carousel__item--link"
                    @if ($brandExternal) target="_blank" rel="noopener" @endif
                    aria-label="{{ $brand->name }}">
            @else
                <div class="brand-carousel__item">
            @endif
                @if ($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" loading="lazy">
                @else
                    <span class="brand-carousel__name">{{ $brand->name }}</span>
                @endif
            @if ($brandUrl)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
</section>
@endif
