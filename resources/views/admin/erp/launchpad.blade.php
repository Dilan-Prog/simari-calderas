@extends('admin.layouts.master')
@section('title')
    Operaciones - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
@section('content')
    <div class="erp-page">
        <div class="erp-page-head">
            <div>
                <h1 class="erp-title">Operaciones</h1>
                <p class="erp-subtitle">Tus módulos del ERP. Presiona <kbd>Ctrl</kbd> + <kbd>K</kbd> para saltar a cualquier módulo o documento por folio.</p>
            </div>
            <button type="button" class="erp-btn" data-erp-palette-open>Buscar módulo o folio…</button>
        </div>

        @forelse ($areas as $area)
            <section class="erp-area">
                <h2 class="erp-area-title">{{ $area['label'] }}</h2>
                <div class="erp-tiles">
                    @foreach ($area['items'] as $item)
                        @php $kpi = $kpis[$item['permission']] ?? null; @endphp
                        <a class="erp-tile" href="{{ $item['url'] }}">
                            <span class="erp-tile-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                            </span>
                            <span class="erp-tile-label">{{ $item['label'] }}</span>
                            @if ($kpi)
                                <span class="erp-tile-kpi erp-tone-{{ $kpi['tone'] }}">
                                    <strong>{{ $kpi['value'] }}</strong> {{ $kpi['label'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="erp-empty">
                <p>Aún no hay módulos de Operaciones disponibles para tu rol.</p>
            </div>
        @endforelse
    </div>
@endsection
