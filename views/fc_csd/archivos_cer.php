<?php
/** Sólo recibe metadatos permitidos del controlador autenticado. */
$archivos = $controlador->archivos_cer_vista;
$escapar = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="col-md-12">
    <div class="widget widget-box box-container widget-mylistings">
        <div class="widget-header"><h2>Archivos CER</h2></div>
        <?php if ($archivos['estado'] !== 'disponible'): ?>
            <p>No se puede consultar el listado de certificados con el acceso actual.</p>
        <?php else: ?>
            <table id="fc_cer_csd" class="table table-striped">
                <thead><tr><th>Id</th><th>Documento</th><th>Tipo</th></tr></thead>
                <tbody>
                <?php foreach ($archivos['filas'] as $archivo): ?>
                    <tr><td><?= (int)$archivo['id'] ?></td><td><?= $escapar($archivo['descripcion']) ?></td><td>CER</td></tr>
                <?php endforeach; ?>
                <?php if ($archivos['filas'] === []): ?>
                    <tr><td colspan="3">No hay certificados CER asociados a este CSD.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
