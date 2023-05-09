<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_factura_relacionada extends _relacionada
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura_relacionada';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'fc_relacion' => $tabla,
            'cat_sat_tipo_relacion'=>'fc_relacion','com_sucursal'=>'fc_factura', 'com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('fc_factura_id', 'fc_relacion_id');

        $columnas_extra = array();

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') 
            FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $fc_factura_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_factura_etapa ON fc_factura_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_factura_etapa.fc_factura_id = fc_factura.id ORDER BY fc_factura_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";



        $columnas_extra['fc_factura_etapa'] = "$fc_factura_etapa";


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura Relacionada';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion', 'fc_relacion_id', 'fc_factura_id')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}