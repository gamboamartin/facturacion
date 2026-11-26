<?php
declare(strict_types=1);
$entrada = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$secciones = ['fc_factura', 'fc_nota_credito', 'fc_complemento_pago'];
$seccion = $entrada['seccion'];
if (!in_array($seccion, $secciones, true)) { throw new RuntimeException('Sección QA no permitida'); }
$controlador = (object)['registro_id' => $entrada['id'] ?? 7, 'registro' => (object)[
    'descarga_xml' => $entrada['xml'] ?? './index.php?seccion='.$seccion.'&accion=descarga_xml&registro_id=7&session_id=ficticia',
    'genera_pdf' => $entrada['pdf'] ?? './index.php?seccion='.$seccion.'&accion=genera_pdf&registro_id=7&session_id=ficticia'
]];
require dirname(__DIR__, 3).'/views/'.$seccion.'/descargar_por_separado.php';
