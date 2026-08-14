<?php
namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use PDO;

class datos_adicionales_com_cliente_artistik extends modelo {
    public function __construct(PDO $link) {
        $tabla = 'datos_adicionales_com_cliente_artistik';
        $columnas = array($tabla => false);
        parent::__construct(link: $link, tabla: $tabla, columnas: $columnas);
    }
}