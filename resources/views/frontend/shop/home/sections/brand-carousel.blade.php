@php
    $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();
@endphp

@if ($brands->count() > 0)
<section class="brand-carousel">
    <div class="brand-carousel__label">Marcas con las que trabajamos y distribuimos</div>
    {{-- Cinta continua: el mismo grupo de logos se pinta dos veces y el CSS
         desplaza ambos para que el bucle no tenga saltos. La copia va con
         aria-hidden y sin foco para no duplicar los enlaces a lectores de
         pantalla ni al teclado. --}}
    <div class="brand-carousel__track" style="--brand-count: {{ $brands->count() }}">
        @foreach ([false, true] as $isClone)
        <div class="brand-carousel__group" @if ($isClone) aria-hidden="true" @endif>
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
                        @if ($isClone) tabindex="-1" @else aria-label="{{ $brand->name }}" @endif>
                @else
                    <div class="brand-carousel__item">
                @endif
                    @if ($brand->logo_url)
                        <img src="{{ $brand->logo_url }}" alt="{{ $isClone ? '' : (trim((string) $brand->logo_alt) !== '' ? trim($brand->logo_alt) : $brand->name) }}" loading="lazy">
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
        @endforeach
    </div>
</section>
@endif
