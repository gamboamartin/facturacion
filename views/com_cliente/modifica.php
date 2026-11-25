<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
?>
<section class="com-cliente com-cliente-form-page" aria-label="Edición de cliente">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <form method="post" action="<?php echo htmlspecialchars($controlador->link_modifica_bd, ENT_QUOTES, 'UTF-8'); ?>" class="form-additional com-cliente-form" enctype="multipart/form-data" id="form_com_cliente_modifica">
        <?php include $controlador->include_inputs_modifica; ?>
    </form>
    <?php
    $cliente_relacionados = array_filter(
        array_merge(
            array_values((array)($controlador->buttons ?? [])),
            array_values((array)($controlador->buttons_childrens_alta ?? [])),
            array_values((array)($controlador->buttons_parents_ir ?? [])),
            array_values((array)($controlador->buttons_parents_alta ?? []))
        ),
        static fn($button): bool => trim((string)$button) !== ''
    );
    ?>
    <?php if ($cliente_relacionados !== []): ?>
        <details class="com-cliente-related"><summary>Opciones relacionadas del cliente</summary><div>
            <?php foreach ($cliente_relacionados as $cliente_button) { echo $cliente_button; } ?>
        </div></details>
    <?php endif; ?>
</section>
