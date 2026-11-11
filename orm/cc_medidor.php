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
        $query = "INSERT INTO cc_medidor (
                    tipo_medidor_id,
                    descripcion,
                    codigo
              ) VALUES (
                    :cc_tipo_medidor_id,
                    :descripcion,
                    :codigo
              )";

        try {
            $stmt = $this->link->prepare($query);

            $rs = $stmt->execute([
                ':cc_tipo_medidor_id' => (int)$_POST['cc_tipo_medidor_id'],
                ':descripcion' => $_POST['descripcion'],
                ':codigo' => $this->get_codigo_aleatorio()
            ]);

            if (!$rs) {
                return (new errores())->error(
                    mensaje: 'No se pudo registrar el tipo de medidor',
                    data: []
                );
            }

            $rs = new stdClass();
            $rs->registro_id = $this->link->lastInsertId();
            $rs->cc_tipo_medidor_id = $_POST['cc_tipo_medidor_id'];
            $rs->descripcion = $_POST['descripcion'];

        } catch (PDOException $e) {
            return (new errores())->error(
                mensaje: $e->getMessage(),
                data: $e
            );
        }

        return $rs;
    }

}