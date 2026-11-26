<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_producto $controlador */
?>
<section class="com-cliente com-tipo-producto" aria-label="Edición de tipo de producto">
    <?php include dirname(__DIR__) . '/com_producto/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <?php include __DIR__ . '/_formulario.php'; ?>
</section>
