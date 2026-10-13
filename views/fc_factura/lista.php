<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php include "init.php"; ?>

<div class="agentes-page">

    <?php include (new views())->ruta_templates . "mensajes.php"; ?>

    <div class="agentes-header">
        <div class="agentes-header-info">
            <span class="page-icon"><i class="bi bi-receipt"></i></span>
            <h1><?php echo htmlspecialchars((string)$controlador->seccion_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="agentes-header-actions">
            <?php foreach ($controlador->acciones_visibles_permitidas as $accion_visible): ?>
                <?php echo $accion_visible->boton; ?>
            <?php endforeach; ?>
        </div>
    </div>
   <?php include (new views())->template_path('indicadores/_indicadores_facturas.php'); ?>
    <div class="filtros-avanzados filtros-lista">
        <div class="filtro-grupo">
            <label for="fecha_inicio">Fecha Inicio</label>
            <input type="date" id="fecha_inicio" data-ajax="rango-fechas" data-filtro_campo="fc_factura.fecha"
                   data-filtro_key="campo1">

            <label for="fecha_fin">Fecha Fin</label>
            <input type="date" id="fecha_fin" data-ajax="rango-fechas" data-filtro_campo="fc_factura.fecha"
                   data-filtro_key="campo2">
        </div>

        <div class="filtro-grupo">
            <label for="folio">Folio</label>
            <input type="text" id="folio" data-ajax="filtro" data-filtro_campo="fc_factura.folio"
                   placeholder="Ej: A-000107">

            <label for="cantidad-monto">Total</label>
            <input type="text" id="cantidad-monto" data-ajax="filtro" data-filtro_campo="fc_factura.total"
                   placeholder="Ej: 5000">

            <label for="rfc">RFC</label>
            <input type="text" id="rfc" data-ajax="filtro" data-filtro_campo="com_cliente.rfc"
                   placeholder="Ej: ABCD123456XYZ">
        </div>

        <button type="button" id="filtrar" class="iv-btn iv-btn-primary">Filtrar</button>
        <button type="button" id="limpiar" class="iv-btn iv-btn-secondary">Limpiar</button>

        <form method="post" action="<?php echo $controlador->link_exportar_xls; ?>" enctype="multipart/form-data">
            <button id="descargar_excel" class="iv-btn iv-btn-secondary">Descargar Excel</button>
        </form>
    </div>

    <div class="agents-table-card">
        <div class="table-scroll">
            <table class="datatable table table-striped agents-table"></table>
        </div>
    </div>

</div>