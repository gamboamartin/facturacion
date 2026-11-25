<?php /** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */ ?>
<?php foreach (['dp_pais' => 'event_change', 'dp_estado' => 'event_full', 'dp_municipio' => 'event_full', 'dp_cp' => 'event_full'] as $cliente_servicio => $cliente_evento) {
    echo $controlador->url_servicios[$cliente_servicio][$cliente_evento];
} ?>
<script>
window.clientesMetodosPagoPermitidos = <?php echo json_encode((new \gamboamartin\cat_sat\models\_validacion())->metodo_pago_permitido, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="js/com_cliente/formulario.js?v=20261125"></script>
<script src="js/com_cliente/cif.js?v=20261125"></script>
