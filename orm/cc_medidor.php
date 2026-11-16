<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use PDOException;
use stdClass;


class cc_medidor extends _modelo_parent
{
    public function __construct(PDO $link)
    {
        $tabla = 'cc_medidor';
        $columnas = array(
            $tabla => false,
            'cc_tipo_medidor' => $tabla
        );

        $campos_obligatorios = array();

        parent::__construct(
            link: $link,
            tabla: $tabla,
            campos_obligatorios: $campos_obligatorios,
            columnas: $columnas
        );

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Medidor';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        // Candidate: use the controller-populated registro, never global POST.
        $tipo = $this->registro['cc_tipo_medidor_id'] ?? null;
        if ((!is_int($tipo) && !is_string($tipo)) ||
            !preg_match('/^[1-9][0-9]*$/D', (string)$tipo) ||
            strlen((string)$tipo) > 10 ||
            (strlen((string)$tipo) === 10 && strcmp((string)$tipo, '2147483647') > 0)) {
            return (new errores())->error(mensaje: 'Tipo de medidor invalido', data: []);
        }
        $this->registro['cc_tipo_medidor_id'] = (int)$tipo;
        // Retain automatic code generation, while letting ORM build base/audit fields.
        if (!isset($this->registro['codigo'])) {
            $this->registro['codigo'] = $this->get_codigo_aleatorio();
        }
        // ORM owns legitimate actor checks, validation, insert and its standard result.
        return parent::alta_bd(keys_integra_ds: $keys_integra_ds);
    }

}
