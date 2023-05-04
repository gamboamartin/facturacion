<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_nota_credito_relacionada extends _relacionada
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_nota_credito_relacionada';
        $columnas = array($tabla => false, 'fc_nota_credito' => $tabla, 'fc_relacion_nc' => $tabla,
            'cat_sat_tipo_relacion'=>'fc_relacion_nc','com_sucursal'=>'fc_nota_credito', 'com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('fc_nota_credito_id', 'fc_relacion_nc_id');

        $columnas_extra = array();

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado_nc.uuid,'') 
            FROM fc_cfdi_sellado_nc WHERE fc_cfdi_sellado_nc.fc_nota_credito_id = fc_nota_credito.id)";

        $fc_nota_credito_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_nota_credito_etapa ON fc_nota_credito_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_nota_credito_etapa.fc_nota_credito_id = fc_nota_credito.id ORDER BY fc_nota_credito_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_nota_credito_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";



        $columnas_extra['fc_nota_credito_etapa'] = "$fc_nota_credito_etapa";


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura Relacionada';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion', 'fc_relacion_nc_id', 'fc_nota_credito_id')): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_relacion = new fc_relacion_nc(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_relacionada(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_relacion = new fc_relacion_nc(link: $this->link);

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_relacion = new fc_relacion_nc(link: $this->link);
        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }




}