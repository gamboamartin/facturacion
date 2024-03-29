<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cfdi_sellado extends _sellado
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_cfdi_sellado';
        $columnas = array($tabla => false, 'fc_factura' => $tabla);

        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'CFDI Sellado';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_elimina = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina);
        }
        return $r_elimina;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica);
        }
        return $r_modifica;
    }

    public function status(string $campo, int $registro_id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $r_modifica = parent::status($campo, $registro_id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica);
        }
        return $r_modifica;
    }


}