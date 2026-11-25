<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_cliente $controlador */
$tipo_es_alta = $controlador->accion === 'alta';
$tipo_link_guardar = $tipo_es_alta ? $controlador->link_alta_bd : $controlador->link_modifica_bd;
?>
<form method="post" action="<?php echo htmlspecialchars($tipo_link_guardar, ENT_QUOTES, 'UTF-8'); ?>" class="form-additional com-tipo-form" enctype="multipart/form-data" id="form_com_tipo_cliente_<?php echo $tipo_es_alta ? 'alta' : 'modifica'; ?>">
    <section class="com-cliente-card com-cliente-section com-tipo-form-card" aria-labelledby="tipo-datos-titulo">
        <div class="com-cliente-section-heading">
            <span class="com-cliente-step" aria-hidden="true"><i class="bi bi-tag"></i></span>
            <div><h2 id="tipo-datos-titulo">Datos del tipo de cliente</h2><p>Usa un nombre breve y fácil de reconocer al capturar clientes.</p></div>
        </div>
        <div class="com-cliente-fields com-tipo-fields">
            <?php if (!$tipo_es_alta) { echo $controlador->inputs->codigo; } ?>
            <?php echo $controlador->inputs->descripcion; ?>
        </div>
        <p class="com-cliente-help" id="tipo-nombre-ayuda">El nombre debe tener entre 3 y 200 caracteres. Ejemplo: Residencial.</p>
    </section>
    <div class="com-cliente-save-bar">
        <p><i class="bi bi-check2-circle" aria-hidden="true"></i> Revisa el nombre antes de guardar.</p>
        <button type="submit" class="com-cliente-button com-cliente-button-primary"><?php echo $tipo_es_alta ? 'Guardar tipo de cliente' : 'Guardar cambios'; ?></button>
    </div>
</form>
<?php
$tipo_relacionados = array_filter(
    array_merge(
        array_values((array)($controlador->buttons ?? [])),
        array_values((array)($controlador->buttons_childrens_alta ?? [])),
        array_values((array)($controlador->buttons_parents_ir ?? [])),
        array_values((array)($controlador->buttons_parents_alta ?? []))
    ),
    static fn($button): bool => trim((string)$button) !== ''
);
?>
<?php if ($tipo_relacionados !== []): ?>
<details class="com-cliente-related"><summary>Opciones relacionadas</summary><div>
    <?php foreach ($tipo_relacionados as $tipo_button) { echo $tipo_button; } ?>
</div></details>
<?php endif; ?>
