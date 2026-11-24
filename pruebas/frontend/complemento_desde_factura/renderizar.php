<?php
declare(strict_types=1);
// Fixture sintético de la vista; no carga autoload, configuración privada, aplicación, BD ni PAC.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$controlador = (object)['complemento_desde_factura_config' => json_decode(
    stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR)];
include dirname(__DIR__, 3).'/views/fc_factura/complemento_pago.php';
