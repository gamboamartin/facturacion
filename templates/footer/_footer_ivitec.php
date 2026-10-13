<?php
/**
 * Pie de página IVITEC (se activa con generales::$footer_v2).
 *
 * - Logo: el mismo origen que el header (logo de la empresa del sistema).
 * - Enlaces (ayuda, soporte, privacidad): opcionales. Se leen de
 *   generales::$footer_links = ['ayuda' => 'url', 'soporte' => 'url', 'privacidad' => 'url'].
 *   Si la propiedad no existe o una clave no tiene destino, esa opción no se muestra.
 *
 * @var base\controller\controlador_base $controlador
 */
require_once __DIR__ . '/../logo_service.php';
use config\generales;

$iv_footer_e = function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

$iv_footer_logo = $controlador->logo_empresa_url ?? null;
if (empty($iv_footer_logo)) {
    $iv_footer_logo = logo_empresa_url_framework($controlador->link);
}

$iv_footer_rutas = [];
if (property_exists(generales::class, 'footer_links') && is_array(generales::$footer_links)) {
    $iv_footer_rutas = generales::$footer_links;
}

$iv_footer_items = [
    'ayuda' => [
        'Centro de ayuda',
        '<circle cx="12" cy="12" r="9"/><path d="M9.5 8.5a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4M12 16.5h.01"/>',
    ],
    'soporte' => [
        'Soporte',
        '<path d="M4 13v-1a8 8 0 0 1 16 0v1M20 17v2a2 2 0 0 1-2 2h-4"/><rect x="3" y="11" width="4" height="7" rx="2"/><rect x="17" y="11" width="4" height="7" rx="2"/>',
    ],
    'privacidad' => [
        'Aviso de privacidad',
        '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>',
    ],
];

$iv_footer_hay_links = false;
foreach ($iv_footer_items as $clave => $item) {
    if (!empty($iv_footer_rutas[$clave])) {
        $iv_footer_hay_links = true;
    }
}
?>
<footer class="iv-footer" aria-label="Pie de página del sistema">
    <div class="iv-footer__row">
        <div class="iv-footer__brand">
            <?php if (!empty($iv_footer_logo)) { ?>
                <img class="iv-footer__logo" src="<?= $iv_footer_e($iv_footer_logo) ?>" alt="Logo empresa">
            <?php } ?>
            <div class="iv-footer__description">
                <strong>Sistema de facturación</strong>
                <span>Desarrollado por IVITEC</span>
            </div>
        </div>

        <?php if ($iv_footer_hay_links) { ?>
            <nav class="iv-footer__links" aria-label="Ayuda y privacidad">
                <?php foreach ($iv_footer_items as $clave => $item) { ?>
                    <?php if (!empty($iv_footer_rutas[$clave])) { ?>
                        <a href="<?= $iv_footer_e($iv_footer_rutas[$clave]) ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $item[1] ?></svg>
                            <span><?= $iv_footer_e($item[0]) ?></span>
                        </a>
                    <?php } ?>
                <?php } ?>
            </nav>
        <?php } ?>
    </div>
    <p class="iv-footer__copyright">© <?= date('Y') ?> IVITEC. Todos los derechos reservados.</p>
</footer>