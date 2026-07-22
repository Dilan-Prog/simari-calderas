@extends('admin.layouts.master')
@section('title')
    {{ $collection->name }} - Productos - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/collections.css')
@endpush

@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <a href="{{ route('admin.collections.index') }}" class="breadcrumb-clients-manager" style="margin-bottom:4px;display:inline-block;">
                &larr; Volver a Colecciones
            </a>
            <h1 style="margin:0 0 4px;">{{ $collection->name }}</h1>
            <p class="breadcrumb-clients-manager main">
                Gestiona los productos de esta colección manual y su orden de aparición
            </p>
        </div>
        <span class="collection-type-badge manual">Manual</span>
    </div>

    {{-- Product search --}}
    <div class="collection-product-search" id="collectionProductSearch">
        <div class="collection-product-search__input-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" id="collectionProductInput" class="collection-product-search__input"
                placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
        </div>
        <div class="collection-product-search__dropdown" id="collectionProductDropdown" style="display:none;">
            <div class="collection-product-search__empty" id="collectionProductEmpty" style="display:none;">
                Sin resultados
            </div>
            <ul class="collection-product-search__list" id="collectionProductList"></ul>
        </div>
    </div>

    {{-- Products grid --}}
    <div class="collection-products-grid" id="collectionProductsGrid" data-collection-id="{{ $collection->id }}">
        @forelse ($collection->manualProducts as $product)
            <div class="collection-product-card" draggable="true" data-product-id="{{ $product->id }}">
                <span class="collection-product-card__handle">⋮⋮</span>
                <div class="collection-product-card__img">
                    @if ($product->cover_image_url)
                        <img src="{{ $product->cover_image_url }}" alt="{{ $product->name }}">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    @endif
                </div>
                <div class="collection-product-card__info">
                    <span class="collection-product-card__name">{{ $product->name }}</span>
                    <span class="collection-product-card__sku">SKU: {{ $product->sku }}</span>
                </div>
                <span class="collection-product-card__price">${{ number_format($product->price, 2) }}</span>
                <button type="button" class="collection-product-card__remove btn-remove-product"
                    data-product-id="{{ $product->id }}" aria-label="Quitar producto">✕</button>
            </div>
        @empty
            <p class="collection-products-empty" id="collectionProductsEmpty">
                Esta colección todavía no tiene productos. Usa el buscador de arriba para agregar.
            </p>
        @endforelse
    </div>

</section>
@include('admin.components.center-toast')
</div>
@endsection

@push('scripts')
<script>
(function () {
    const collectionId = document.getElementById('collectionProductsGrid').dataset.collectionId;
    const searchUrl   = '{{ route('admin.collections.products.search') }}';
    const addUrl      = '{{ url('/admin/colecciones') }}/' + collectionId + '/productos';
    const removeUrlBase = '{{ url('/admin/colecciones') }}/' + collectionId + '/productos';
    const reorderUrl  = '{{ url('/admin/colecciones') }}/' + collectionId + '/productos/reordenar';
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    const input     = document.getElementById('collectionProductInput');
    const dropdown  = document.getElementById('collectionProductDropdown');
    const list      = document.getElementById('collectionProductList');
    const emptyMsg  = document.getElementById('collectionProductEmpty');
    const grid      = document.getElementById('collectionProductsGrid');

    let debounceTimer = null;

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatPrice(n) {
        return '$' + parseFloat(n ?? 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function hideDropdown() {
        dropdown.style.display = 'none';
        list.innerHTML = '';
    }

    function renderResults(products) {
        list.innerHTML = '';
        if (!products.length) {
            emptyMsg.style.display = 'block';
            list.style.display = 'none';
            return;
        }
        emptyMsg.style.display = 'none';
        list.style.display = 'block';

        products.forEach(p => {
            const li = document.createElement('li');
            li.className = 'collection-product-search__item';
            li.innerHTML = `
                <div class="collection-product-search__item-img">
                    ${p.cover_image_url ? `<img src="${esc(p.cover_image_url)}" alt="${esc(p.name)}">` : ''}
                </div>
                <div class="collection-product-search__item-info">
                    <span class="collection-product-search__item-name">${esc(p.name)}</span>
                    <span class="collection-product-search__item-sku">SKU: ${esc(p.sku)}</span>
                </div>
                <span class="collection-product-search__item-price">${formatPrice(p.price)}</span>
            `;
            li.addEventListener('click', () => addProduct(p));
            list.appendChild(li);
        });
    }

    async function searchProducts(q) {
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q);
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return [];
            return await res.json();
        } catch (err) {
            console.error(err);
            return [];
        }
    }

    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 2) { hideDropdown(); return; }

        debounceTimer = setTimeout(async () => {
            const products = await searchProducts(q);
            dropdown.style.display = 'block';
            renderResults(products);
        }, 300);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#collectionProductSearch')) hideDropdown();
    });

    function removeEmptyState() {
        const emptyState = document.getElementById('collectionProductsEmpty');
        if (emptyState) emptyState.remove();
    }

    function buildProductCard(product) {
        const card = document.createElement('div');
        card.className = 'collection-product-card';
        card.draggable = true;
        card.dataset.productId = product.id;
        card.innerHTML = `
            <span class="collection-product-card__handle">⋮⋮</span>
            <div class="collection-product-card__img">
                ${product.cover_image_url ? `<img src="${esc(product.cover_image_url)}" alt="${esc(product.name)}">` : ''}
            </div>
            <div class="collection-product-card__info">
                <span class="collection-product-card__name">${esc(product.name)}</span>
                <span class="collection-product-card__sku">SKU: ${esc(product.sku)}</span>
            </div>
            <span class="collection-product-card__price">${formatPrice(product.price)}</span>
            <button type="button" class="collection-product-card__remove btn-remove-product"
                data-product-id="${product.id}" aria-label="Quitar producto">✕</button>
        `;
        wireCard(card);
        return card;
    }

    async function addProduct(product) {
        try {
            const res = await fetch(addUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: product.id }),
            });
            const data = await res.json();

            if (res.ok && data.success) {
                removeEmptyState();
                grid.appendChild(buildProductCard(data.product));
                showCenterToast('Producto agregado a la colección.');
            } else {
                showCenterToast(data.message ?? 'No se pudo agregar el producto.', 'error');
            }
        } catch (err) {
            console.error(err);
            showCenterToast('Error de conexión al agregar el producto.', 'error');
        }

        input.value = '';
        hideDropdown();
    }

    async function removeProduct(productId, card) {
        try {
            const res = await fetch(`${removeUrlBase}/${productId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _method: 'DELETE' }),
            });
            const data = await res.json();

            if (res.ok && data.success) {
                card.remove();
                if (!grid.querySelector('.collection-product-card')) {
                    const p = document.createElement('p');
                    p.className = 'collection-products-empty';
                    p.id = 'collectionProductsEmpty';
                    p.textContent = 'Esta colección todavía no tiene productos. Usa el buscador de arriba para agregar.';
                    grid.appendChild(p);
                }
                showCenterToast('Producto quitado de la colección.');
            } else {
                showCenterToast(data.message ?? 'No se pudo quitar el producto.', 'error');
            }
        } catch (err) {
            console.error(err);
            showCenterToast('Error de conexión al quitar el producto.', 'error');
        }
    }

    async function persistOrder() {
        const order = Array.from(grid.querySelectorAll('.collection-product-card'))
            .map(card => parseInt(card.dataset.productId, 10));

        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                showCenterToast(data.message ?? 'No se pudo guardar el nuevo orden.', 'error');
            } else {
                showCenterToast('Orden actualizado.');
            }
        } catch (err) {
            console.error(err);
            showCenterToast('Error de conexión al guardar el orden.', 'error');
        }
    }

    // ── Drag to reorder ──
    let draggedCard = null;

    function wireCard(card) {
        card.querySelector('.btn-remove-product').addEventListener('click', () => {
            removeProduct(card.dataset.productId, card);
        });

        card.addEventListener('dragstart', function () {
            draggedCard = card;
            card.classList.add('is-dragging');
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            draggedCard = null;
            persistOrder();
        });

        card.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!draggedCard || draggedCard === card) return;

            const rect = card.getBoundingClientRect();
            const isAfter = (e.clientY - rect.top) > rect.height / 2;
            card.parentNode.insertBefore(draggedCard, isAfter ? card.nextSibling : card);
        });
    }

    grid.querySelectorAll('.collection-product-card').forEach(wireCard);
})();
</script>
@endpush
