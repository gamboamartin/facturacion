<?php
use config\views;
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_cliente $controlador */
?>
<section class="com-cliente com-cliente-lista com-tipo-cliente com-tipo-lista" aria-label="Administración de tipos de cliente">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <div class="com-cliente-card com-cliente-directory">
        <div class="com-cliente-directory-heading">
            <div><h2>Tu catálogo de tipos</h2><p>Clasificaciones disponibles al registrar o editar un cliente.</p></div>
            <span class="com-cliente-count" id="tipos-cliente-total" role="status" aria-live="polite">Consultando tipos…</span>
        </div>
        <div class="com-cliente-search-bar">
            <div class="com-cliente-search">
                <label for="tipos-cliente-busqueda">Buscar tipo de cliente</label>
                <div><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="tipos-cliente-busqueda" placeholder="Escribe el nombre o el identificador…" autocomplete="off" aria-controls="tipos-cliente-tabla"></div>
            </div>
            <button type="button" class="com-cliente-button com-cliente-button-secondary" id="tipos-cliente-limpiar" disabled>Limpiar búsqueda</button>
        </div>
        <div class="com-cliente-table-wrap">
            <table id="tipos-cliente-tabla" class="datatable table table-striped" aria-label="Tipos de cliente registrados"></table>
        </div>
    </div>
    <p class="com-cliente-footnote"><i class="bi bi-shield-check" aria-hidden="true"></i> Cada acción se muestra según los permisos de tu perfil.</p>
</section>
