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
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:10px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f2933;">

    <tr><td style="background:#141516;padding:18px 28px;">
        <span style="font-size:18px;font-weight:bold;color:#ffffff;letter-spacing:.3px;">Equiterm Industries</span>
        <span style="float:right;font-size:12px;color:#ffb48a;text-transform:uppercase;letter-spacing:.6px;line-height:26px;">Aviso interno</span>
    </td></tr>

    <tr><td style="padding:26px 28px 6px;">
        <h1 style="margin:0 0 6px;font-size:21px;line-height:1.3;color:#141516;">{{ $heading }}</h1>
        <div style="color:#52606d;">Pedido <strong style="color:#141516;">{{ $order_number }}</strong>@if ($order_date) · {{ $order_date }}@endif</div>
    </td></tr>

    <tr><td style="padding:14px 28px 4px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #ffd2b8;background:#fff6f0;border-radius:8px;">
            <tr><td style="padding:12px 16px 4px;"><span style="color:#52606d;">Estado:</span> <strong>{{ $status_label }}</strong></td></tr>
            <tr><td style="padding:2px 16px;"><span style="color:#52606d;">Método de pago:</span> <strong>{{ $payment_method }}</strong></td></tr>
            <tr><td style="padding:2px 16px 12px;"><span style="color:#52606d;">Total:</span> <strong style="font-size:16px;">{{ $total }}</strong></td></tr>
        </table>
    </td></tr>

    <tr><td style="padding:14px 28px 4px;">
        <div style="color:#7b8794;font-size:12px;text-transform:uppercase;letter-spacing:.4px;">Cliente</div>
        <div style="font-weight:bold;">{{ $customer['name'] }}</div>
        <div><a href="mailto:{{ $customer['email'] }}" style="color:#c4480a;">{{ $customer['email'] }}</a></div>
        <div>{{ $customer['phone'] !== '' ? $customer['phone'] : 'Teléfono no capturado' }}</div>
    </td></tr>

    <tr><td style="padding:16px 28px 4px;">
        <div style="font-weight:bold;margin-bottom:8px;">Productos</div>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            @foreach ($items as $item)
            <tr>
                <td style="padding:7px 0;border-bottom:1px solid #eef0f2;">
                    {{ $item['name'] }}
                    @if ($item['sku'] !== '')<span style="color:#7b8794;">({{ $item['sku'] }})</span>@endif
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

    <tr><td style="padding:8px 28px 4px;">
        <div style="color:#7b8794;font-size:12px;text-transform:uppercase;letter-spacing:.4px;">Enviar a</div>
        @foreach ($ship_to_lines as $line)
            <div>{{ $line }}</div>
        @endforeach
    </td></tr>

    @if (! empty($admin_url))
    <tr><td style="padding:18px 28px 26px;">
        <a href="{{ $admin_url }}" style="display:inline-block;background:#ff6213;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-weight:bold;">Ver el pedido en el panel</a>
        <div style="margin-top:10px;font-size:12px;color:#7b8794;">Si respondes a este correo, le escribes directo al cliente.</div>
    </td></tr>
    @else
    <tr><td style="padding-bottom:22px;"></td></tr>
    @endif

</table>
</td></tr>
</table>
</body>
</html>
