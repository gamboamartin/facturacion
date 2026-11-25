<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_cliente $controlador */
?>
<section class="com-cliente com-tipo-cliente" aria-label="Alta de tipo de cliente">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <?php include __DIR__ . '/_formulario.php'; ?>
</section>
