<?php
declare(strict_types=1);
/** @var controllers\controlador_adm_session $controlador */

$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
);

$loginAction = './index.php?seccion=adm_session&accion=loguea';
require_once __DIR__ . '/../../templates/logo_service.php';
$clientName = nombre_empresa_framework($controlador->link) ?? 'Empresa cliente';
$clientLogoUrl = logo_empresa_url_framework($controlador->link) ?? '';
$errorMessage = $controlador->existe_msj ? $controlador->mensaje_html : '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso | Sistema de Facturación</title>
    <link rel="stylesheet" href="css/ivitec-login/ivitec-login.css">
</head>
<body>
<main class="iv-login" data-ivitec-login>
    <div class="iv-login__pattern" aria-hidden="true"></div>

    <header class="iv-login__topbar">
        <img class="iv-login__brand-logo" src="img/ivitec-login/ivitec-logo.png" alt="IVITEC Tecnología">
        <div class="iv-login__system-name">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 2h9l3 3v17H6z"></path>
                <path d="M14 2v5h5M9 12h6M9 16h6"></path>
            </svg>
            <span>Sistema de Facturación</span>
        </div>
    </header>

    <section class="iv-login__stage">
        <div class="iv-login__visual">
            <div class="iv-login__visual-copy">
                <span class="iv-login__badge">CFDI 4.0</span>
                <h1>Control claro.<br><em>Facturación segura.</em></h1>
                <p>Ingresa a una plataforma diseñada para trabajar con orden, confianza y transparencia.</p>
            </div>
            <div class="iv-login__art" aria-hidden="true">
                <span class="iv-login__orbit iv-login__orbit--one"></span>
                <span class="iv-login__orbit iv-login__orbit--two"></span>
                <img src="img/ivitec-login/infraestructura-ivitec.png" alt="">
            </div>
            <div class="iv-login__secure-caption">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span><strong>Entorno protegido</strong> Conexión segura para tus operaciones</span>
            </div>
        </div>

        <div class="iv-login__form-panel">
            <div class="iv-login__client">
                <div class="iv-login__client-logo">
                    <?php if ($clientLogoUrl !== ''): ?>
                        <img src="<?= $escape($clientLogoUrl) ?>" alt="Logo de <?= $escape($clientName) ?>">
                    <?php else: ?>
                        <div class="iv-login__client-placeholder">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 21h18M5 21V8l7-4v17M12 10h7v11M8 10v1M8 14v1M8 18v1M16 13v1M16 17v1"></path>
                            </svg>
                            <span>LOGO</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="iv-login__client-caption">Portal empresarial</span>
                    <strong><?= $escape($clientName) ?></strong>
                </div>
            </div>

            <div class="iv-login__welcome">
                <span>ACCESO AL SISTEMA</span>
                <h2>Iniciar sesión</h2>
                <p>Captura tus credenciales para continuar.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="iv-login__alert" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v6M12 17h.01"></path>
                    </svg>
                    <span><?= $escape($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= $escape($loginAction) ?>" method="post" data-login-form>
                <div class="iv-login__field">
                    <label for="login-username">Usuario</label>
                    <div class="iv-login__input">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21c.8-4 3.5-6 8-6s7.2 2 8 6"></path>
                        </svg>
                        <input id="login-username" name="user" type="text" placeholder="Nombre de usuario"
                               autocomplete="username" maxlength="150" required autofocus>
                    </div>
                </div>

                <div class="iv-login__field">
                    <label for="login-password">Contraseña</label>
                    <div class="iv-login__input">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="11" rx="2"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path>
                        </svg>
                        <input id="login-password" name="password" type="password" placeholder="Ingresa tu contraseña"
                               autocomplete="current-password" required data-password-input>
                        <button class="iv-login__password-toggle" type="button" aria-label="Mostrar contraseña"
                                aria-pressed="false" data-password-toggle>
                            <svg class="iv-login__eye" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="2.5"></circle>
                            </svg>
                            <svg class="iv-login__eye-off" viewBox="0 0 24 24" aria-hidden="true" hidden>
                                <path d="m3 3 18 18M10.5 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-2.2 3M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6c1 0 2-.2 2.8-.5"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div style="margin-top: 20px;"></div>
                <button class="iv-login__submit" type="submit" data-submit-button>
                    <span data-button-label>Entrar al sistema</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14M13 6l6 6-6 6"></path>
                    </svg>
                </button>
            </form>

            <div class="iv-login__powered">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span>Plataforma desarrollada por <strong>IVITEC</strong></span>
            </div>
        </div>
    </section>

    <footer class="iv-login__footer">
        <span>Tecnología que impulsa tu negocio</span>
        <span>© <?= date('Y') ?> IVITEC Tecnología</span>
    </footer>
</main>

<script src="js/ivitec-login/ivitec-login.js" defer></script>
</body>
</html>