<?php /** @var  \gamboamartin\banco\controllers\controlador_bn_banco $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->descripcion; ?>

<?php echo $controlador->inputs->bn_tipo_banco_id; ?>

<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>



<div class="cold-row-12">
    <?php foreach ($controlador->buttons as $button){ ?>
        <?php echo $button; ?>
    <?php }?>
</div>
