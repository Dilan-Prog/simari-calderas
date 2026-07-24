@php
    $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->get();
@endphp

@if ($brands->count() > 0)
<section class="brand-carousel">
    <div class="brand-carousel__label">Marcas con las que trabajamos y distribuimos</div>
    <div class="brand-carousel__track">
        @foreach ($brands as $brand)
            <div class="brand-carousel__item">
                @if ($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" loading="lazy">
                @else
                    <span class="brand-carousel__name">{{ $brand->name }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
