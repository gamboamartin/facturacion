<?php
/** Vista común: los enlaces siguen pasando por las acciones y permisos nativos. */
$descarga_id = (int)($controlador->registro_id ?? 0);
$descarga_enlace = static function (mixed $url, string $accion) use ($descarga_seccion, $descarga_id): string {
    if (!is_string($url) || $url === '' || preg_match('/[\\\\\x00-\x20]/', $url)) {
        return '';
    }
    $partes = parse_url($url);
    if ($partes === false || isset($partes['scheme']) || isset($partes['host']) || isset($partes['fragment'])
        || !in_array($partes['path'] ?? '', ['index.php', './index.php'], true)) {
        return '';
    }
    parse_str($partes['query'] ?? '', $parametros);
    foreach (['seccion', 'accion', 'registro_id'] as $clave) {
        $veces = 0;
        foreach (explode('&', $partes['query'] ?? '') as $par) {
            $veces += urldecode(explode('=', $par, 2)[0]) === $clave ? 1 : 0;
        }
        if ($veces !== 1) {
            return '';
        }
    }
    return $descarga_id > 0 && ($parametros['seccion'] ?? null) === $descarga_seccion
        && ($parametros['accion'] ?? null) === $accion
        && ($parametros['registro_id'] ?? null) === (string)$descarga_id ? $url : '';
};
$descarga_xml = $descarga_enlace($controlador->registro->descarga_xml ?? '', 'descarga_xml');
$descarga_pdf = $descarga_enlace($controlador->registro->genera_pdf ?? '', 'genera_pdf');
$descarga_escapar = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<section class="container" id="descargas-por-separado" data-seccion="<?= $descarga_escapar($descarga_seccion) ?>" data-registro-id="<?= $descarga_id ?>">
    <h1><?= $descarga_escapar($descarga_titulo) ?></h1>
    <?php if ($descarga_xml !== '' || $descarga_pdf !== ''): ?>
        <p>Primero se solicitará el XML y después el PDF. Esta pestaña permanecerá abierta.</p>
        <p>Si el navegador bloquea varias descargas, permite descargarlas o usa los enlaces individuales.</p>
        <ul class="list-unstyled">
            <?php if ($descarga_xml !== ''): ?>
                <li><a class="btn btn-success" data-descarga="xml" href="<?= $descarga_escapar($descarga_xml) ?>" target="_blank" rel="noopener">Descargar XML</a> <span data-estado="xml" role="status" aria-live="polite">Pendiente</span></li>
            <?php endif; ?>
            <?php if ($descarga_pdf !== ''): ?>
                <li><a class="btn btn-success" data-descarga="pdf" href="<?= $descarga_escapar($descarga_pdf) ?>" target="_blank" rel="noopener">Descargar PDF</a> <span data-estado="pdf" role="status" aria-live="polite">Pendiente</span></li>
            <?php endif; ?>
        </ul>
        <p id="descargas-resultado" role="status" aria-live="polite">Preparando descargas…</p>
        <noscript>Usa los enlaces individuales para descargar los documentos.</noscript>
        <script src="js/descargas/por_separado.js?v=<?= (int)filemtime(dirname(__DIR__, 2).'/js/descargas/por_separado.js') ?>" defer></script>
    <?php else: ?>
        <p>No hay enlaces de descarga disponibles para esta sesión.</p>
    <?php endif; ?>
</section>
