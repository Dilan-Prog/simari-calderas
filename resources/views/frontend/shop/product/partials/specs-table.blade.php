@if ($specifications->count() > 0)
<div id="especificaciones" class="product-specs">
    <div class="product-specs__title">Especificaciones técnicas</div>
    <div class="product-specs__table">
        @foreach ($specifications as $spec)
            <div class="product-specs__row">
                <div class="product-specs__label">{{ $spec['key'] ?? '' }}</div>
                <div class="product-specs__value">{{ $spec['value'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif
