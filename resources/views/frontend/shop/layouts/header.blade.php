<header class="eq-header" x-data="megaMenu()" @mouseleave="close()">
  <div class="eq-header__topbar">
    <span>Envío gratis a partir de $1,000 MXN toda la República Mexicana</span>
  </div>

  <div class="eq-header__row">
    <div class="eq-header__row-inner">
      <a href="{{ route('home') }}" class="eq-header__logo" aria-label="Ir al inicio">
        <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Equiterm Industries" width="140" height="40">
      </a>

      <form action="{{ route('catalog.index') }}" method="GET" class="eq-header__search">
        <input type="text" name="q" placeholder="Buscar calderas, calentadores, refacciones..." value="{{ request('q') }}">
        <button type="submit" aria-label="Buscar">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
        </button>
      </form>

      <div class="eq-header__actions">
        <a href="#" class="eq-header__action">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
          <span>Iniciar sesión</span>
        </a>
        <a href="#" class="eq-header__action">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.5-8 11-8 11z" stroke-linejoin="round"/></svg>
          <span>Favoritos</span>
        </a>
        <button type="button" class="eq-header__action eq-header__cart" @click="$store.shop.toggleCart()">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="21" r="1.4" fill="currentColor" stroke="none"/><circle cx="18" cy="21" r="1.4" fill="currentColor" stroke="none"/></svg>
          <span>Carrito</span>
          <span class="eq-header__cart-badge" x-text="$store.shop.cartCount">0</span>
        </button>
      </div>
    </div>
  </div>

  <div class="eq-header__nav-row">
    <nav class="eq-header__nav">
      <div class="eq-header__nav-item" @mouseenter="open('categorias')">
        <span>Categorías
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>

      <div class="eq-header__nav-item" @mouseenter="open('servicios')">
        <span>Servicios
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>

      @foreach ($headerMainItems as $item)
        <a href="{{ $item->url ?? '#' }}" target="{{ $item->target }}" @mouseenter="close()">{{ $item->title }}</a>
      @endforeach

      {{-- MEGA MENU: CATEGORÍAS --}}
      <div class="eq-mega" x-show="activeMenu === 'categorias'" x-cloak @mouseenter="cancelClose()">
        <div class="eq-mega__col eq-mega__col--categories">
          @foreach ($megaMenuCategories as $category)
            <div class="eq-mega__item" :class="{ 'is-active': activeCategoryId === {{ $category->id }} }" @mouseenter="setCategory({{ $category->id }})">
              {{ $category->name }}
            </div>
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--brands">
          <div class="eq-mega__col-label">Marcas</div>
          @foreach ($megaMenuCategoryBrands as $categoryId => $brands)
            <template x-if="activeCategoryId === {{ $categoryId }}">
              <div>
                @foreach ($brands as $entry)
                  <div class="eq-mega__item" :class="{ 'is-active': activeBrandId === {{ $entry['brand']->id }} }" @mouseenter="setBrand({{ $entry['brand']->id }})">
                    <span>{{ $entry['brand']->name }}</span>
                    <span class="eq-mega__count">{{ $entry['count'] }}</span>
                  </div>
                @endforeach
              </div>
            </template>
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--models">
          <div class="eq-mega__col-label">Modelos</div>
          @foreach ($megaMenuBrandProducts as $key => $products)
            @php [$catId, $brandId] = explode('-', $key); @endphp
            <template x-if="activeCategoryId === {{ (int) $catId }} && activeBrandId === {{ (int) $brandId }}">
              <div>
                @foreach ($products as $product)
                  <a href="{{ route('product.show', $product->slug) }}" class="eq-mega__model">{{ $product->name }}</a>
                @endforeach
              </div>
            </template>
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--featured">
          <div class="eq-mega__col-label">Productos destacados</div>
          <div class="eq-mega__grid">
            @foreach ($megaMenuCategoryProducts as $categoryId => $products)
              <template x-if="activeCategoryId === {{ $categoryId }}">
                <div class="eq-mega__grid-row">
                  @foreach ($products as $product)
                    <x-frontend.shop.product-card :product="$product" compact="true" />
                  @endforeach
                </div>
              </template>
            @endforeach
          </div>
          <a href="{{ route('catalog.index') }}" class="eq-mega__see-more">Ver más productos</a>
        </div>
      </div>

      {{-- MEGA MENU: SERVICIOS --}}
      <div class="eq-mega eq-mega--servicios" x-show="activeMenu === 'servicios'" x-cloak @mouseenter="cancelClose()">
        @forelse ($headerServiciosItems as $item)
          <div class="eq-mega__col">
            <h4>{{ $item->title }}</h4>
            @foreach ($item->children as $child)
              <a href="{{ $child->url ?? '#' }}" target="{{ $child->target }}">{{ $child->title }}</a>
            @endforeach
          </div>
        @empty
          <div class="eq-mega__col"><p>Próximamente</p></div>
        @endforelse
      </div>
    </nav>
  </div>
</header>
