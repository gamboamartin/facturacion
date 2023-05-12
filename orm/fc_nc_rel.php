<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_nc_rel extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_nc_rel';
        $columnas = array($tabla=>false,'fc_relacion_nc'=>$tabla,'fc_factura'=>$tabla,
            'cat_sat_tipo_relacion'=>'fc_relacion_nc','fc_nota_credito'=>'fc_relacion_nc',
            'cat_sat_tipo_de_comprobante'=>'fc_factura','com_sucursal'=>'fc_factura','com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('codigo','fc_relacion_nc_id','descripcion_select','alias','codigo_bis',
            'fc_relacion_nc_id');

        $no_duplicados = array();

        $campos_view = array();

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";


        $fc_factura_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_factura_etapa ON fc_factura_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_factura_etapa.fc_factura_id = fc_factura.id ORDER BY fc_factura_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_factura_etapa'] = $fc_factura_etapa;

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas Relacionadas A NC';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_relacion_nc = (new fc_relacion_nc(link: $this->link))->registro(
            registro_id: $this->registro['fc_relacion_nc_id'], retorno_obj: true);

        $fc_factura = (new fc_factura(link: $this->link))->registro(
            registro_id: $this->registro['fc_factura_id'], retorno_obj: true);

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_relacion_nc->fc_relacion_nc_id.$fc_factura->fc_factura_id.time().mt_rand(10,99);
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }


}