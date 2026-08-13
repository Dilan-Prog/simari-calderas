@extends('admin.layouts.master')

@section('title', 'Carritos Abandonados - Admin')

@section('content')
<div class="abandoned-carts-page">
    <div>
        <p class="breadcrumb-clients-manager">Panel de Control &gt; <strong>Carritos Abandonados</strong></p>
        <h1 style="margin:0 0 4px;">Carritos Abandonados</h1>
        <p class="breadcrumb-clients-manager main">Carritos y checkouts sin actividad reciente, listos para recuperación manual o automatizada</p>
    </div>

    <div class="abandoned-carts-kpis">
        <div class="abandoned-carts-kpi">
            <span class="abandoned-carts-kpi__label">Carritos abandonados</span>
            <span class="abandoned-carts-kpi__value">{{ $kpis['carrito_count'] }}</span>
        </div>
        <div class="abandoned-carts-kpi">
            <span class="abandoned-carts-kpi__label">Checkouts abandonados</span>
            <span class="abandoned-carts-kpi__value">{{ $kpis['checkout_count'] }}</span>
        </div>
        <div class="abandoned-carts-kpi">
            <span class="abandoned-carts-kpi__label">Valor potencial en riesgo</span>
            <span class="abandoned-carts-kpi__value">${{ number_format($kpis['potential_value'], 2) }} MXN</span>
        </div>
    </div>

    <div class="abandoned-carts-card">
        <div class="abandoned-carts-tabs">
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['tipo' => 'carrito'])) }}"
               class="abandoned-carts-tab @if($tipo === 'carrito') is-active @endif">
                Carritos abandonados
            </a>
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['tipo' => 'checkout'])) }}"
               class="abandoned-carts-tab @if($tipo === 'checkout') is-active @endif">
                Checkouts abandonados
            </a>
        </div>

        <div class="abandoned-carts-thresholds">
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['horas' => 2])) }}"
               class="abandoned-carts-threshold @if((int) request('horas', 24) === 2) is-active @endif">+2h</a>
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['horas' => 24])) }}"
               class="abandoned-carts-threshold @if((int) request('horas', 24) === 24) is-active @endif">+24h</a>
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['horas' => 72])) }}"
               class="abandoned-carts-threshold @if((int) request('horas', 24) === 72) is-active @endif">+3 días</a>
            <a href="{{ route('admin.abandoned-carts.index', array_merge(request()->query(), ['horas' => 168])) }}"
               class="abandoned-carts-threshold @if((int) request('horas', 24) === 168) is-active @endif">+7 días</a>
        </div>

        <div class="abandoned-carts-table-wrap">
            <table class="abandoned-carts-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Última actividad</th>
                        <th>Cliente / Contacto</th>
                        <th># Artículos</th>
                        <th>Valor</th>
                        @if ($tipo === 'checkout')
                            <th>Checkout iniciado</th>
                        @endif
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carts as $cart)
                        <tr>
                            <td>{{ $cart->last_activity_at->diffForHumans() }}</td>
                            <td>
                                @if ($cart->customer)
                                    {{ trim($cart->customer->first_name . ' ' . $cart->customer->last_name) }}
                                @elseif ($cart->contact_name)
                                    {{ $cart->contact_name }}
                                    <br><small style="color:#6b7280;">{{ $cart->contact_email }} {{ $cart->contact_phone }}</small>
                                @else
                                    Invitado (sin datos)
                                @endif
                            </td>
                            <td>{{ $cart->items->sum('quantity') }}</td>
                            <td>${{ number_format($cart->subtotal(), 2) }} MXN</td>
                            @if ($tipo === 'checkout')
                                <td>{{ $cart->checkout_started_at->diffForHumans() }}</td>
                            @endif
                            <td>
                                <form method="POST" action="{{ route('admin.abandoned-carts.dismiss', $cart) }}">
                                    @csrf
                                    <button type="submit" class="abandoned-carts-dismiss-btn">Descartar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tipo === 'checkout' ? 6 : 5 }}" style="text-align:center; color:#9ca3af; padding:24px 0;">
                                No hay carritos abandonados con estos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($carts->hasPages())
            {{ $carts->links('admin.components.pagination') }}
        @endif
    </div>
</div>
@endsection

@push('styles')
    <style>
        .abandoned-carts-page {
            width: 95%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px 0 48px;
            height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .abandoned-carts-kpis {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
        }

        .abandoned-carts-kpi {
            flex: 1 1 200px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .abandoned-carts-kpi__label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }

        .abandoned-carts-kpi__value {
            font-size: 24px;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .abandoned-carts-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 20px;
            margin-top: 16px;
        }

        .abandoned-carts-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .abandoned-carts-tab {
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
            border-bottom: 2px solid transparent;
        }

        .abandoned-carts-tab.is-active {
            color: var(--secondary-color);
            border-bottom-color: var(--secondary-color);
        }

        .abandoned-carts-thresholds {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .abandoned-carts-threshold {
            padding: 6px 14px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
        }

        .abandoned-carts-threshold.is-active {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }

        .abandoned-carts-threshold:hover:not(.is-active) {
            border-color: var(--button-primary-color-hover);
            color: var(--button-primary-color-hover);
        }

        .abandoned-carts-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 60vh;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
        }

        .abandoned-carts-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .abandoned-carts-table th, .abandoned-carts-table td {
            padding: 10px 12px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .abandoned-carts-table th {
            background: #f9fafb;
            font-weight: 700;
            position: sticky;
            top: 0;
        }

        .abandoned-carts-dismiss-btn {
            padding: 6px 14px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            background: #fff;
            font-size: 12.5px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            white-space: nowrap;
        }

        .abandoned-carts-dismiss-btn:hover {
            border-color: var(--button-primary-color-hover);
            color: var(--button-primary-color-hover);
        }
    </style>
@endpush
