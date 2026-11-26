<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_producto $controlador */
$tipo_producto_es_alta = $controlador->accion === 'alta';
$tipo_producto_link_guardar = $tipo_producto_es_alta ? $controlador->link_alta_bd : $controlador->link_modifica_bd;
?>
<form method="post" action="<?= htmlspecialchars($tipo_producto_link_guardar, ENT_QUOTES, 'UTF-8') ?>" class="form-additional com-producto-form com-tipo-producto-form" id="form_com_tipo_producto_<?= $tipo_producto_es_alta ? 'alta' : 'modifica' ?>">
    <section class="com-cliente-card com-cliente-section com-producto-tipo-card" aria-labelledby="tipo-producto-datos-titulo">
        <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true"><i class="bi bi-tag"></i></span><div><h2 id="tipo-producto-datos-titulo">Datos del tipo de producto</h2><p>Usa un nombre claro para agrupar y encontrar tus productos.</p></div></div>
        <div class="com-cliente-fields com-producto-tipo-fields">
            <?php if (!$tipo_producto_es_alta) { echo $controlador->inputs->codigo; } ?>
            <?php echo $controlador->inputs->descripcion; ?>
        </div>
        <p class="com-cliente-help">Ejemplos: Productos, Servicios o Refacciones.</p>
    </section>
    <div class="com-cliente-save-bar"><p><i class="bi bi-check2-circle" aria-hidden="true"></i> Revisa el nombre antes de guardar.</p><button type="submit" class="com-cliente-button com-cliente-button-primary"><?= $tipo_producto_es_alta ? 'Guardar tipo de producto' : 'Guardar cambios' ?></button></div>
</form>
