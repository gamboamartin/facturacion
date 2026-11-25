<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
?>
<section class="com-cliente com-cliente-lista" aria-label="Administración de clientes">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <div class="com-cliente-card com-cliente-directory">
        <div class="com-cliente-directory-heading">
            <div><h2>Directorio de clientes</h2><p>Busca por código, razón social o RFC.</p></div>
            <span class="com-cliente-count" id="clientes-total" role="status" aria-live="polite">Consultando clientes…</span>
        </div>
        <div class="com-cliente-search-bar">
            <div class="com-cliente-search">
                <label for="clientes-busqueda">Buscar cliente</label>
                <div><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="clientes-busqueda" placeholder="Escribe un nombre, RFC o código…" autocomplete="off" aria-controls="clientes-tabla"></div>
            </div>
            <button type="button" class="com-cliente-button com-cliente-button-secondary" id="clientes-limpiar" disabled>Limpiar búsqueda</button>
        </div>
        <div class="com-cliente-table-wrap">
            <table id="clientes-tabla" class="datatable table table-striped" aria-label="Clientes registrados"></table>
        </div>
    </div>
    <p class="com-cliente-footnote"><i class="bi bi-shield-check" aria-hidden="true"></i> Las acciones disponibles dependen de los permisos de tu perfil.</p>
</section>
