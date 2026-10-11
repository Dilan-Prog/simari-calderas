{{-- Panel 8: Lista de materiales (ERP Fase 2A). Solo lectura + enlaces al módulo. --}}
@php
    $bomSvc = app(\App\Services\BomService::class);
    $activeBom = $product->activeBom()->withCount('lines')->first();
    $bomCanCreate = auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', 'create');
    $bomCanView = auth()->user()->isAdmin() || auth()->user()->hasPermission('boms');
    $bomCost = $activeBom ? $bomSvc->costOfBom($activeBom) : 0;
    $bomPrice = $product->base_price;
    $bomMargin = $bomCost > 0 && $bomPrice > 0 ? round((($bomPrice - $bomCost) / $bomPrice) * 100, 1) : null;
    $bomVersions = $product->boms()->count();
@endphp
<div class="pform-tab-panel" id="pformPanel8" role="tabpanel">
    <div class="pform-card">
        <h2 class="pform-card-title">Lista de materiales
            @if ($activeBom)<span class="pform-supplier-badge">Producto fabricado</span>@endif
        </h2>
        @if ($activeBom)
            <p class="pform-hint">
                Receta vigente: <strong>v{{ $activeBom->version }}{{ $activeBom->name ? ' · ' . $activeBom->name : '' }}</strong>
                · {{ $activeBom->lines_count }} componente(s) · {{ $bomVersions }} versión(es) en total.
            </p>
            <p class="pform-hint">
                Costo estándar: <strong>${{ number_format($bomCost, 2) }}</strong>
                · Precio sin IVA: <strong>${{ number_format($bomPrice, 2) }}</strong>
                @if ($bomMargin !== null)· Margen: <strong>{{ $bomMargin }}%</strong>@endif
            </p>
            @if ($bomCanView)
                <p>
                    <a class="pform-btn-secondary" href="{{ route('admin.boms.show', $activeBom) }}">Ver receta y árbol</a>
                    @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', 'edit'))
                        <a class="pform-btn-secondary" href="{{ route('admin.boms.edit', $activeBom) }}">Editar receta</a>
                    @endif
                </p>
            @endif
        @else
            <p class="pform-hint">Este producto no tiene una receta vigente (no se considera fabricado).</p>
            @if ($bomCanCreate)
                <p><a class="pform-btn-secondary" href="{{ route('admin.boms.create', ['product_id' => $product->id]) }}">Crear lista de materiales</a></p>
            @endif
        @endif
    </div>
</div>
