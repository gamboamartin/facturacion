<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php $obj_views = new views(); ?>
<?php
    $layout = $controlador->info_relaciones['layout'];
    $relaciones = $controlador->info_relaciones['relaciones'];
    $formatter = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">
                <section class="top-title">
                    <?php include $obj_views->ruta_templates."head/items.php"; ?>
                    <h1 class="h-side-title page-title page-title-big text-color-primary">
                        LAYOUT ID <?php echo $layout['fc_layout_nom_id']; ?>
                        <?php echo $layout['com_cliente_razon_social']; ?>
                        <?php
                            echo $formatter->formatCurrency($layout['fc_layout_nom_total'], 'MXN');
                        ?>
                    </h1>
                    <h4 class="h-side-title page-title text-color-primary">
                        Monto por relacionar:
                        <?php
                            echo $formatter->formatCurrency($layout['fc_layout_nom_monto_por_asignar'], 'MXN');
                        ?>
                    </h4>
                </section> <!-- /. content-header -->
                <?php include $obj_views->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >
                    <?php include $obj_views->ruta_templates . "head/subtitulo.php"; ?>

                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID Factura</th>
                                <th>Folio</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Monto Relacionado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relaciones as $relacion): ?>
                                <tr>
                                    <td><?php echo $relacion['fc_layout_factura_id']; ?></td>
                                    <td><?php echo $relacion['fc_factura_folio']; ?></td>
                                    <td><?php echo $relacion['com_cliente_razon_social']; ?></td>
                                    <td class="txt-center">
                                        <?php
                                            echo $formatter->formatCurrency($relacion['fc_factura_total'], 'MXN');
                                        ?>
                                    </td>
                                    <td class="txt-center">
                                        <?php
                                            echo $formatter->formatCurrency($relacion['fc_layout_factura_monto_relacionado'], 'MXN');
                                        ?>
                                    </td>
                                    <td class="d-flex">
                                        <a href="<?php echo $relacion['url_eliminar']; ?>"
                                           class="btn btn-danger btn-sm flex-fill"
                                           onclick="return confirm('¿Seguro que deseas eliminar esta relacion?');">
                                            🗑 Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>


                        </tbody>
                    </table>

                </div>

            </div>
        </div>
    </div>

</main>
