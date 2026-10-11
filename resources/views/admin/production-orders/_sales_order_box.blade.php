@php
    $__u = auth()->user();
    $__canView = $__u->isAdmin() || $__u->hasPermission('production-orders', 'view');
    $__canCreate = $__u->isAdmin() || $__u->hasPermission('production-orders', 'create');
@endphp
@if ($__canView && \Illuminate\Support\Facades\Route::has('admin.production-orders.index') && \Illuminate\Support\Facades\Schema::hasTable('production_orders'))
    @php $__prd = \App\Models\ProductionOrder::with('product:id,name')->where('sales_order_id', $order->id)->orderByDesc('id')->get(); @endphp
    @push('styles')
        @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
    @endpush
    @if ($__prd->isNotEmpty() || ($__canCreate && $order->status !== 'cancelado'))
        <div class="prd-box">
            <div class="prd-box-head">
                <strong>Producción</strong>
                @if ($__canCreate && $order->status !== 'cancelado')
                    <a class="prd-box-link" href="{{ route('admin.production-orders.create', array_filter(['sales_order_id' => $order->id, 'project_id' => $order->project_id ?? null])) }}">Crear orden de producción</a>
                @endif
            </div>
            @foreach ($__prd as $p)
                <div class="prd-box-row">
                    <a href="{{ route('admin.production-orders.show', $p) }}" class="prd-link">{{ $p->number }}</a>
                    <span>{{ $p->product->name ?? '' }}</span>
                    <x-erp.chip :tone="\App\Support\ErpUi::tone($p->status)" :label="\App\Models\ProductionOrder::statusLabel($p->status)" />
                    <span class="prd-muted">{{ $p->progress }}%</span>
                </div>
            @endforeach
        </div>
    @endif
@endif
