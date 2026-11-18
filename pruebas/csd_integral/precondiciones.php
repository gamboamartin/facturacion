<?php
declare(strict_types=1);
/** Sólo lectura de metadatos dentro de la futura instancia efímera; no crea datos. */
$externo = getenv('QA_HOST_NET_NS');
if (!$externo || readlink('/proc/self/ns/net') === $externo
    || (is_dir('/home') && count(scandir('/home')) > 2) || file_exists('/etc/mysql')
    || !is_file('/qa-instance-id') || !file_exists('/run/mysqld/mysqld.sock')) {
    fwrite(STDERR, "BLOQUEADO: aislamiento de instancia no acreditado\n");
    exit(2);
}
$instance = trim(file_get_contents('/qa-instance-id'));
if (!preg_match('/^[a-f0-9]{32}$/', $instance)) { throw new RuntimeException('Instancia no acreditada'); }
$credenciales = json_decode(file_get_contents('/qa-credenciales-ficticias.json'), true, flags: JSON_THROW_ON_ERROR);
if (($credenciales['base'] ?? '') !== 'qa_csd_integral') { throw new RuntimeException('Base ajena al ensayo'); }
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral',
    $credenciales['usuario'], $credenciales['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ((int)$link->query('SELECT @@skip_networking')->fetchColumn() !== 1) {
    throw new RuntimeException('Red del motor habilitada');
}
$plan = json_decode(file_get_contents(__DIR__ . '/preparacion.json'), true, flags: JSON_THROW_ON_ERROR);
$tablas = array_merge($plan['tablas_sin_ddl_acreditado'], $plan['dependencias_adicionales']);
$consulta = $link->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
$faltan = [];
foreach ($tablas as $tabla) {
    $consulta->execute([$tabla]);
    if ((int)$consulta->fetchColumn() !== 1) { $faltan[] = $tabla; }
}
echo json_encode(['estado' => $faltan ? 'BLOQUEADO' : 'METADATOS_PRESENTES_NO_PRUEBA_ALTA',
    'tablas_faltantes' => $faltan, 'sin_mutaciones' => true]), PHP_EOL;
exit($faltan ? 2 : 0);
