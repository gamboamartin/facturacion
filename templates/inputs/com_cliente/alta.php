<?php
use config\generales;
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
$cliente_es_alta = true;
include dirname(__DIR__, 3) . '/views/com_cliente/_formulario.php';
if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente) {
    include dirname(__DIR__, 3) . '/views/com_cliente/_datos_adicionales.php';
}
?>
<div class="com-cliente-save-bar">
    <p><i class="bi bi-check2-circle" aria-hidden="true"></i> Revisa los datos fiscales antes de guardar.</p>
    <button type="submit" class="com-cliente-button com-cliente-button-primary">Guardar cliente</button>
</div>
