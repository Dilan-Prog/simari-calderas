<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#ffffff;">
<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1f2933;">
    <div>{!! $bodyHtml !!}</div>
    @if($signatureHtml !== '')
        <div style="margin-top:20px;color:#52606d;">{!! $signatureHtml !!}</div>
    @endif
</div>
</body>
</html>
