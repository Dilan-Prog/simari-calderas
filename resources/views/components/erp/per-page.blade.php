@props(['default' => \App\Support\PerPage::DEFAULT, 'inForm' => false, 'label' => 'Mostrar', 'suffix' => 'por página'])
{{--
    Selector de tamaño de página: «Mostrar [10 ▾] por página»  (?per_page=10|20|30|50|100|200|500|all).
      <x-erp.per-page />                 → <form method="GET"> propio que conserva todos los parámetros actuales
                                           (filtros, vista, orden…) salvo «page» (vuelve a la página 1).
      <x-erp.per-page :in-form="true" /> → sólo el <select name="per_page">, para colocarlo DENTRO del formulario
                                           de filtros; al cambiar envía ese formulario.
      default : tamaño por omisión del controlador (debe coincidir con PerPage::paginate(..., $default)).
    Backend: App\Support\PerPage::paginate($query, $request).
--}}
@php
    $ppParam = \App\Support\PerPage::PARAM;
    $ppCurrent = \App\Support\PerPage::current(request(), (int) $default);
    $ppId = 'erp-pp-' . substr(md5(uniqid('', true)), 0, 6);
    $ppHidden = $inForm ? [] : \App\Support\PerPage::hiddenFields(\Illuminate\Support\Arr::except(request()->query(), ['page', $ppParam]));
@endphp
@once
    @push('styles')
        <style>
            /* Pie de listado: «Mostrando X–Y de N» + selector, juntos y con salto de línea si no caben. */
            .erp-wl-meta { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; }
            @media (max-width: 768px) { .erp-wl-meta { justify-content: center; } }
            .erp-perpage { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 12px; color: var(--erp-muted, #6b7280); white-space: nowrap; }
            .erp-perpage label { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-weight: 500; cursor: pointer; }
            .erp-perpage select { border: 1px solid #d4d7dc; border-radius: 8px; padding: 4px 26px 4px 10px; min-height: 32px; font-size: 13px; font-weight: 600; font-family: inherit; color: var(--erp-text, #374151); background: #fff; cursor: pointer; outline: none; }
            .erp-perpage select:focus-visible { border-color: var(--erp-accent, #f97316); box-shadow: 0 0 0 3px rgba(249, 115, 22, .15); }
            @media (max-width: 768px) {
                .erp-perpage { font-size: 14px; }
                .erp-perpage select { min-height: 44px; font-size: 16px; padding: 8px 30px 8px 12px; }
            }
            @media print { .erp-perpage { display: none !important; } }
        </style>
    @endpush
@endonce
@if ($inForm)
    <span {{ $attributes->class(['erp-perpage']) }}>
        <label for="{{ $ppId }}">{{ $label }}
            <select id="{{ $ppId }}" name="{{ $ppParam }}" onchange="this.form && (this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit())">
                @foreach (\App\Support\PerPage::OPTIONS as $val => $text)
                    <option value="{{ $val }}" @selected((string) $val === $ppCurrent)>{{ $text }}</option>
                @endforeach
            </select>
            {{ $suffix }}</label>
    </span>
@else
    <form method="GET" action="{{ url()->current() }}" {{ $attributes->class(['erp-perpage']) }} data-erp-perpage>
        @foreach ($ppHidden as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <label for="{{ $ppId }}">{{ $label }}
            <select id="{{ $ppId }}" name="{{ $ppParam }}" onchange="this.form.submit()">
                @foreach (\App\Support\PerPage::OPTIONS as $val => $text)
                    <option value="{{ $val }}" @selected((string) $val === $ppCurrent)>{{ $text }}</option>
                @endforeach
            </select>
            {{ $suffix }}</label>
        <noscript><button type="submit">Aplicar</button></noscript>
    </form>
@endif
