<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_producto $controlador */
?>
<section class="com-cliente com-producto" aria-label="Alta de producto">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <?php include __DIR__ . '/_formulario.php'; ?>
</section>
