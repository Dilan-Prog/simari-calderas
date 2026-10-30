<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f5f7;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f2933;">

    <tr><td style="background:#141516;padding:18px 28px;">
        <span style="font-size:18px;font-weight:bold;color:#ffffff;letter-spacing:.3px;">{{ $company }}</span>
    </td></tr>

    <tr><td style="padding:28px 28px 8px;">
        <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#141516;">{{ $heading }}</h1>
        <div>{!! $intro_html !!}</div>
    </td></tr>

    @if (! empty($payment_rows))
    <tr><td style="padding:12px 28px 4px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #ffd2b8;background:#fff6f0;border-radius:8px;">
            <tr><td style="padding:14px 16px 6px;font-weight:bold;color:#c4480a;">{{ $payment_title }}</td></tr>
            @foreach ($payment_rows as $label => $value)
            <tr><td style="padding:3px 16px;">
                <span style="color:#52606d;">{{ $label }}:</span>
                <strong>{{ $value }}</strong>
            </td></tr>
            @endforeach
            @if (! empty($payment_note))
            <tr><td style="padding:8px 16px 4px;color:#52606d;font-size:13px;">{!! nl2br(e($payment_note), false) !!}</td></tr>
            @endif
            <tr><td style="padding-bottom:10px;"></td></tr>
        </table>
    </td></tr>
    @elseif (! empty($payment_note))
    <tr><td style="padding:8px 28px 0;color:#52606d;font-size:13px;">{!! nl2br(e($payment_note), false) !!}</td></tr>
    @endif

    @if (! empty($tracking))
    <tr><td style="padding:12px 28px 4px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e4e7eb;border-radius:8px;">
            <tr><td style="padding:14px 16px 6px;font-weight:bold;">Datos del envío</td></tr>
            @if (! empty($tracking['carrier']))
            <tr><td style="padding:3px 16px;"><span style="color:#52606d;">Paquetería:</span> <strong>{{ $tracking['carrier'] }}</strong></td></tr>
            @endif
            @if (! empty($tracking['guia']))
            <tr><td style="padding:3px 16px;"><span style="color:#52606d;">Guía:</span> <strong>{{ $tracking['guia'] }}</strong></td></tr>
            @endif
            @if (! empty($tracking['eta']))
            <tr><td style="padding:3px 16px;"><span style="color:#52606d;">Entrega estimada:</span> <strong>{{ $tracking['eta'] }}</strong></td></tr>
            @endif
            @if (! empty($tracking['url']))
            <tr><td style="padding:10px 16px 14px;">
                <a href="{{ $tracking['url'] }}" style="display:inline-block;background:#ff6213;color:#ffffff;text-decoration:none;padding:9px 18px;border-radius:6px;font-weight:bold;">Rastrear mi paquete</a>
            </td></tr>
            @else
            <tr><td style="padding-bottom:12px;"></td></tr>
            @endif
        </table>
    </td></tr>
    @endif

    <tr><td style="padding:16px 28px 4px;">
        <div style="font-weight:bold;margin-bottom:8px;">Pedido {{ $order_number }}@if ($order_date) <span style="font-weight:normal;color:#52606d;">· {{ $order_date }}</span>@endif</div>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            @foreach ($items as $item)
            <tr>
                <td style="padding:7px 0;border-bottom:1px solid #eef0f2;">
                    {{ $item['name'] }}
                    <span style="color:#7b8794;">· Cant. {{ $item['quantity'] }}</span>
                </td>
                <td align="right" style="padding:7px 0;border-bottom:1px solid #eef0f2;white-space:nowrap;">{{ $item['total'] }}</td>
            </tr>
            @endforeach
            <tr><td style="padding:10px 0 2px;color:#52606d;">Subtotal</td><td align="right" style="padding:10px 0 2px;">{{ $subtotal }}</td></tr>
            <tr><td style="padding:2px 0;color:#52606d;">IVA</td><td align="right" style="padding:2px 0;">{{ $tax }}</td></tr>
            <tr><td style="padding:2px 0;color:#52606d;">Envío</td><td align="right" style="padding:2px 0;">{{ $shipping }}</td></tr>
            <tr><td style="padding:8px 0;font-weight:bold;font-size:16px;">Total</td><td align="right" style="padding:8px 0;font-weight:bold;font-size:16px;">{{ $total }}</td></tr>
        </table>
    </td></tr>

    <tr><td style="padding:8px 28px 20px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="top" width="50%" style="padding-right:8px;">
                    <div style="color:#7b8794;font-size:12px;text-transform:uppercase;letter-spacing:.4px;">Enviar a</div>
                    <div style="font-weight:bold;">{{ $ship_to_name }}</div>
                    @foreach (array_filter($ship_to_lines) as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </td>
                <td valign="top" width="50%" style="padding-left:8px;">
                    <div style="color:#7b8794;font-size:12px;text-transform:uppercase;letter-spacing:.4px;">Método de pago</div>
                    <div style="font-weight:bold;">{{ $payment_method }}</div>
                </td>
            </tr>
        </table>
    </td></tr>

    @if ($footer !== '')
    <tr><td style="padding:0 28px 22px;color:#52606d;font-size:13px;">{!! nl2br(e($footer), false) !!}</td></tr>
    @endif

    <tr><td style="background:#f4f5f7;padding:14px 28px;color:#7b8794;font-size:12px;">
        Este correo se generó automáticamente por tu pedido en {{ $company }}. Puedes responderlo y te atenderemos.
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
