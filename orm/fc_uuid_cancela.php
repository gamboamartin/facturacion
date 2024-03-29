<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_motivo_cancelacion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_uuid_html;
use gamboamartin\xml_cfdi_4\timbra;
use PDO;
use stdClass;


class fc_uuid_cancela extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_uuid_cancela';
        $columnas = array($tabla=>false,'fc_uuid'=>$tabla,'cat_sat_motivo_cancelacion'=>$tabla,'fc_csd'=>'fc_uuid',
            'org_sucursal'=>'fc_csd','com_sucursal'=>'fc_uuid','com_cliente'=>'com_sucursal',
            'dp_calle_pertenece'=>'com_sucursal','dp_colonia_postal'=>'dp_calle_pertenece',
            'dp_cp'=>'dp_colonia_postal','cat_sat_tipo_de_comprobante'=>'fc_uuid', 'org_empresa'=>'org_sucursal');

        $campos_obligatorios = array('codigo','fc_uuid_id','cat_sat_motivo_cancelacion_id');

        $no_duplicados = array();

        $campos_view = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: array(),
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'UUID Externos Cancelados';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_uuid = (new fc_uuid(link: $this->link))->registro(registro_id: $this->registro['fc_uuid_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $fc_uuid);
        }
        $cat_sat_motivo_cancelacion = (new cat_sat_motivo_cancelacion(link: $this->link))->registro(
            registro_id: $this->registro['cat_sat_motivo_cancelacion_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $cat_sat_motivo_cancelacion);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_uuid['fc_uuid_descripcion'];
            $descripcion .= ' '.$cat_sat_motivo_cancelacion['cat_sat_motivo_cancelacion_descripcion'];
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $r_alta_bd);
        }

        $data_csd = (new fc_csd(link: $this->link))->data($fc_uuid['fc_csd_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener rutas', data: $data_csd);
        }

        $cancela = (new timbra())->cancela(
            motivo_cancelacion: $cat_sat_motivo_cancelacion['cat_sat_motivo_cancelacion_codigo'],
            rfc_emisor:  $fc_uuid['org_empresa_rfc'],
            rfc_receptor:  $fc_uuid['com_cliente_rfc'],uuid:  $fc_uuid['fc_uuid_uuid'],
            pass_csd: $data_csd->fc_csd_password,ruta_cer: $data_csd->ruta_cer,
            ruta_key: $data_csd->ruta_key,total: $fc_uuid['fc_uuid_total'],uuid_sustitucion: '');

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cancelar',data: $cancela);
        }


        return $r_alta_bd;
    }




}