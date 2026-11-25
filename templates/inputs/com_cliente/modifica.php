<?php
use config\generales;
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
$cliente_es_alta = false;
include dirname(__DIR__, 3) . '/views/com_cliente/_formulario.php';
?>
<div class="com-cliente-save-bar">
    <p><i class="bi bi-check2-circle" aria-hidden="true"></i> Revisa los datos fiscales antes de guardar.</p>
    <button type="submit" class="com-cliente-button com-cliente-button-primary">Guardar cambios</button>
</div>
