<?php
declare(strict_types=1);
require_once '/qa/bootstrap.php';
use gamboamartin\errores\errores;
$c = json_decode(file_get_contents('/qa-credenciales-ficticias.json'), true, flags: JSON_THROW_ON_ERROR);
if ($c['base'] !== 'qa_csd_integral' || !is_file('/qa-instance-id')) { throw new RuntimeException('Instancia no acreditada'); }
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral', $c['usuario'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode = ''");
$instalador = new gamboamartin\administrador\instalacion\instalacion();
$metodos = ['_add_adm_sistema', '_add_adm_categoria', '_add_adm_categoria_sistema',
    '_add_adm_categoria_usuario', '_add_adm_categoria_secciones', '_add_adm_seccion_pertenece'];
foreach ($metodos as $metodo) {
    (new ReflectionMethod($instalador, $metodo))->invoke($instalador, $link);
    if (errores::$error) { echo json_encode(['estado'=>'BLOQUEADO','metodo'=>$metodo]), PHP_EOL; exit(1); }
}
echo json_encode(['estado'=>'DDL_ADMINISTRATIVO_FPM_PREPARADO','metodos'=>$metodos,
    'fk_activas'=>(int)$link->query('SELECT @@foreign_key_checks')->fetchColumn()]), PHP_EOL;
