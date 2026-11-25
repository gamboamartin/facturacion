<?php /** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */ ?>
<?php foreach (['dp_pais' => 'event_change', 'dp_estado' => 'event_full', 'dp_municipio' => 'event_full', 'dp_cp' => 'event_full', 'dp_colonia_postal' => 'event_full', 'dp_calle_pertenece' => 'event_update'] as $cliente_servicio => $cliente_evento) {
    echo $controlador->url_servicios[$cliente_servicio][$cliente_evento];
} ?>
<script src="js/com_cliente/formulario.js?v=20261125"></script>
