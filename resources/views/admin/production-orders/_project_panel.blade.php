@php
    $__u = auth()->user();
    $__canView = $__u->isAdmin() || $__u->hasPermission('production-orders', 'view');
    $__canCreate = $__u->isAdmin() || $__u->hasPermission('production-orders', 'create');
@endphp
@if (\Illuminate\Support\Facades\Schema::hasTable('production_orders'))
    @php
        $__prd = \App\Models\ProductionOrder::with('product:id,name')->where('project_id', $project->id)->where('status', '!=', 'cancelada')->orderBy('id')->get();
        $__planned = (float) $__prd->sum('quantity_planned');
        $__prodPct = $__planned > 0 ? (int) round($__prd->sum(fn ($o) => $o->progress * $o->quantity_planned) / $__planned) : null;
        $__svc = \App\Models\TechnicalService::where('project_id', $project->id)->where('status', '!=', 'cancelled')->get(['id', 'status']);
        $__svcPct = $__svc->count() ? (int) round($__svc->where('status', 'completed')->count() / $__svc->count() * 100) : null;
        $__late = $__prd->filter(fn ($o) => $o->isOpen() && $o->daysToDue() !== null && $o->daysToDue() < 0)->count();
    @endphp
    @push('styles')
        @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
    @endpush
    <div class="prj-panel">
        <div class="prj-panel-head">
            <h2>Producción y servicios</h2>
            @if ($__canCreate && \Illuminate\Support\Facades\Route::has('admin.production-orders.create'))
                <a class="prd-box-link" href="{{ route('admin.production-orders.create', ['project_id' => $project->id]) }}">Crear orden de producción</a>
            @endif
        </div>
        <div class="prd-prj-bars">
            <div>
                <div class="prd-prj-bar-label"><span>Producción ({{ $__prd->count() }} orden(es){{ $__late ? ' · ' . $__late . ' vencida(s)' : '' }})</span><strong>{{ $__prodPct !== null ? $__prodPct . '%' : '—' }}</strong></div>
                <x-erp.progress :value="$__prodPct ?? 0" />
            </div>
            <div>
                <div class="prd-prj-bar-label"><span>Servicios ({{ $__svc->where('status', 'completed')->count() }} de {{ $__svc->count() }} completados)</span><strong>{{ $__svcPct !== null ? $__svcPct . '%' : '—' }}</strong></div>
                <x-erp.progress :value="$__svcPct ?? 0" />
            </div>
        </div>
        @if ($__canView && $__prd->isNotEmpty())
            <div class="prd-box-list">
                @foreach ($__prd as $p)
                    <div class="prd-box-row">
                        <a href="{{ route('admin.production-orders.show', $p) }}" class="prd-link">{{ $p->number }}</a>
                        <span>{{ $p->product->name ?? '' }}</span>
                        <x-erp.chip :tone="\App\Support\ErpUi::tone($p->status)" :label="\App\Models\ProductionOrder::statusLabel($p->status)" />
                        <span class="prd-muted">{{ $p->progress }}%@if ($p->due_date) · {{ $p->due_date->format('d/m/Y') }}@endif</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
