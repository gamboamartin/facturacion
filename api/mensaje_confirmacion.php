<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Número confirmado</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .icon { font-size: 56px; margin-bottom: 16px; }
        h1 { color: #1a1a2e; font-size: 22px; margin-bottom: 8px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 24px; }
        .servicios {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: left;
            margin-bottom: 28px;
        }
        .servicios p { color: #444; font-size: 13px; line-height: 2; }
        .btn {
            display: inline-block;
            background: #25D366;
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover { background: #1ebe5d; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>¡Hola {$nombre}!</h1>
        <p class="subtitle">Tu número ha sido confirmado correctamente.<br>Ya puedes solicitar los siguientes servicios:</p>
        <div class="servicios">
            <p>
                📄 Descargar facturas (PDF y XML)<br>
                🖊️ Timbrar facturas<br>
                🧾 Crear facturas<br>
                👤 Crear clientes<br>
                📦 Crear productos
            </p>
        </div>
        <a href="{$whatsapp_url}" class="btn">💬 Volver al chat</a>
    </div>
</body>
</html>
HTML;
exit;