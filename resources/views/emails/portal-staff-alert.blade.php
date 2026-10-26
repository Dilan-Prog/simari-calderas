<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="background:#0f2a43;color:#ffffff;padding:16px 24px;font-size:13px;letter-spacing:.04em;text-transform:uppercase;">
                        Portal Equiterm &middot; Aviso interno
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#0f2a43;">{{ $heading }}</h1>
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.5;">{{ $intro }}</p>

                        @if (! empty($rows))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 16px;font-size:14px;">
                                @foreach ($rows as $label => $value)
                                    @continue($value === null || $value === '')
                                    <tr>
                                        <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#6b7280;width:38%;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;vertical-align:top;">{!! nl2br(e($value)) !!}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif

                        @foreach ($lines as $line)
                            <p style="margin:0 0 10px;font-size:14px;line-height:1.5;">{{ $line }}</p>
                        @endforeach

                        @foreach ($actions as $label => $url)
                            <p style="margin:0 0 10px;">
                                <a href="{{ $url }}" style="display:inline-block;background:#0f2a43;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;">{{ $label }}</a>
                            </p>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px;background:#f9fafb;color:#6b7280;font-size:12px;">
                        Aviso automático del portal de clientes de Equiterm Industries. También se creó una tarea en el panel.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
