<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ocurrió un error inesperado — Equiterm Industries</title>
    {{--
        Página de error 100% autocontenida (sin @vite, sin depender del layout
        de la tienda) a propósito -- si algo se rompió a fondo (build, assets,
        etc.), esta pantalla igual debe poder renderizar sin depender de nada
        más que pueda estar fallando también.
    --}}
    <style>
        :root {
            --secondary-color: #ff6213;
            --font-family: 'Inter', Arial, sans-serif;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: var(--font-family);
            background: #f7f7f8;
            color: #141516;
        }
        .error-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .error-card {
            max-width: 480px;
            width: 100%;
            text-align: center;
            background: #ffffff;
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }
        .error-card img {
            max-width: 170px;
            height: auto;
            margin-bottom: 28px;
        }
        .error-card .error-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #fff2e9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 10px;
            color: #141516;
        }
        .error-card p {
            font-size: 15px;
            line-height: 1.6;
            color: #5c5f66;
            margin: 0 0 28px;
        }
        .error-card a.btn {
            display: inline-block;
            background: var(--secondary-color);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 8px;
            transition: opacity 0.15s ease;
        }
        .error-card a.btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="error-wrap">
        <div class="error-card">
            <img src="{{ asset('images/logo/Negro-color/Recurso%205equiterm-logo-negro-color-3x.png') }}" alt="Equiterm Industries">
            <div class="error-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <h1>Ocurrió un error inesperado</h1>
            <p>Ya lo sabemos y estamos trabajando en ello. Mientras tanto, intenta regresar al inicio o vuelve a intentarlo en unos minutos.</p>
            <a href="{{ url('/') }}" class="btn">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
