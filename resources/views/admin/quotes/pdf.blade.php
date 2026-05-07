<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $quote->quote_number }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a2e;
    background: #fff;
    padding: 0;
}
.page { padding: 36px 44px; }

/* Header (dark) */
.doc-header { background: #1a1a1a; }
.header { display: table; width: 100%; padding: 26px 44px; }
.header-left { display: table-cell; vertical-align: top; width: 50%; }
.header-right { display: table-cell; vertical-align: top; text-align: right; }
.company-meta { margin-top: 8px; }
.company-meta p { font-size: 10px; color: #9CA3AF; line-height: 1.6; }
.cotizacion-label {
    font-size: 9px; font-weight: bold; color: #ff6213;
    letter-spacing: 2px; text-transform: uppercase; margin-bottom: 5px;
}
.cotizacion-number { font-size: 22px; font-weight: bold; color: #ffffff; line-height: 1; }
.cotizacion-dates { margin-top: 6px; }
.cotizacion-dates p { font-size: 10px; color: #9CA3AF; line-height: 1.7; }
.cotizacion-dates span { color: #D1D5DC; font-weight: bold; }
.header-divider { height: 3px; background: #ff6213; width: 60px; margin: 0 44px 28px; }

/* Receptor */
.receptor-block { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 14px 18px; margin-bottom: 24px; }
.receptor-title { font-size: 9px; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
.receptor-grid { display: table; width: 100%; }
.receptor-col { display: table-cell; width: 50%; vertical-align: top; }
.receptor-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.receptor-value { font-size: 11px; color: #1a1a2e; font-weight: bold; }
.receptor-item { margin-bottom: 10px; }

/* Products table */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.items-table thead tr { background: #1a1a2e; }
.items-table thead th {
    padding: 9px 12px;
    text-align: left;
    font-size: 9px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: bold;
}
.items-table thead th.text-right { text-align: right; }
.items-table tbody tr:nth-child(even) { background: #f8f8f8; }
.items-table tbody td { padding: 9px 12px; font-size: 11px; color: #333; border-bottom: 1px solid #eee; vertical-align: middle; }
.items-table tbody td.text-right { text-align: right; }
.items-table tbody td.td-name { font-weight: bold; color: #1a1a2e; }
.items-table tbody td.td-sku { font-size: 9px; color: #999; }

/* Totals */
.totals-wrap { display: table; width: 100%; margin-bottom: 28px; }
.totals-spacer { display: table-cell; width: 55%; }
.totals-box { display: table-cell; width: 45%; vertical-align: top; }
.totals-row { display: table; width: 100%; padding: 5px 0; border-bottom: 1px solid #eee; }
.totals-row-label { display: table-cell; font-size: 11px; color: #666; }
.totals-row-value { display: table-cell; text-align: right; font-size: 11px; font-weight: bold; color: #333; }
.totals-row.final { border-bottom: none; margin-top: 4px; padding-top: 10px; }
.totals-row.final .totals-row-label { font-size: 14px; font-weight: bold; color: #1a1a2e; }
.totals-row.final .totals-row-value { font-size: 14px; font-weight: bold; color: #e8612c; }

/* Notes */
.notes-section { display: table; width: 100%; margin-bottom: 20px; border-top: 1px solid #eee; padding-top: 16px; }
.notes-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
.notes-col:last-child { padding-right: 0; }
.notes-label { font-size: 9px; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 6px; }
.notes-text { font-size: 10px; color: #666; line-height: 1.6; }

/* Footer */
.footer {
    background: #1a1a1a;
    padding: 16px 44px;
    display: table;
    width: 100%;
    margin-top: 32px;
    border-top: 2px solid #2E2E2E;
}
.footer-brand {
    display: table-cell;
    width: 33%;
    vertical-align: middle;
}
.footer-center {
    display: table-cell;
    width: 34%;
    vertical-align: middle;
    text-align: center;
    font-size: 9px;
    color: #6B7280;
    line-height: 1.7;
}
.footer-right {
    display: table-cell;
    width: 33%;
    vertical-align: middle;
    text-align: right;
    font-size: 9px;
    color: #6B7280;
    line-height: 1.7;
}
.footer-right a { color: #ff6213; text-decoration: none; }
.accent-bottom {
    height: 3px;
    background: #ff6213;
}
</style>
</head>
<body>

    <!-- Dark header (full-width, outside page padding) -->
    <div class="doc-header">
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                     alt="Equiterm Industries" style="height:28px;width:auto;">
                <div class="company-meta">
                    <p>administracion@equitermindustries.com.mx</p>
                    <p>México, Aguascalientes</p>
                </div>
            </div>
            <div class="header-right">
                <div class="cotizacion-label">Cotización</div>
                <div class="cotizacion-number">{{ $quote->quote_number }}</div>
                <div class="cotizacion-dates">
                    <p>Emisión: <span>{{ $quote->created_at->format('d/m/Y') }}</span></p>
                    @if($quote->valid_until)
                    <p>Válida hasta: <span>{{ $quote->valid_until->format('d/m/Y') }}</span></p>
                    @endif
                </div>
            </div>
        </div>
    </div>

<div class="page">

    <!-- Receptor -->
    <div class="receptor-block">
        <div class="receptor-title">Cotización para</div>
        <div class="receptor-grid">
            <div class="receptor-col">
                <div class="receptor-item">
                    <div class="receptor-label">Nombre</div>
                    <div class="receptor-value">{{ $quote->guest_name }}</div>
                </div>
                @if($quote->guest_company)
                <div class="receptor-item">
                    <div class="receptor-label">Empresa</div>
                    <div class="receptor-value">{{ $quote->guest_company }}</div>
                </div>
                @endif
            </div>
            <div class="receptor-col">
                @if($quote->guest_email)
                <div class="receptor-item">
                    <div class="receptor-label">Correo</div>
                    <div class="receptor-value">{{ $quote->guest_email }}</div>
                </div>
                @endif
                @if($quote->guest_phone)
                <div class="receptor-item">
                    <div class="receptor-label">Teléfono</div>
                    <div class="receptor-value">{{ $quote->guest_phone }}</div>
                </div>
                @endif
                @if($quote->guest_rfc)
                <div class="receptor-item">
                    <div class="receptor-label">RFC</div>
                    <div class="receptor-value">{{ $quote->guest_rfc }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Products table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Descripción</th>
                <th style="width:90px;">SKU</th>
                <th class="text-right" style="width:50px;">Cant.</th>
                <th class="text-right" style="width:100px;">Precio Unit.</th>
                <th class="text-right" style="width:60px;">Desc. %</th>
                <th class="text-right" style="width:100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $i => $item)
            <tr>
                <td style="text-align:center;color:#aaa;">{{ $i + 1 }}</td>
                <td class="td-name">{{ $item->product_name }}</td>
                <td class="td-sku">{{ $item->product_sku ?? '—' }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                <td class="text-right" style="font-weight:bold;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <div class="totals-row">
                <div class="totals-row-label">Subtotal</div>
                <div class="totals-row-value">{{ $quote->currency }} ${{ number_format($quote->subtotal, 2) }}</div>
            </div>
            @if($quote->discount_total > 0)
            <div class="totals-row">
                <div class="totals-row-label">Descuento</div>
                <div class="totals-row-value">- {{ $quote->currency }} ${{ number_format($quote->discount_total, 2) }}</div>
            </div>
            @endif
            <div class="totals-row">
                <div class="totals-row-label">IVA ({{ $quote->tax_rate }}%)</div>
                <div class="totals-row-value">{{ $quote->currency }} ${{ number_format($quote->tax_total, 2) }}</div>
            </div>
            <div class="totals-row final">
                <div class="totals-row-label">Total</div>
                <div class="totals-row-value">{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Notes & Terms -->
    @if($quote->notes || $quote->terms_conditions)
    <div class="notes-section">
        @if($quote->notes)
        <div class="notes-col">
            <div class="notes-label">Notas</div>
            <div class="notes-text">{{ $quote->notes }}</div>
        </div>
        @endif
        @if($quote->terms_conditions)
        <div class="notes-col">
            <div class="notes-label">Términos y Condiciones</div>
            <div class="notes-text">{{ $quote->terms_conditions }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="footer-brand">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:28px;width:auto;">
        </div>
        <div class="footer-center">
            Cotización generada el {{ now()->format('d/m/Y H:i') }}<br>
            Este documento es una cotización y no constituye una factura.
        </div>
        <div class="footer-right">
            administracion@equitermindustries.com.mx<br>
            equitermindustries.com.mx
        </div>
    </div>
    <div class="accent-bottom"></div>

</div>
</body>
</html>
