@foreach ($nodes as $n)
    <li class="bom-node {{ $n['is_assembly'] ? 'is-assembly' : '' }}">
        <div class="bom-node-row">
            @if ($n['is_assembly'])
                <button type="button" class="bom-node-toggle" data-toggle aria-expanded="true" title="Expandir / contraer">▾</button>
            @else
                <span class="bom-node-toggle bom-node-leaf">•</span>
            @endif
            <span class="bom-node-name">
                {{ $n['product']->name }}
                <span class="bom-muted">{{ $n['product']->sku }}</span>
                @if ($n['is_assembly'])<span class="erp-chip erp-tone-neutral">Subensamble</span>@endif
                @if ($n['cyclic'])<span class="erp-chip erp-tone-bad">Ciclo</span>@endif
                @if ($n['missing_cost'])<span class="erp-chip erp-tone-warn" title="El componente no tiene costo ni precio de proveedor">Sin costo</span>@endif
            </span>
            <span class="bom-node-qty">{{ rtrim(rtrim(number_format($n['quantity'], 4, '.', ','), '0'), '.') }} {{ $n['unit'] }}
                @if ($n['scrap'] > 0)<small class="bom-muted">(+{{ rtrim(rtrim(number_format($n['scrap'], 2), '0'), '.') }}% merma)</small>@endif
            </span>
            <span class="bom-node-unit-cost">{{ $n['unit_cost'] > 0 ? '$' . number_format($n['unit_cost'], 2) : '—' }}</span>
            <span class="bom-node-cost">{{ $n['cost'] > 0 ? '$' . number_format($n['cost'], 2) : '—' }}</span>
        </div>
        @if ($n['children'])
            <ul class="bom-tree-children">
                @include('admin.boms._tree_node', ['nodes' => $n['children']])
            </ul>
        @endif
    </li>
@endforeach
