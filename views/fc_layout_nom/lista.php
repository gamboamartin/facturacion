<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php include "init.php"; ?>
<?php
/*
 * Cabecera: las acciones de reporte se agrupan en un desplegable.
 * La visibilidad la sigue decidiendo acciones_visibles_permitidas (permisos por grupo);
 * aquí solo se reparten en dos listas.
 */
$iv_reportes = ['reporte_facturacion', 'reporte_ventas_por_operador', 'reporte_anual'];
$iv_acciones_cabecera = [];
$iv_acciones_reportes = [];

foreach ($controlador->acciones_visibles_permitidas as $accion_visible) {
    if (in_array($accion_visible->adm_accion_descripcion, $iv_reportes, true)) {
        $iv_acciones_reportes[] = $accion_visible;
        continue;
    }
    $iv_acciones_cabecera[] = $accion_visible;
}
?>

<div class="agentes-page">

    <?php include (new views())->ruta_templates . "mensajes.php"; ?>

    <div class="agentes-header">
        <div class="agentes-header-info">
            <span class="page-icon"><i class="bi bi-list-ul"></i></span>
            <h1><?php echo htmlspecialchars((string)$controlador->seccion_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="agentes-header-actions">
            <?php foreach ($iv_acciones_cabecera as $accion_visible): ?>
                <?php echo $accion_visible->boton; ?>
            <?php endforeach; ?>

            <?php if (count($iv_acciones_reportes) > 0): ?>
                <details class="iv-dropdown">
                   <summary class="btn btn-warning item-br">Reportes <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
                    <div class="iv-dropdown__menu">
                        <?php foreach ($iv_acciones_reportes as $accion_visible): ?>
                            <?php echo $accion_visible->boton; ?>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>

    <?php include (new views())->template_path('indicadores/_indicadores_nomina.php'); ?>

    <div class="agents-table-card">
        <div class="table-scroll">
            <table class="datatable table table-striped agents-table"></table>
        </div>
    </div>

</div>
