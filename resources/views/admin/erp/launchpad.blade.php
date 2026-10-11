@extends('admin.layouts.master')
@section('title')
    Operaciones - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
@section('content')
    @php
        $hour = (int) now()->format('G');
        $greet = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
        $first = trim(explode(' ', (string) auth()->user()->first_name)[0] ?? '');
        $today = \Illuminate\Support\Carbon::now()->locale('es')->translatedFormat('l d \d\e F \d\e Y');
    @endphp
    <div class="erp-page">
        <div class="erp-page-head">
            <div class="erp-greeting">
                <small>{{ ucfirst($today) }}</small>
                <h1 class="erp-title">{{ $greet }}{{ $first ? ', ' . $first : '' }}</h1>
                <p class="erp-subtitle">Tus módulos del ERP. Presiona <kbd>Ctrl</kbd> + <kbd>K</kbd> para saltar a un módulo o a un documento por folio.</p>
            </div>
            <button type="button" class="erp-btn" data-erp-palette-open>Ir a módulo o folio…</button>
        </div>

        {{-- Tiras de proceso con documentos abiertos por paso --}}
        <div class="erp-strips">
            @foreach ($strips as $strip)
                <div class="erp-strip">
                    <div class="erp-strip-head"><strong>{{ $strip['label'] }}</strong><span>{{ $strip['caption'] }}</span></div>
                    <x-erp.process-chevrons :steps="$strip['steps']" :counts="true" />
                </div>
            @endforeach
        </div>

        @forelse ($areas as $area)
            <section class="erp-area">
                <h2 class="erp-area-title">{{ $area['label'] }} <small>{{ count($area['items']) }} {{ count($area['items']) === 1 ? 'app' : 'apps' }}</small></h2>
                <div class="erp-tiles">
                    @foreach ($area['items'] as $item)
                        @php $kpi = $kpis[$item['permission']] ?? null; @endphp
                        @if ($kpi)
                            <x-erp.kpi-tile :href="$item['url']" :title="$item['label']" :value="$kpi['value']" :caption="$kpi['label']" :tone="$kpi['tone']" />
                        @else
                            <a class="erp-tile" href="{{ $item['url'] }}">
                                <span class="erp-tile-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                                </span>
                                <span class="erp-tile-label">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @empty
            <x-erp.empty title="Sin módulos disponibles" text="Aún no hay módulos de Operaciones disponibles para tu rol." />
        @endforelse
    </div>
@endsection
