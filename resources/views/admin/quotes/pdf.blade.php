<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $quote->quote_number }}</title>
<style>

@page {
    margin: 130px 0px 70px 0px;
}

* { box-sizing: border-box; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a2e;
    background: #fff;
    margin: 0;
    padding: 0;
}

/* ════════════════════════════════════════════════════
   HEADER FIJO — abarca todo el ancho del papel
   ════════════════════════════════════════════════════ */
#pdf-header {
    position: fixed;
    top: -130px;
    left: 0;
    right: 0;
    background: #1a1a1a;
}
.header-body {
    padding: 20px 44px 14px 44px;
    display: table;
    width: 100%;
}
.header-left  { display: table-cell; vertical-align: top; }
.header-right { display: table-cell; vertical-align: top; text-align: right; width: 46%; }

.company-meta p { font-size: 9.5px; color: #9CA3AF; line-height: 1.7; margin: 0; margin-top: 5px; }

.cot-label  {
    font-size: 9px; font-weight: bold; color: #ff6213;
    letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px;
}
.cot-number { font-size: 22px; font-weight: bold; color: #ffffff; line-height: 1; }
.cot-dates  { margin-top: 6px; }
.cot-dates p { font-size: 9.5px; color: #9CA3AF; line-height: 1.8; display: inline; margin: 0; }
.cot-dates span { color: #D1D5DC; font-weight: bold; }

.header-accent { height: 3px; background: #ff6213; margin: 0; }


/* ════════════════════════════════════════════════════
   FOOTER FIJO — abarca todo el ancho del papel
   ════════════════════════════════════════════════════ */
#pdf-footer {
    position: fixed;
    bottom: -70px;
    left: 0;
    right: 0;
    height: 50px;
    background: #1a1a1a;
    border-top: 2px solid #ff6213;
}
.footer-inner  { display: table; width: 100%; height: 50px; padding: 0 44px; }
.footer-brand  { display: table-cell; width: 30%; vertical-align: middle; }
.footer-center {
    display: table-cell; width: 40%; vertical-align: middle;
    text-align: center; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-right  {
    display: table-cell; width: 30%; vertical-align: middle;
    text-align: right; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-orange { color: #ff6213; }


/* ════════════════════════════════════════════════════
   WRAPPER DE CONTENIDO — padding lateral simula márgenes
   ════════════════════════════════════════════════════ */
#content {
    padding: 0 44px;
}


/* ════════════════════════════════════════════════════
   BLOQUE RECEPTOR
   ════════════════════════════════════════════════════ */
.receptor {
    background: #f8f8f8;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 14px 16px;
    margin-top: 22px;
    margin-bottom: 14px;
}
.receptor-title {
    font-size: 9px; font-weight: bold; color: #6b6b6b;
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;
}
.receptor-cols { display: table; width: 100%; }
.receptor-col  { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
.receptor-col:last-child { padding-right: 0; }
.r-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.r-value { font-size: 11px; color: #1a1a2e; font-weight: bold; margin-bottom: 8px; }


/* ════════════════════════════════════════════════════
   TIRA DE CONDICIONES
   ════════════════════════════════════════════════════ */
.conditions {
    display: table;
    width: 100%;
    margin-bottom: 16px;
}
.cond-item { display: table-cell; vertical-align: top; padding-right: 12px; }
.cond-item:last-child { padding-right: 0; }
.cond-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
.cond-value { font-size: 11px; color: #333; font-weight: 600; }


/* ════════════════════════════════════════════════════
   TABLA DE PRODUCTOS
   ════════════════════════════════════════════════════ */
.items-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 16px;
}
thead { display: table-header-group; }
.items-table thead tr { background: #141516; }
.items-table thead th {
    padding: 9px 8px;
    text-align: left;
    font-size: 9px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: bold;
}
.items-table thead th.th-right { text-align: right; }

.items-table tbody tr { page-break-inside: avoid; }
.items-table tbody tr:nth-child(even) { background: #f5f5f5; }
.items-table tbody td {
    padding: 9px 8px;
    font-size: 11px;
    color: #444;
    border-bottom: 1px solid #eee;
    vertical-align: top;
    word-wrap: break-word;
}
.items-table tbody td.td-right  { text-align: right; }
.items-table tbody td.td-name   { font-weight: bold; color: #1a1a2e; }
.items-table tbody td.td-sku    { font-size: 9.5px; color: #999; }
.item-desc-text {
    font-size: 9px; font-weight: normal; color: #555;
    margin-top: 4px; line-height: 1.55;
}


/* ════════════════════════════════════════════════════
   TOTALES
   ════════════════════════════════════════════════════ */
.totals-wrap   { display: table; width: 100%; margin-bottom: 22px; }
.totals-spacer { display: table-cell; width: 50%; }
.totals-box    { display: table-cell; width: 50%; vertical-align: top; }

.totals-row    { display: table; width: 100%; padding: 5px 0; border-bottom: 1px solid #eee; }
.totals-row:last-child { border-bottom: none; }
.totals-lbl    { display: table-cell; width: 45%; font-size: 11px; color: #666; }
.totals-val    {
    display: table-cell; width: 55%; text-align: right;
    font-size: 11px; font-weight: bold; color: #333;
    white-space: nowrap;
}

.totals-final  {
    background: #ff6213; border-radius: 5px; padding: 10px 14px;
    display: table; width: 100%; margin-top: 8px;
}
.totals-final-lbl { display: table-cell; font-size: 13px; font-weight: bold; color: #fff; }
.totals-final-val {
    display: table-cell; text-align: right;
    font-size: 13px; font-weight: bold; color: #fff;
    white-space: nowrap;
}


/* ════════════════════════════════════════════════════
   NOTAS Y TÉRMINOS
   ════════════════════════════════════════════════════ */
.notes-section {
    display: table; width: 100%;
    border-top: 1px solid #eee; padding-top: 16px;
    margin-bottom: 16px;
}
.notes-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
.notes-col:last-child { padding-right: 0; }
.notes-label {
    font-size: 9px; font-weight: bold; color: #999;
    text-transform: uppercase; letter-spacing: .8px; margin-bottom: 6px;
}
.notes-text { font-size: 10px; color: #666; line-height: 1.6; word-wrap: break-word; }

</style>
</head>
<body>

{{-- ── Header fijo ──────────────────────────────────────────── --}}
<div id="pdf-header">
    <div class="header-body">
        <div class="header-left">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
            <div class="company-meta">
                <p>administracion@equitermindustries.com.mx</p>
                <p>México, Aguascalientes</p>
            </div>
        </div>
        <div class="header-right">
            <div class="cot-label">Cotización</div>
            <div class="cot-number">{{ $quote->quote_number }}</div>
            <div class="cot-dates">
                <p>Emisión: <span>{{ $quote->created_at->format('d/m/Y') }}</span></p>
                @if($quote->valid_until)
                <p>Válida hasta: <span>{{ $quote->valid_until->format('d/m/Y') }}</span></p>
                @endif
                <p>Moneda: <span>{{ $quote->currency }}</span></p>
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- ── Footer fijo ──────────────────────────────────────────── --}}
<div id="pdf-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:22px;width:auto;">
        </div>
        <div class="footer-center">
            Cotización generada el {{ now()->format('d/m/Y H:i') }}<br>
            Este documento es una cotización y no constituye una factura.
        </div>
        <div class="footer-right">
            administracion@equitermindustries.com.mx<br>
            <span class="footer-orange">equitermindustries.com.mx</span>
        </div>
    </div>
</div>

{{-- ── Contenido principal ──────────────────────────────────── --}}
<div id="content">

    {{-- Receptor --}}
    <div class="receptor">
        <div class="receptor-title">Cotización para</div>
        <div class="receptor-cols">
            <div class="receptor-col">
                <div class="r-label">Nombre</div>
                <div class="r-value">{{ $quote->guest_name }}</div>
                @if($quote->guest_company)
                <div class="r-label">Empresa</div>
                <div class="r-value">{{ $quote->guest_company }}</div>
                @endif
            </div>
            <div class="receptor-col">
                @if($quote->guest_email)
                <div class="r-label">Correo</div>
                <div class="r-value">{{ $quote->guest_email }}</div>
                @endif
                @if($quote->guest_phone)
                <div class="r-label">Teléfono</div>
                <div class="r-value">{{ $quote->guest_phone }}</div>
                @endif
                @if($quote->guest_rfc)
                <div class="r-label">RFC</div>
                <div class="r-value">{{ $quote->guest_rfc }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Condiciones --}}
    <div class="conditions">
        <div class="cond-item">
            <div class="cond-label">IVA</div>
            <div class="cond-value">{{ $quote->tax_rate }}%</div>
        </div>
        <div class="cond-item">
            <div class="cond-label">Moneda</div>
            <div class="cond-value">{{ $quote->currency }}</div>
        </div>
        @if($quote->valid_until)
        <div class="cond-item">
            <div class="cond-label">Válido hasta</div>
            <div class="cond-value">{{ $quote->valid_until->format('d/m/Y') }}</div>
        </div>
        @endif
    </div>

    {{-- Tabla de productos --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:34%;">Descripción</th>
                <th style="width:13%;">SKU</th>
                <th class="th-right" style="width:6%;white-space:nowrap;">Cant.</th>
                <th class="th-right" style="width:17%;">Precio Unit.</th>
                <th class="th-right" style="width:8%;white-space:nowrap;">Desc.%</th>
                <th class="th-right" style="width:18%;white-space:nowrap;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $i => $item)
            <tr>
                <td style="text-align:center;color:#aaa;">{{ $i + 1 }}</td>
                <td class="td-name">
                    {{ $item->product_name }}
                    @if($item->notes)
                    <div class="item-desc-text">{!! nl2br(e($item->notes)) !!}</div>
                    @endif
                </td>
                <td class="td-sku">{{ $item->product_sku ?? '—' }}</td>
                <td class="td-right" style="white-space:nowrap;">{{ $item->quantity }}</td>
                <td class="td-right">{{ $quote->currency }} ${{ number_format($item->unit_price, 2) }}</td>
                <td class="td-right" style="white-space:nowrap;">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                <td class="td-right" style="font-weight:bold;white-space:nowrap;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <div class="totals-wrap">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <div class="totals-row">
                <div class="totals-lbl">Subtotal</div>
                <div class="totals-val">{{ $quote->currency }} ${{ number_format($quote->subtotal, 2) }}</div>
            </div>
            @if($quote->discount_total > 0)
            <div class="totals-row">
                <div class="totals-lbl">Descuento</div>
                <div class="totals-val">− {{ $quote->currency }} ${{ number_format($quote->discount_total, 2) }}</div>
            </div>
            @endif
            <div class="totals-row">
                <div class="totals-lbl">IVA ({{ $quote->tax_rate }}%)</div>
                <div class="totals-val">{{ $quote->currency }} ${{ number_format($quote->tax_total, 2) }}</div>
            </div>
            <div class="totals-final">
                <div class="totals-final-lbl">Total</div>
                <div class="totals-final-val">{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Notas y términos --}}
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

</div>{{-- /#content --}}
</body>
</html>
