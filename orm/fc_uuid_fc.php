<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_uuid_fc extends _uuid_ext {
    public function __construct(PDO $link){
        $tabla = 'fc_uuid_fc';
        $columnas = array($tabla=>false,'fc_uuid'=>$tabla,'fc_relacion'=>$tabla,
            'cat_sat_tipo_relacion'=>'fc_relacion','cat_sat_tipo_de_comprobante'=>'fc_uuid',
            'com_sucursal'=>'fc_uuid','com_cliente'=>'com_sucursal','fc_csd'=>'fc_uuid','org_sucursal'=>'fc_csd',
            'org_empresa'=>'org_sucursal');

        $no_duplicados = array();

        $campos_obligatorios = array('fc_uuid_id','fc_relacion_id');

        $fc_uuid_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_uuid_etapa ON fc_uuid_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_uuid_etapa.fc_uuid_id = fc_uuid.id ORDER BY fc_uuid_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_uuid_etapa'] = "$fc_uuid_etapa";


        $campos_view = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'UUID Externos Relacionados';


    }


}