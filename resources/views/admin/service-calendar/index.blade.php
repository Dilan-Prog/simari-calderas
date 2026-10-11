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
    <x-erp.page-header
        title="Calendario de servicios"
        code="PRD1"
        :desc="'Programación por técnico y por proyecto.' . ($canEdit ? ' Arrastra un servicio a otro día (o a otra hora en la vista Día) para reprogramarlo.' : '')"
        :crumbs="[['Producción', null], ['Calendario de servicios', null]]">
        @if ($canCreate)
            <a href="{{ route('admin.technical-services.create') }}" class="erp-btn erp-btn--primary">+ Nuevo servicio</a>
        @endif
    </x-erp.page-header>

    <x-erp.calendar>
        <x-slot:filters>
            <select class="erp-select" data-cal-filter="technician_id" aria-label="Técnico">
                <option value="">Todos los técnicos</option>
                @foreach ($technicians as $t)
                    <option value="{{ $t->id }}" @selected($filters['technician_id'] === (string) $t->id)>{{ trim($t->first_name . ' ' . $t->last_name) }}</option>
                @endforeach
            </select>
            <select class="erp-select" data-cal-filter="project_id" aria-label="Proyecto">
                <option value="">Todos los proyectos</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($filters['project_id'] === (string) $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 28) }}</option>
                @endforeach
            </select>
            <select class="erp-select" data-cal-filter="service_type_id" aria-label="Tipo de servicio">
                <option value="">Todos los tipos</option>
                @foreach ($types as $t)
                    <option value="{{ $t->id }}" @selected($filters['service_type_id'] === (string) $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <select class="erp-select" data-cal-filter="status" aria-label="Estado">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $k => $l)
                    <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>
                @endforeach
            </select>
        </x-slot:filters>

        <x-slot:legend>
            @foreach ($statuses as $k => $l)
                <span><i class="cal-swatch cal-st-{{ $k }}"></i>{{ $l }}</span>
            @endforeach
            <span><i class="cal-swatch cal-pr-urgent"></i>Urgente</span>
            <span><i class="cal-swatch cal-pr-high"></i>Alta</span>
        </x-slot:legend>
    </x-erp.calendar>
</div>
@endsection
