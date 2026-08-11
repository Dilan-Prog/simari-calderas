<header class="eq-header" x-data="megaMenu()">
  <div class="eq-header__topbar">
    <span>Envío gratis a partir de $1,000 MXN toda la República Mexicana</span>
  </div>

  <div class="eq-header__row">
    <div class="eq-header__row-inner">
      <a href="{{ route('home') }}" class="eq-header__logo" aria-label="Ir al inicio">
        <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Equiterm Industries" width="140" height="40">
      </a>

      <div class="eq-search" x-data="searchOverlay()" @keydown.escape.window="close()">
        <form class="eq-header__search" @submit.prevent="submit()">
          <input type="text" x-model="query" @input="onInput()" @focus="onFocus()" placeholder="Buscar calderas, calentadores, refacciones..." autocomplete="off">
          <button type="submit" aria-label="Buscar">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
          </button>
        </form>

        <template x-teleport="body">
          <div class="eq-search-overlay" x-show="open" x-cloak @click.self="close()">
            <div class="eq-search-overlay__panel">
              <button type="button" class="eq-search-overlay__close" @click="close()" aria-label="Cerrar búsqueda">&times;</button>

              <div class="eq-search-overlay__sidebar">
                <div class="eq-search-overlay__block">
                  <div class="eq-search-overlay__label">Ordenar</div>
                  <select class="eq-search-overlay__select" x-model="orden" @change="fetchResults()">
                    <option value="relevancia">Relevancia</option>
                    <option value="descuento">Mayor descuento</option>
                    <option value="precio_asc">Precio: menor a mayor</option>
                    <option value="precio_desc">Precio: mayor a menor</option>
                  </select>
                </div>

                <div class="eq-search-overlay__block" x-show="categories.length">
                  <div class="eq-search-overlay__label">Categorías</div>
                  <template x-for="cat in categories" :key="cat.id">
                    <button type="button" class="eq-search-overlay__facet" :class="{ 'is-active': selectedCategory === cat.id }" @click="toggleCategory(cat.id)">
                      <span x-text="cat.name"></span>
                      <span class="eq-search-overlay__facet-count" x-text="cat.count"></span>
                    </button>
                  </template>
                </div>

                <div class="eq-search-overlay__block" x-show="brands.length">
                  <div class="eq-search-overlay__label">Marcas</div>
                  <template x-for="brand in brands" :key="brand.id">
                    <button type="button" class="eq-search-overlay__facet" :class="{ 'is-active': selectedBrand === brand.id }" @click="toggleBrand(brand.id)">
                      <span x-text="brand.name"></span>
                      <span class="eq-search-overlay__facet-count" x-text="brand.count"></span>
                    </button>
                  </template>
                </div>
              </div>

              <div class="eq-search-overlay__main">
                <div class="eq-search-overlay__main-header">
                  <h2>Productos <span x-show="total > 0" x-text="'(' + total + ')'"></span></h2>
                  <a :href="viewAllUrl" class="eq-search-overlay__view-all">Ver todos los resultados</a>
                </div>
                <div class="eq-search-overlay__grid" x-html="productsHtml"></div>
                <div class="eq-search-overlay__loading" x-show="loading" x-cloak>Buscando...</div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <div class="eq-header__actions">
        @if (Auth::guard('customer')->check())
          @php $headerCustomer = Auth::guard('customer')->user(); @endphp
          <div class="eq-header__user" x-data="{ userOpen: false, logoutOpen: false }" @click.outside="userOpen = false">
            <button type="button" class="eq-header__action eq-header__user-btn" @click="userOpen = !userOpen">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
              <span>Mi Cuenta</span>
            </button>
            <div class="eq-header__user-menu" x-show="userOpen" x-cloak>
              <div class="eq-header__user-menu-head">
                <div class="eq-header__user-menu-name">Hola, {{ $headerCustomer->first_name }}</div>
                <div class="eq-header__user-menu-email">{{ $headerCustomer->email }}</div>
              </div>
              <a href="{{ route('shop.account') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
                Mi perfil
              </a>
              <a href="{{ route('shop.account') }}#pedidos">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="17" rx="1.5"/><path d="M8 9h8M8 13h8M8 17h5" stroke-linecap="round"/></svg>
                Mis pedidos
              </a>
              <a href="{{ route('shop.account') }}#direcciones">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-7.2 7-12a7 7 0 1 0-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
                Direcciones
              </a>
              <a href="{{ route('shop.account') }}#pagos">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18" stroke-linecap="round"/></svg>
                Métodos de pago
              </a>
              <a href="{{ route('shop.account') }}#favoritos">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.5-8 11-8 11z" stroke-linejoin="round"/></svg>
                Favoritos
              </a>
              @if ($headerCustomer->portal_access)
                <a href="{{ route('customer.dashboard') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4l-5 5V21h3.3l5-5a4.5 4.5 0 0 0 6.4-6.4l-2.8 2.8-2.3-2.3 2.8-2.8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Portal de servicios
                </a>
              @endif
              <div class="eq-header__user-menu-footer">
                <button type="button" @click="logoutOpen = true; userOpen = false">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Cerrar sesión
                </button>
              </div>
            </div>

            <template x-teleport="body">
              <div class="eq-modal" x-show="logoutOpen" x-cloak @click.self="logoutOpen = false" @keydown.escape.window="logoutOpen = false">
                <div class="eq-modal__card">
                  <div class="eq-modal__icon eq-modal__icon--danger">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                  <div class="eq-modal__title">¿Cerrar sesión?</div>
                  <div class="eq-modal__text">Tendrás que iniciar sesión de nuevo para acceder a tu cuenta.</div>
                  <div class="eq-modal__actions">
                    <button type="button" class="eq-modal__btn" @click="logoutOpen = false">Cancelar</button>
                    <form method="POST" action="{{ route('shop.logout') }}" style="flex:1;display:flex;">
                      @csrf
                      <button type="submit" class="eq-modal__btn eq-modal__btn--danger">Cerrar sesión</button>
                    </form>
                  </div>
                </div>
              </div>
            </template>
          </div>
        @else
          <a href="{{ route('shop.login') }}" class="eq-header__action">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
            <span>Iniciar sesión</span>
          </a>
        @endif
        <a href="#" class="eq-header__action">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.5-8 11-8 11z" stroke-linejoin="round"/></svg>
          <span>Favoritos</span>
        </a>
        <a href="{{ route('checkout.index') }}" class="eq-header__action eq-header__cart">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 8H6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="21" r="1.4" fill="currentColor" stroke="none"/><circle cx="18" cy="21" r="1.4" fill="currentColor" stroke="none"/></svg>
          <span>Carrito</span>
          <span class="eq-header__cart-badge" x-text="$store.shop.cartCount">0</span>
        </a>
      </div>
    </div>
  </div>

  <div class="eq-header__nav-row">
    <nav class="eq-header__nav">
      <div class="eq-header__nav-item" @click="open('categorias')" @mouseleave="close()">
        <span>Categorías
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>

      <div class="eq-header__nav-item" @click="open('servicios')" @mouseleave="close()">
        <span>Servicios
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </div>

      @foreach ($headerMainItems as $item)
        <a href="{{ $item->url ?? '#' }}" target="{{ $item->target }}" @mouseenter="close()">{{ $item->title }}</a>
      @endforeach

      {{-- MEGA MENU: CATEGORÍAS --}}
      <div class="eq-mega" x-show="activeMenu === 'categorias'" x-cloak @mouseenter="cancelClose()" @mouseleave="close()">
        <div class="eq-mega__col eq-mega__col--categories">
          @foreach ($megaMenuCategories as $category)
            <a href="{{ route('catalog.category', $category->slug) }}" class="eq-mega__item" :class="{ 'is-active': activeCategoryId === {{ $category->id }} }" @mouseenter="setCategory({{ $category->id }})">
              {{ $category->name }}
            </a>
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--brands">
          @foreach ($megaMenuCategories as $category)
            <template x-if="activeCategoryId === {{ $category->id }}">
              <div>
                @forelse ($category->children as $sub)
                  <a href="{{ route('catalog.category', $sub->slug) }}" class="eq-mega__item" :class="{ 'is-active': activeSubCategoryId === {{ $sub->id }} }" @mouseenter="setSubCategory({{ $sub->id }})">
                    <span>{{ $sub->name }}</span>
                  </a>
                @empty
                  <p class="eq-mega__empty">Sin subcategorías</p>
                @endforelse
              </div>
            </template>
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--models">
          @foreach ($megaMenuCategories as $category)
            @foreach ($category->children as $sub)
              <template x-if="activeCategoryId === {{ $category->id }} && activeSubCategoryId === {{ $sub->id }}">
                <div>
                  @forelse ($sub->children as $child)
                    <a href="{{ route('catalog.category', $child->slug) }}" class="eq-mega__model" @mouseenter="setModel({{ $child->id }})">{{ $child->name }}</a>
                  @empty
                    <p class="eq-mega__empty">Sin categorías hijas</p>
                  @endforelse
                </div>
              </template>
            @endforeach
          @endforeach
        </div>

        <div class="eq-mega__col eq-mega__col--featured">
          <div class="eq-mega__col-label">Productos destacados</div>
          <div class="eq-mega__grid">
            {{--
              FIX: $megaMenuCategoryProducts agrupa por el category_id
              PROPIO de cada producto — que casi siempre es una subcategoría
              (hoja), no la categoría padre. Antes esto solo comparaba contra
              activeCategoryId (padre), así que la columna quedaba vacía en
              cuanto una categoría tenía subcategorías (el caso normal): el
              grupo de productos vive bajo el id de la subcategoría, nunca
              bajo el del padre. Ahora compara contra la subcategoría activa
              cuando hay una elegida, y contra la categoría padre solo
              cuando todavía no se ha entrado a ninguna subcategoría (padres
              sin hijas, p. ej. "Equipos de Refrigeración").
              Además ahora existe un 3er nivel (activeModelId, categoría
              hija / "modelo"). La condición prioriza el nivel más
              específico activo: hija > subcategoría > categoría padre.
            --}}
            @foreach ($megaMenuCategoryProducts as $categoryId => $products)
              <template x-if="activeModelId === {{ $categoryId }}
                  || (activeModelId === null && activeSubCategoryId === {{ $categoryId }})
                  || (activeModelId === null && activeSubCategoryId === null && activeCategoryId === {{ $categoryId }})">
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
      <div class="eq-mega eq-mega--servicios" x-show="activeMenu === 'servicios'" x-cloak @mouseenter="cancelClose()" @mouseleave="close()">
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
