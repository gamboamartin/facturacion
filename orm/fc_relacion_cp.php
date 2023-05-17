<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_relacion_cp extends _relacion
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_relacion_cp';
        $columnas = array($tabla => false, 'fc_complemento_pago' => $tabla, 'cat_sat_tipo_relacion' => $tabla);
        $campos_obligatorios = array('fc_complemento_pago_id', 'cat_sat_tipo_relacion_id');

        $columnas_extra = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas Relacionadas';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar relacion', data: $r_alta_bd);
        }
        return $r_alta_bd;


    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);


        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;

    }


}