<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_producto $controlador */
$producto_es_alta = $controlador->accion === 'alta';
$producto_link_guardar = $producto_es_alta ? $controlador->link_com_producto_alta_bd : $controlador->link_com_producto_modifica_bd;
?>
<form method="post" action="<?= htmlspecialchars($producto_link_guardar, ENT_QUOTES, 'UTF-8') ?>" class="form-additional com-producto-form" id="form_com_producto_<?= $producto_es_alta ? 'alta' : 'modifica' ?>">
    <section class="com-cliente-card com-cliente-section" aria-labelledby="producto-identidad-titulo">
        <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">01</span><div><h2 id="producto-identidad-titulo">Identificación del producto</h2><p>Define cómo lo encontrarás en el catálogo y al capturar documentos.</p></div></div>
        <div class="com-cliente-fields">
            <?php echo $controlador->inputs->com_tipo_producto_id; ?>
            <?php echo $controlador->inputs->codigo; ?>
            <?php echo $controlador->inputs->descripcion; ?>
            <?php echo $controlador->inputs->precio; ?>
        </div>
        <p class="com-cliente-help">El código identifica tu producto. Revisa que la descripción y el precio unitario sean correctos.</p>
    </section>
    <section class="com-cliente-card com-cliente-section" aria-labelledby="producto-sat-titulo">
        <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">02</span><div><h2 id="producto-sat-titulo">Clasificación fiscal</h2><p>Identifica el producto o servicio en el catálogo del SAT.</p></div></div>
        <div class="com-cliente-fields com-producto-sat-campo"><?php echo $controlador->inputs->cat_sat_producto; ?></div>
        <?php if ($producto_es_alta): ?>
            <p class="com-cliente-help" id="producto-sat-ayuda">Escribe la clave SAT completa o busca por descripción y elige un resultado.</p>
            <div class="com-producto-resultados" aria-busy="false">
                <div class="com-producto-tabla-sat"><table class="table" aria-label="Resultados del catálogo SAT"><thead><tr><th scope="col">Producto o servicio SAT</th><th scope="col">Seleccionar</th></tr></thead><tbody id="datos_producto"></tbody></table></div>
                <p class="com-producto-sat-estado" id="producto-sat-estado" role="status" aria-live="polite">Escribe al menos 3 caracteres para buscar.</p>
            </div>
        <?php else: ?>
            <p class="com-producto-sat-actual"><span>Producto SAT registrado</span><strong><?= htmlspecialchars((string)($controlador->registro['cat_sat_cve_prod_descripcion_select'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p class="com-cliente-help">La clave SAT se conserva al editar este producto.</p>
        <?php endif; ?>
        <div class="com-cliente-fields com-producto-fiscal-campos">
            <?php echo $controlador->inputs->cat_sat_unidad_id; ?>
            <?php echo $controlador->inputs->cat_sat_obj_imp_id; ?>
        </div>
    </section>
    <div class="com-cliente-save-bar"><p><i class="bi bi-check2-circle" aria-hidden="true"></i> Revisa los datos antes de guardar.</p><button type="submit" class="com-cliente-button com-cliente-button-primary" value="modifica" name="btn_action_next"><?= $producto_es_alta ? 'Guardar producto' : 'Guardar cambios' ?></button></div>
</form>
