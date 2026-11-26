<?php
/** @var gamboamartin\facturacion\controllers\controlador_fc_complemento_pago $controlador */
?>
<iframe name="iframe1" src="<?php echo htmlspecialchars($controlador->registro->genera_pdf, ENT_QUOTES, 'UTF-8'); ?>" width="0px"></iframe>
<iframe name="iframe2" src="<?php echo htmlspecialchars($controlador->registro->descarga_xml, ENT_QUOTES, 'UTF-8'); ?>" width="0px"></iframe>
