<?php
declare(strict_types=1);
if (!getenv('QA_HOST_NET_NS') || readlink('/proc/self/ns/net') === getenv('QA_HOST_NET_NS')
    || file_exists('/etc/mysql') || (is_dir('/home') && count(scandir('/home')) > 2)) {
    throw new RuntimeException('Aislamiento requerido');
}
require_once '/var/www/html/facturacion/config/database.php';
require_once '/var/www/html/facturacion/config/generales.php';
$loader = require '/var/www/html/facturacion/vendor/autoload.php';
$composer = json_decode(file_get_contents('/var/www/html/facturacion/composer.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($composer['autoload']['psr-4'] as $prefijo => $ruta) {
    $loader->addPsr4($prefijo, '/var/www/html/facturacion/' . $ruta, true);
}
$_SESSION = [];
$_POST = [];
$_FILES = [];
