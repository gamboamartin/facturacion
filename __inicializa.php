<?php

use base\conexion;
use gamboamartin\errores\errores;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

$link->beginTransaction();

$organigrama = new gamboamartin\organigrama\instalacion\instalacion();

$instala = $organigrama->instala(link: $link);
if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar organigrama', data: $instala);
}

print_r($instala);


$comercial = new gamboamartin\comercial\instalacion\instalacion();

$instala = $comercial->instala(link: $link);
if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar comercial', data: $instala);
}

print_r($instala);


$facturacion = new gamboamartin\facturacion\instalacion\instalacion();

$instala = $facturacion->instala(link: $link);
if(errores::$error){
    $link->rollBack();
    $error = (new errores())->error(mensaje: 'Error al instalar facturacion', data: $instala);
}

print_r($instala);

$link->commit();


