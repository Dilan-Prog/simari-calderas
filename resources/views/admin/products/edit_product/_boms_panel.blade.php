{{-- Panel 8: Lista de materiales (ERP Fase 2A). Solo lectura + enlaces al módulo. Usa el sistema de componentes ERP. --}}
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
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px;">
                <x-erp.status value="Vigente" tone="ok" />
                <span class="pform-hint" style="margin: 0;">Receta vigente: <strong>v{{ $activeBom->version }}{{ $activeBom->name ? ' · ' . $activeBom->name : '' }}</strong></span>
            </div>
            <x-erp.facts :items="[
                ['label' => 'Componentes', 'value' => $activeBom->lines_count],
                ['label' => 'Versiones', 'value' => $bomVersions],
                ['label' => 'Costo estándar', 'value' => $bomCost > 0 ? '$' . number_format($bomCost, 2) : '—'],
                ['label' => 'Precio (s/IVA)', 'value' => $bomPrice > 0 ? '$' . number_format($bomPrice, 2) : '—'],
                ['label' => 'Margen', 'value' => $bomMargin !== null ? $bomMargin . '%' : '—'],
            ]" />
            @if ($bomCanView)
                <p style="display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0 0;">
                    <a class="erp-btn" href="{{ route('admin.boms.show', $activeBom) }}">Ver estructura y versiones</a>
                    @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('boms', 'edit'))
                        <a class="erp-btn" href="{{ route('admin.boms.edit', $activeBom) }}">Editar lista de materiales</a>
                    @endif
                </p>
            @endif
        @else
            <p class="pform-hint">Este producto no tiene una lista de materiales vigente (no se considera fabricado).</p>
            @if ($bomCanCreate)
                <p><a class="erp-btn erp-btn--primary" href="{{ route('admin.boms.create', ['product_id' => $product->id]) }}">Crear lista de materiales</a></p>
            @endif
        @endif
    </div>
</div>
