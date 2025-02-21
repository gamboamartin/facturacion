<?php
/**
 * @var gamboamartin\facturacion\controllers\controlador_fc_factura $controlador
 */
?>
<iframe name="iframe1" src="<?php echo $controlador->registro->genera_pdf; ?>" width="0px"></iframe>
<iframe name="iframe2" src="<?php echo $controlador->registro->descarga_xml; ?>" width="0px"></iframe>

