{{--
    Calculadora de Alberca — Leads (Admin). Módulo de solo lectura + una
    única acción de escritura (vincular a Cotización real) sobre los leads
    generados por la calculadora pública de bombas de calor para alberca.

    Contrato de datos (PoolCalculatorLeadController::index()):
      - $leads           LengthAwarePaginator<PoolCalculatorLead> con adVisit/matchedQuote cargados
      - $quotesForMatch  Collection<Quote> aceptadas, las 50 más recientes (para el <select> del modal)
      - $statuses        PoolCalculatorLead::STATUSES
      - $filters         ['ref','status','from','to']
--}}
@extends('admin.layouts.master')

@section('title', 'Calculadora de Alberca - Admin')

@section('content')
<div class="container user-manager">
    <section class="clients-manager-section">

        {{-- Header --}}
        <header class="clients-manager-main" style="margin-bottom:4px;">
            <div>
                <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                    Panel de Control &gt; Calculadora de Alberca
                </p>
                <h1>Calculadora de Alberca — Leads</h1>
                <p class="breadcrumb-clients-manager main">Leads generados por la calculadora pública de dimensionamiento de bomba de calor</p>
            </div>
        </header>

        @if (session('success'))
            <div class="pform-hint" style="background:#ecfdf5;color:#065f46;padding:10px 14px;border-radius:8px;margin:12px 0;">
                {{ session('success') }}
            </div>
        @endif

        @php
            $customerName = fn ($customer) => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : null;
            $statusLabels = [
                'nuevo' => 'Nuevo',
                'contactado' => 'Contactado',
                'cotizado' => 'Cotizado',
                'descartado' => 'Descartado',
            ];
            $statusColors = [
                'nuevo' => ['bg' => '#eff6ff', 'fg' => '#1d4ed8'],
                'contactado' => ['bg' => '#fef9c3', 'fg' => '#854d0e'],
                'cotizado' => ['bg' => '#ecfdf5', 'fg' => '#065f46'],
                'descartado' => ['bg' => '#f1f5f9', 'fg' => '#475569'],
            ];
        @endphp

        {{-- Filtros --}}
        <form method="GET" action="{{ route('admin.pool-calculator-leads.index') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin:16px 0;">
            <div>
                <label class="pform-label" for="filterRef">Ref</label>
                <input type="text" id="filterRef" name="ref" value="{{ $filters['ref'] }}" placeholder="EQ-XXXX" class="pform-input">
            </div>
            <div>
                <label class="pform-label" for="filterStatus">Estatus</label>
                <select id="filterStatus" name="status" class="users-manager-select">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>
                            {{ $statusLabels[$status] ?? ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="pform-label" for="filterFrom">Desde</label>
                <input type="date" id="filterFrom" name="from" value="{{ $filters['from'] }}" class="pform-input">
            </div>
            <div>
                <label class="pform-label" for="filterTo">Hasta</label>
                <input type="date" id="filterTo" name="to" value="{{ $filters['to'] }}" class="pform-input">
            </div>
            <div>
                <button type="submit" class="button-primary size-adjustment">Filtrar</button>
                <a href="{{ route('admin.pool-calculator-leads.index') }}" class="button-secondary size-adjustment">Limpiar</a>
            </div>
        </form>

        {{-- Tabla --}}
        <main class="table-container-clients-manager head">
            <table class="clients-manager-table brand-table">
                <thead>
                    <tr>
                        <th>REF</th>
                        <th>CIUDAD / MODELO RECOMENDADO</th>
                        <th>ESTATUS</th>
                        <th>ORIGEN</th>
                        <th>FECHA</th>
                        <th>ACCIÓN</th>
                    </tr>
                </thead>
            </table>
            <div class="table-scroll">
                <table class="clients-manager-table">
                    <tbody>
                    @forelse ($leads as $lead)
                        @php
                            // El payload real lo arma pool-calculator.js como
                            // { datos: {...inputs del form...}, resultado: {...output de dimensionar()...} }
                            // -- 'ciudad' vive en datos (es un input), y
                            // 'modelo' dentro de resultado es un OBJETO
                            // {nombre, btu, precio, slug, url}, no un string
                            // plano -- por eso se extrae ->nombre, nunca se
                            // imprime el array completo.
                            $datos = $lead->payload['datos'] ?? [];
                            $resultado = $lead->payload['resultado'] ?? [];
                            $ciudad = $datos['ciudad'] ?? null;
                            $modeloNombre = is_array($resultado['modelo'] ?? null)
                                ? ($resultado['modelo']['nombre'] ?? null)
                                : ($resultado['modelo'] ?? null);
                            $color = $statusColors[$lead->status] ?? ['bg' => '#f1f5f9', 'fg' => '#475569'];
                        @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:12px 16px;font-family:monospace;font-weight:600;color:#141516;">
                                {{ $lead->ref }}
                            </td>
                            <td style="padding:12px 16px;">
                                @if ($ciudad || $modeloNombre)
                                    <div>{{ $ciudad ?? '—' }}</div>
                                    <div style="font-size:12px;color:#6b7280;">{{ $modeloNombre ?? 'Modelo no especificado' }}</div>
                                @else
                                    <span style="color:#9ca3af;">Sin datos de resultado</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;">
                                <span style="display:inline-block;padding:3px 10px;background:{{ $color['bg'] }};color:{{ $color['fg'] }};border-radius:6px;font-size:12px;font-weight:600;">
                                    {{ $statusLabels[$lead->status] ?? ucfirst($lead->status) }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:#374151;">
                                {{ $lead->adVisit?->identifier_kind ?? 'Sin visita registrada' }}
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:#374151;">
                                {{ $lead->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td style="padding:12px 16px;">
                                @permiso('pool-calculator-leads', 'edit')
                                    @if ($lead->matched_quote_id)
                                        <span style="font-size:12px;color:#065f46;">
                                            Vinculado a {{ $lead->matchedQuote?->quote_number ?? ('#' . $lead->matched_quote_id) }}
                                        </span>
                                    @else
                                        <button type="button" class="table-users-manager-action-btn edit btn-vincular-lead"
                                            data-lead-id="{{ $lead->id }}" data-lead-ref="{{ $lead->ref }}"
                                            data-action-url="{{ route('admin.pool-calculator-leads.mark-matched', $lead) }}">
                                            Vincular a Cotización
                                        </button>
                                    @endif
                                @else
                                    @if ($lead->matched_quote_id)
                                        <span style="font-size:12px;color:#065f46;">
                                            Vinculado a {{ $lead->matchedQuote?->quote_number ?? ('#' . $lead->matched_quote_id) }}
                                        </span>
                                    @else
                                        <span style="font-size:12px;color:#9ca3af;">Sin vincular</span>
                                    @endif
                                @endpermiso
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:24px 16px;text-align:center;color:#6b7280;">
                                No hay leads de la calculadora todavía.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </main>

        <div style="margin-top:16px;">
            {{ $leads->links('admin.components.pagination') }}
        </div>

    </section>
</div>

{{-- Modal: Vincular a Cotización --}}
<div id="vincularLeadModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="vincularLeadModalTitle">Vincular lead a Cotización</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeVincularLeadModal">✕</button>
        </div>

        <form class="user-manager-modal-body" id="vincularLeadForm" method="POST">
            @csrf
            @method('PATCH')

            <p class="pform-hint">Lead <strong id="vincularLeadRef"></strong></p>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Cotización aceptada <span style="color:red">*</span></label>
                <input type="text" id="quoteSearchInput" class="users-manager-input" placeholder="Buscar por número, nombre, correo o teléfono&hellip;" style="margin-bottom:8px;">
                <select class="users-manager-select" name="quote_id" id="quoteSelect" size="8" required>
                    @foreach ($quotesForMatch as $quote)
                        @php
                            $qCustomerName = $customerName($quote->customer);
                        @endphp
                        <option value="{{ $quote->id }}"
                            data-search="{{ strtolower($quote->quote_number . ' ' . $qCustomerName . ' ' . $quote->guest_name . ' ' . $quote->customer?->email . ' ' . $quote->guest_email . ' ' . $quote->customer?->phone . ' ' . $quote->guest_phone) }}">
                            {{ $quote->quote_number }} — {{ $qCustomerName ?: ($quote->guest_name ?? 'Sin nombre') }}
                            ({{ $quote->customer?->email ?? $quote->guest_email ?? 'sin correo' }})
                        </option>
                    @endforeach
                </select>
                <p class="pform-hint">Solo se listan las 50 cotizaciones aceptadas más recientes. Usa el buscador para filtrar por texto.</p>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelVincularLeadModal" class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Vincular</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('vincularLeadModal');
    var form = document.getElementById('vincularLeadForm');
    var refLabel = document.getElementById('vincularLeadRef');
    var quoteSelect = document.getElementById('quoteSelect');
    var quoteSearch = document.getElementById('quoteSearchInput');

    function openModal(url, ref) {
        form.action = url;
        refLabel.textContent = ref;
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    document.querySelectorAll('.btn-vincular-lead').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.dataset.actionUrl, btn.dataset.leadRef);
        });
    });

    var closeBtn = document.getElementById('closeVincularLeadModal');
    var cancelBtn = document.getElementById('cancelVincularLeadModal');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    if (quoteSearch) {
        quoteSearch.addEventListener('input', function () {
            var term = quoteSearch.value.trim().toLowerCase();
            Array.prototype.forEach.call(quoteSelect.options, function (opt) {
                var matches = !term || (opt.dataset.search || '').indexOf(term) !== -1;
                opt.style.display = matches ? '' : 'none';
            });
        });
    }
})();
</script>
@endpush
@endsection
