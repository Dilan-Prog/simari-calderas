{{-- Panel 7: Bloques dinámicos de la página de producto.
     Se incluye DENTRO del <form> de edición, así que nada aquí tiene `name`
     ni botones submit: todo se guarda por AJAX (ProductBlockController) y no
     participa del "Publicar/Guardar borrador" del formulario. Lógica en
     _blocks_scripts.blade.php (modales + JS). --}}
<div class="pform-tab-panel" id="pformPanel7" role="tabpanel">
    <div class="pform-panel pblocks" id="pblocksRoot"
        data-product-id="{{ $product->id }}"
        data-base-url="{{ route('admin.products.blocks.index', $product->id) }}"
        data-templates-url="{{ route('admin.products.blocks.templates') }}">

        <div class="pform-specs-header">
            <div>
                <h2 class="pform-panel-title">Bloques de la página de producto</h2>
                <p class="pform-hint" style="margin:0">Secciones que se muestran en el detalle de este producto.
                    Una <strong>plantilla</strong> se comparte entre varios productos (si la editas, cambia en todos);
                    una sección <strong>propia</strong> solo existe en este producto. Arrastra para reordenar — el
                    orden y la visibilidad se guardan solos.</p>
            </div>
            <span class="pblocks-status" id="pblocksStatus" aria-live="polite"></span>
        </div>

        @foreach ([
            'stack' => ['Pila principal', 'Bloques a todo el ancho, debajo de la ficha del producto.'],
            'sidebar' => ['Columna lateral', 'Bloques de la columna junto al producto, arriba de «Medios de pago».'],
        ] as $zone => [$zoneLabel, $zoneHint])
            <section class="pblocks-zone" data-zone="{{ $zone }}">
                <header class="pblocks-zone-head">
                    <div>
                        <h3 class="pblocks-zone-title">{{ $zoneLabel }}</h3>
                        <p class="pform-hint" style="margin:0">{{ $zoneHint }}</p>
                    </div>
                    <div class="pblocks-zone-actions">
                        <button type="button" class="pform-btn outline pblocks-use-template" data-zone="{{ $zone }}">
                            Usar plantilla
                        </button>
                        <button type="button" class="pform-btn primary pblocks-new-custom" data-zone="{{ $zone }}">
                            Nueva sección personalizada
                        </button>
                    </div>
                </header>
                <ul class="pblocks-list" data-zone="{{ $zone }}"></ul>
                <div class="pblocks-empty" data-zone="{{ $zone }}">
                    Aún no hay bloques en esta zona.
                </div>
            </section>
        @endforeach
    </div>
</div>
