@php
    $depth = $depth ?? 0;
    $qtyFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
@endphp
@foreach ($nodes as $n)
    <li class="bom-node {{ $n['is_assembly'] ? 'is-assembly' : '' }}">
        <div class="erp-tree-row erp-tree-row--bom">
            <span class="bom-node-main" style="--lvl: {{ $depth }}">
                @if ($n['is_assembly'])
                    <button type="button" class="bom-node-toggle" data-toggle aria-expanded="true" aria-label="Expandir o contraer {{ $n['product']->name }}" title="Expandir / contraer">▾</button>
                @else
                    <span class="bom-node-leaf" aria-hidden="true">·</span>
                @endif
                <span class="bom-node-name">{{ $n['product']->name }}</span>
                <span class="bom-muted">{{ $n['product']->sku }}</span>
                @if ($n['is_assembly'])<x-erp.chip tone="neu" label="Subensamble" />@endif
                @if ($n['cyclic'])<x-erp.chip tone="bad" label="Ciclo" />@endif
                @if ($n['missing_cost'])<x-erp.chip tone="warn" label="Sin costo" title="El componente no tiene costo ni precio de proveedor" />@endif
                <span class="bom-lvl">N{{ $depth + 2 }}</span>
            </span>
            <span class="is-num bom-col-qty">{{ $qtyFmt($n['quantity']) }} <span class="bom-muted">{{ $n['unit'] }}</span></span>
            <span class="is-num bom-col-merma">{{ $n['scrap'] > 0 ? rtrim(rtrim(number_format($n['scrap'], 2), '0'), '.') . '%' : '—' }}</span>
            <span class="is-num bom-col-unit">{{ $n['unit_cost'] > 0 ? '$' . number_format($n['unit_cost'], 2) : '—' }}</span>
            <span class="is-num bom-col-cost bom-node-cost">{{ $n['cost'] > 0 ? '$' . number_format($n['cost'], 2) : '—' }}</span>
        </div>
        @if ($n['children'])
            <ul class="bom-tree-children">
                @include('admin.boms._tree_node', ['nodes' => $n['children'], 'depth' => $depth + 1])
            </ul>
        @endif
    </li>
@endforeach
