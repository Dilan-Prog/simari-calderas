<aside class="catalog-filters" x-data="priceRangeSlider({{ (int) ($priceBounds->min_price ?? 0) }}, {{ (int) ($priceBounds->max_price ?? 500000) }}, {{ (int) request('precio_min', $priceBounds->min_price ?? 0) }}, {{ (int) request('precio_max', $priceBounds->max_price ?? 500000) }})">
    <form method="GET" action="{{ $category ? route('catalog.category', $category->slug) : route('catalog.index') }}">
        @if (request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif

        <div class="catalog-filters__panel">
            <div class="catalog-filters__title">Categorías</div>
            @foreach ($categoryOptions as $cat)
                <label class="catalog-filters__option">
                    <input type="checkbox" name="categoria[]" value="{{ $cat->id }}" @checked(in_array($cat->id, (array) request('categoria', [])))>
                    <span>{{ $cat->name }}</span>
                    <span class="catalog-filters__count">{{ $cat->products_count }}</span>
                </label>
            @endforeach
        </div>

        <div class="catalog-filters__panel">
            <div class="catalog-filters__title">Precio</div>
            <div class="catalog-filters__slider">
                <input type="range" :min="bounds[0]" :max="bounds[1]" step="1000" x-model.number="min" @input="syncMax()">
                <input type="range" :min="bounds[0]" :max="bounds[1]" step="1000" x-model.number="max" @input="syncMin()">
            </div>
            <div class="catalog-filters__range-inputs">
                <div>
                    <label>Desde</label>
                    <input type="number" name="precio_min" x-model.number="min">
                </div>
                <div>
                    <label>Hasta</label>
                    <input type="number" name="precio_max" x-model.number="max">
                </div>
            </div>
        </div>

        <div class="catalog-filters__panel">
            <div class="catalog-filters__title">Marcas</div>
            @foreach ($brandOptions as $brand)
                <label class="catalog-filters__option">
                    <input type="checkbox" name="marca[]" value="{{ $brand->id }}" @checked(in_array($brand->id, (array) request('marca', [])))>
                    <span>{{ $brand->name }}</span>
                    <span class="catalog-filters__count">{{ $brand->products_count }}</span>
                </label>
            @endforeach
        </div>

        <button type="submit" class="catalog-filters__apply">Aplicar filtros</button>
    </form>
</aside>
