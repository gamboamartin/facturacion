<?php
declare(strict_types=1);

use config\generales;
use config\views;

/** @var \gamboamartin\system\system $controlador */

include "init.php";

$lista_v2 = true;
if(property_exists(generales::class, 'lista_v2')){
    $lista_v2 = (bool)generales::$lista_v2;
}

if(!$lista_v2){
    include (new views())->ruta_template_base . "views/basics/lista.php";
    return;
}
?>

<div class="agentes-page">

    <?php include (new views())->ruta_templates . "mensajes.php"; ?>

    <div class="agentes-header">
        <div class="agentes-header-info">
            <span class="page-icon"><i class="bi bi-list-ul"></i></span>
            <div>
                <h1><?php echo htmlspecialchars((string)$controlador->seccion_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>
        </div>
        <div class="agentes-header-actions">
            <?php foreach ($controlador->acciones_visibles_permitidas as $accion_visible): ?>
                <?php echo $accion_visible->boton; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="agents-table-card">
        <div class="table-scroll">
            <table class="datatable table table-striped agents-table"></table>
        </div>
    </div>

</div>