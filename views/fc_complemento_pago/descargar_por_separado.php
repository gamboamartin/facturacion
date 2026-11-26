<?php
/** @var gamboamartin\facturacion\controllers\controlador_fc_complemento_pago $controlador */
$pdf = $controlador->registro->genera_pdf;
$xml = $controlador->registro->descarga_xml;
?>
<div class="container">
    <h3>Descargar complemento de pago</h3>
    <p>Espera a que terminen las descargas. Si alguna no inicia, usa su enlace individual.</p>
    <?php if ($pdf !== '') { ?>
        <a class="btn btn-success" href="<?php echo htmlspecialchars($pdf, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Descargar PDF</a>
        <iframe name="iframe1" src="<?php echo htmlspecialchars($pdf, ENT_QUOTES, 'UTF-8'); ?>" width="0px" height="0px" title="Descarga PDF" style="border:0"></iframe>
    <?php } ?>
    <?php if ($xml !== '') { ?>
        <a class="btn btn-success" href="<?php echo htmlspecialchars($xml, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Descargar XML</a>
        <iframe name="iframe2" src="<?php echo htmlspecialchars($xml, ENT_QUOTES, 'UTF-8'); ?>" width="0px" height="0px" title="Descarga XML" style="border:0"></iframe>
    <?php } ?>
    <?php if ($pdf === '' && $xml === '') { ?>
        <p>No hay enlaces de descarga disponibles para esta sesión.</p>
    <?php } ?>
    <p>Cierra esta pestaña cuando finalicen las descargas.</p>
</div>
