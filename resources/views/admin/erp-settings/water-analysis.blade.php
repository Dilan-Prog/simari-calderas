@extends('admin.layouts.master')
@section('title')
    Parámetros de análisis de agua - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/erp-settings.css')
@endpush

@section('content')
    @php
        $canEdit = auth()->user()->hasPermission('erp-settings', 'edit');
        $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    @endphp

    <div class="container user-manager">
        <section class="clients-manager-section">

            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; <a href="{{ route('admin.erp-settings.index') }}">Configuración ERP</a> &gt; Parámetros de análisis de agua
                    </p>
                    <h1>Parámetros de análisis de agua</h1>
                    <p class="breadcrumb-clients-manager main">Valores mínimo y máximo que ven los clientes en su “Reporte de análisis químico”. Cada resultado se evalúa siempre contra los límites vigentes, también en el historial.</p>
                </div>
            </header>

            <div class="pform-panel-wrap" style="margin-top:20px;">
                <div class="pform-panel">
                    <div class="erp-panel-header">
                        <span class="erp-panel-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.5L4.5 19a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V2" /><path d="M8.5 2h7" /><path d="M7 16h10" /></svg>
                        </span>
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Dureza, pH, alcalinidad, conductividad y STD</h2>
                            <p class="erp-panel-subtitle">Deja vacío un límite para no aplicarlo; si ambos están vacíos, el cliente verá “—” y el resultado quedará “sin rango definido”.</p>
                        </div>
                    </div>

                    <div class="table-scroll">
                        <table class="clients-manager-table">
                            <thead>
                                <tr>
                                    <th>PARÁMETRO</th>
                                    <th style="width:150px;">UNIDAD</th>
                                    <th style="width:130px;">MÍNIMO</th>
                                    <th style="width:130px;">MÁXIMO</th>
                                    <th style="width:110px;">ESTADO</th>
                                    @if ($canEdit)
                                        <th style="width:110px;">ACCIONES</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parameters as $p)
                                    @php
                                        $bag = $errors->getBag('parameter' . $p->id);
                                        $own = old('_parameter') == $p->id;
                                        $form = 'waParam' . $p->id;
                                        $ro = $canEdit ? '' : 'readonly';
                                    @endphp
                                    <tr>
                                        <td>
                                            @if ($canEdit)
                                                <form method="POST" action="{{ route('admin.erp-settings.water-analysis.update', $p->id) }}" id="{{ $form }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="_parameter" value="{{ $p->id }}">
                                                </form>
                                            @endif
                                            <input type="text" class="pform-input" name="name" form="{{ $form }}" maxlength="120" required {{ $ro }}
                                                value="{{ $own ? old('name') : $p->name }}" aria-label="Nombre del parámetro">
                                            <div style="font-size:11px; color:#9ca3af; margin-top:2px;">Clave: {{ $p->code }}</div>
                                            @if ($bag->any())
                                                @foreach ($bag->all() as $message)
                                                    <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" class="pform-input" name="unit" form="{{ $form }}" maxlength="30" {{ $ro }}
                                                value="{{ $own ? old('unit') : $p->unit }}" aria-label="Unidad">
                                        </td>
                                        <td>
                                            <input type="text" inputmode="decimal" class="pform-input" name="min_value" form="{{ $form }}" {{ $ro }}
                                                placeholder="—" value="{{ $own ? old('min_value') : $num($p->min_value) }}" aria-label="Valor mínimo de {{ $p->name }}">
                                        </td>
                                        <td>
                                            <input type="text" inputmode="decimal" class="pform-input" name="max_value" form="{{ $form }}" {{ $ro }}
                                                placeholder="—" value="{{ $own ? old('max_value') : $num($p->max_value) }}" aria-label="Valor máximo de {{ $p->name }}">
                                        </td>
                                        <td>
                                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:#374151;">
                                                <input type="checkbox" name="is_active" value="1" form="{{ $form }}" {{ $canEdit ? '' : 'disabled' }}
                                                    {{ ($own ? old('is_active') : $p->is_active) ? 'checked' : '' }}>
                                                Activo
                                            </label>
                                        </td>
                                        @if ($canEdit)
                                            <td>
                                                <button type="submit" form="{{ $form }}" class="pform-btn outline" style="padding:6px 10px;">Guardar</button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="pform-hint" style="margin-top:12px;">Un parámetro inactivo deja de pedirse a los clientes; los resultados ya capturados se conservan.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
