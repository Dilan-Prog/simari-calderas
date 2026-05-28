<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cotización {{ $quote->quote_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f8;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f8;padding:32px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;max-width:600px;">
                <!-- Header -->
                <tr>
                    <td style="background:#1a1a2e;padding:28px 36px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <div style="font-size:20px;font-weight:bold;color:#e8612c;">Equiterm Industries</div>
                                    <div style="font-size:12px;color:#888;margin-top:2px;">contacto@equiterm.mx</div>
                                </td>
                                <td align="right">
                                    <div style="font-size:11px;color:#666;text-transform:uppercase;letter-spacing:1px;">Cotización</div>
                                    <div style="font-size:16px;font-weight:bold;color:#e8612c;font-family:Courier New,monospace;">{{ $quote->quote_number }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:32px 36px;">
                        <p style="font-size:15px;color:#1a1a2e;margin:0 0 8px;">Estimado/a <strong>{{ $quote->guest_name }}</strong>,</p>
                        <p style="font-size:14px;color:#444;line-height:1.7;margin:0 0 24px;">
                            Gracias por su interés en Equiterm Industries. Adjunto a este correo encontrará la cotización <strong>{{ $quote->quote_number }}</strong>
                            con los detalles de los productos y servicios solicitados.
                        </p>

                        <!-- Summary box -->
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8f8;border-radius:8px;padding:20px;margin-bottom:24px;">
                            <tr>
                                <td>
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:11px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Folio</td>
                                            <td style="font-size:11px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Total</td>
                                            @if($quote->valid_until)
                                            <td style="font-size:11px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Válida hasta</td>
                                            @endif
                                        </tr>
                                        <tr>
                                            <td style="font-size:15px;font-weight:bold;color:#e8612c;font-family:Courier New,monospace;padding-top:4px;">{{ $quote->quote_number }}</td>
                                            <td style="font-size:15px;font-weight:bold;color:#1a1a2e;padding-top:4px;">{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</td>
                                            @if($quote->valid_until)
                                            <td style="font-size:15px;font-weight:bold;color:#1a1a2e;padding-top:4px;">{{ $quote->valid_until->format('d/m/Y') }}</td>
                                            @endif
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:14px;color:#444;line-height:1.7;margin:0 0 16px;">
                            Por favor, revise el documento adjunto en formato PDF. Si tiene alguna pregunta o desea proceder con el pedido, no dude en contactarnos.
                        </p>

                        @if($quote->notes)
                        <div style="background:#fffbf5;border-left:3px solid #e8612c;padding:12px 16px;border-radius:0 6px 6px 0;margin-bottom:16px;">
                            <p style="font-size:11px;color:#999;text-transform:uppercase;letter-spacing:.5px;margin:0 0 6px;">Nota</p>
                            <p style="font-size:13px;color:#555;line-height:1.6;margin:0;">{{ $quote->notes }}</p>
                        </div>
                        @endif

                        <p style="font-size:14px;color:#444;line-height:1.7;margin:0;">
                            Atentamente,<br>
                            <strong style="color:#1a1a2e;">Equiterm Industries</strong>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f0f0f0;padding:16px 36px;text-align:center;">
                        <p style="font-size:10px;color:#aaa;margin:0;">
                            Este correo fue enviado desde el sistema de cotizaciones de Equiterm Industries.
                            El documento adjunto en PDF contiene el detalle completo de la cotización.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
