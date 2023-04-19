<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use config\pac;
use gamboamartin\cat_sat\models\cat_sat_motivo_cancelacion;
use gamboamartin\errores\errores;
use gamboamartin\xml_cfdi_4\timbra;
use PDO;
use stdClass;


class fc_cancelacion extends modelo{

    public function __construct(PDO $link){
        $tabla = 'fc_cancelacion';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla,'cat_sat_motivo_cancelacion'=>$tabla,
            'com_sucursal'=>'fc_factura','com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('fc_factura_id','cat_sat_motivo_cancelacion_id','codigo_bis');



        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Cancelaciones';
    }

    public function alta_bd(): array|stdClass
    {
        $cat_sat_motivo_cancelacion = (new cat_sat_motivo_cancelacion(link: $this->link))->registro(
            registro_id: $this->registro['cat_sat_motivo_cancelacion_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener motivo de cancelacion',
                data: $cat_sat_motivo_cancelacion);
        }

        $fc_factura = (new fc_factura(link: $this->link))->registro(
            registro_id: $this->registro['fc_factura_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_factura', data: $fc_factura);
        }

        $filtro['fc_factura.id'] = $fc_factura->fc_factura_id;

        $fc_cfdi_sellado = (new fc_cfdi_sellado(link: $this->link))->filtro_and(filtro:$filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_cfdi_sellado', data: $fc_cfdi_sellado);
        }
        $uuid = $fc_cfdi_sellado->registros[0]['fc_cfdi_sellado_uuid'];

        $codigo = $fc_factura->fc_factura_folio.''.$cat_sat_motivo_cancelacion->cat_sat_motivo_cancelacion_codigo;
        $codigo .= date('YmdHis');

        if(!isset($this->registro['codigo'])){

            $this->registro['codigo'] = $codigo;
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_factura->fc_factura_folio.''.$cat_sat_motivo_cancelacion->cat_sat_motivo_cancelacion_descripcion;
            $descripcion .= date('YmdHis');
            $this->registro['descripcion'] = $descripcion;
        }
        if(!isset($this->registro['codigo_bis'])){
            $codigo_bis = $codigo;
            $this->registro['codigo_bis'] = $codigo_bis;
        }

        $motivo_cancelacion = $cat_sat_motivo_cancelacion->cat_sat_motivo_cancelacion_codigo;
        $rfc_emisor = $fc_factura->org_empresa_rfc;
        $rfc_receptor = $fc_factura->com_cliente_rfc;

        $fc_csd = (new fc_csd(link: $this->link))->registro(registro_id: $fc_factura->fc_csd_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd', data: $fc_csd);
        }

        $filtro = array();
        $filtro['fc_csd.id'] = $fc_csd->fc_csd_id;
        $r_fc_csd_cer = (new fc_cer_csd(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd_cer', data: $r_fc_csd_cer);
        }

        if($r_fc_csd_cer->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe registro', data: $r_fc_csd_cer);
        }
        if($r_fc_csd_cer->n_registros > 1){
            return $this->error->error(mensaje: 'Error  existe mas de un registro', data: $r_fc_csd_cer);
        }

        $fc_csd_cer = $r_fc_csd_cer->registros[0];

        $ruta_cer = $fc_csd_cer['doc_documento_ruta_absoluta'];

        $filtro = array();
        $filtro['fc_csd.id'] = $fc_csd->fc_csd_id;
        $r_fc_csd_key = (new fc_key_csd(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_csd_key', data: $r_fc_csd_key);
        }

        if($r_fc_csd_key->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe registro', data: $r_fc_csd_key);
        }
        if($r_fc_csd_key->n_registros > 1){
            return $this->error->error(mensaje: 'Error  existe mas de un registro', data: $r_fc_csd_key);
        }

        $fc_csd_key = $r_fc_csd_key->registros[0];

        $ruta_key = $fc_csd_key['doc_documento_ruta_absoluta'];


        $cancela = (new timbra())->cancela(motivo_cancelacion: $motivo_cancelacion,rfc_emisor:  $rfc_emisor,
            rfc_receptor:  $rfc_receptor,uuid:  $uuid,pass_csd: $fc_csd->fc_csd_password,ruta_cer: $ruta_cer,
            ruta_key: $ruta_key,total: $fc_factura->fc_factura_total,uuid_sustitucion: '');

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cancelar',data: $cancela);
        }


        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta',data: $r_alta_bd);
        }
        return $r_alta_bd;
    }


}