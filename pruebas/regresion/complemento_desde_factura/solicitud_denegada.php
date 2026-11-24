<?php
declare(strict_types=1);
require_once __DIR__.'/soporte_seguridad.php';
$_SERVER['REQUEST_METHOD']='POST';
$_SESSION=['usuario_id'=>7,'fc_complemento_factura_csrf'=>'TOKEN-FICTICIO-SIN-VALIDEZ'];
$_POST=['clave_solicitud'=>str_repeat('a',32),'monto'=>'116.00','fecha_pago'=>'2026-11-24T12:00'];
if($argv[1]==='ajeno')$_POST['csrf']='OTRO-TOKEN';
if($argv[1]==='sin_permiso')$_POST['csrf']='TOKEN-FICTICIO-SIN-VALIDEZ';
$pdo=new PDOSeguridadCP();
register_shutdown_function(static function()use($pdo):void {fwrite(STDERR,json_encode(['http'=>http_response_code(),'consultas'=>count($pdo->sql)]));});
(new ControladorSeguridadCP($pdo))->complemento_pago();
throw new RuntimeException('No debe continuar una solicitud rechazada');
