<?php
// Este archivo se incluye desde valida_telefono_contacto.php
// Variables disponibles: $nombre, $whatsapp_url
$numero_empresa = '521234567890'; // <- cambiar por número real
$whatsapp_url   = 'https://wa.me/' . $numero_empresa;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificación exitosa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Sora', sans-serif;
    background: #0f1117;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
    overflow: hidden;
  }

  body::before {
    content: '';
    position: fixed;
    top: -40%;
    left: -20%;
    width: 80vw;
    height: 80vw;
    background: radial-gradient(circle, rgba(29,158,117,0.12) 0%, transparent 65%);
    pointer-events: none;
  }

  body::after {
    content: '';
    position: fixed;
    bottom: -30%;
    right: -10%;
    width: 60vw;
    height: 60vw;
    background: radial-gradient(circle, rgba(83,74,183,0.1) 0%, transparent 65%);
    pointer-events: none;
  }

  .card {
    background: #1a1d27;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 20px;
    padding: 48px 40px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    position: relative;
    animation: fadeUp 0.5s ease both;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(29,158,117,0.12);
    border: 1px solid rgba(29,158,117,0.25);
    color: #4ecfa0;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 50px;
    margin-bottom: 28px;
  }

  .badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #1d9e75;
    animation: pulse 2s ease infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.3; }
  }

  .check-wrap {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(29,158,117,0.1);
    border: 1px solid rgba(29,158,117,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    animation: scaleIn 0.4s 0.2s ease both;
  }

  @keyframes scaleIn {
    from { transform: scale(0.7); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
  }

  .check-wrap svg {
    width: 28px;
    height: 28px;
    stroke: #1d9e75;
    stroke-width: 2.5;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  h1 {
    color: #f0f0f0;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }

  .subtitle {
    color: #6b7280;
    font-size: 13.5px;
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 32px;
  }

  .divider {
    height: 1px;
    background: rgba(255,255,255,0.06);
    margin-bottom: 28px;
  }

  .services {
    text-align: left;
    margin-bottom: 32px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .service-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    animation: fadeUp 0.4s ease both;
  }

  .service-item:nth-child(1) { animation-delay: 0.35s; }
  .service-item:nth-child(2) { animation-delay: 0.42s; }
  .service-item:nth-child(3) { animation-delay: 0.49s; }

  .service-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(29,158,117,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .service-icon svg {
    width: 16px;
    height: 16px;
    stroke: #1d9e75;
    stroke-width: 1.8;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .service-text {
    color: #c9cdd8;
    font-size: 13px;
    font-weight: 400;
  }

  .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #1d9e75;
    color: #fff;
    text-decoration: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.01em;
    transition: background 0.2s, transform 0.15s;
    width: 100%;
  }

  .btn:hover  { background: #17856200; transform: translateY(-1px); background: #15b885; }
  .btn:active { transform: translateY(0); }

  .btn svg {
    width: 18px;
    height: 18px;
    fill: white;
    flex-shrink: 0;
  }

  .footer-note {
    margin-top: 20px;
    color: #3d4150;
    font-size: 11px;
    font-weight: 300;
  }
</style>
</head>
<body>

<div class="card">

  <div class="badge">
    <span class="badge-dot"></span>
    Verificación completada
  </div>

  <div class="check-wrap">
    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
  </div>

  <h1>¡Hola, <?= htmlspecialchars(isset($nombre) ? $nombre : 'bienvenido') ?>!</h1>
  <p class="subtitle">Tu número ha sido verificado correctamente.<br>Ya tienes acceso a los servicios disponibles.</p>

  <div class="divider"></div>

  <div class="services">
    <div class="service-item">
      <div class="service-icon">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <span class="service-text">Consulta y descarga de documentos</span>
    </div>
    <div class="service-item">
      <div class="service-icon">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <span class="service-text">Gestión y seguimiento de trámites</span>
    </div>
    <div class="service-item">
      <div class="service-icon">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <span class="service-text">Atención y soporte por WhatsApp</span>
    </div>
  </div>

  <a href="<?= htmlspecialchars($whatsapp_url) ?>" class="btn">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
    </svg>
    Volver al chat
  </a>

  <p class="footer-note">Este enlace de verificación es de un solo uso.</p>

</div>

</body>
</html>