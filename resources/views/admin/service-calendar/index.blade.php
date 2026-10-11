@extends('admin.layouts.master')

@section('title', 'Calendario de servicios - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/erp-production.js'])
@endpush

@section('content')
<div class="erp-page prd-page cal-page"
     data-service-calendar
     data-events-url="{{ route('admin.service-calendar.events') }}"
     data-reschedule-url="{{ route('admin.service-calendar.reschedule', ['service' => '__ID__']) }}"
     data-can-edit="{{ $canEdit ? 1 : 0 }}"
     data-csrf="{{ csrf_token() }}">
    <div class="erp-page-head">
        <div>
            <h1 class="erp-title">Calendario de servicios</h1>
            <p class="erp-subtitle">Servicios técnicos programados. @if ($canEdit)Arrastra un servicio a otro día (o a otra hora en la vista Día) para reprogramarlo.@endif</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.technical-services.create') }}" class="prd-btn prd-btn-primary">+ Nuevo servicio</a>
        @endif
    </div>

    <div class="prd-card prd-pad cal-toolbar">
        <div class="cal-nav">
            <button type="button" class="prd-btn" data-cal-prev aria-label="Anterior">‹</button>
            <button type="button" class="prd-btn" data-cal-today>Hoy</button>
            <button type="button" class="prd-btn" data-cal-next aria-label="Siguiente">›</button>
            <strong class="cal-title" data-cal-title></strong>
        </div>
        <div class="cal-views" role="tablist">
            <button type="button" class="cal-view is-active" data-cal-view="month">Mes</button>
            <button type="button" class="cal-view" data-cal-view="week">Semana</button>
            <button type="button" class="cal-view" data-cal-view="day">Día</button>
        </div>
    </div>

    <div class="prd-filters cal-filters">
        <select class="prd-input" data-cal-filter="technician_id">
            <option value="">Todos los técnicos</option>
            @foreach ($technicians as $t)
                <option value="{{ $t->id }}" @selected($filters['technician_id'] === (string) $t->id)>{{ trim($t->first_name . ' ' . $t->last_name) }}</option>
            @endforeach
        </select>
        <select class="prd-input" data-cal-filter="project_id">
            <option value="">Todos los proyectos</option>
            @foreach ($projects as $p)
                <option value="{{ $p->id }}" @selected($filters['project_id'] === (string) $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 28) }}</option>
            @endforeach
        </select>
        <select class="prd-input" data-cal-filter="service_type_id">
            <option value="">Todos los tipos</option>
            @foreach ($types as $t)
                <option value="{{ $t->id }}" @selected($filters['service_type_id'] === (string) $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <select class="prd-input" data-cal-filter="status">
            <option value="">Todos los estados</option>
            @foreach ($statuses as $k => $l)
                <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </div>

    <div class="cal-legend">
        @foreach ($statuses as $k => $l)
            <span><i class="cal-swatch cal-st-{{ $k }}"></i>{{ $l }}</span>
        @endforeach
        <span><i class="cal-swatch cal-pr-urgent"></i>Urgente</span>
        <span><i class="cal-swatch cal-pr-high"></i>Alta</span>
    </div>

    <div class="cal-toast" data-cal-toast hidden></div>
    <div class="prd-card cal-body" data-cal-body>
        <div class="cal-loading">Cargando…</div>
    </div>
</div>
@endsection
