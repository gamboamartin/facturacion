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


        $atributos_criticos[] = 'monto_aplicado_factura';

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, atributos_criticos: $atributos_criticos);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas Relacionadas A NC';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $keys = array('fc_relacion_nc_id','fc_factura_id');

        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }

        $registro = $this->init_row_alta(registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $registro);
        }
        $this->registro = $registro;

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }

        $regenera = (new _saldos_fc())->regenera_saldos(fc_factura_id:  $this->registro['fc_factura_id'], link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto regenera',data:  $regenera);
        }

        return $r_alta_bd;
    }

    /**
     * Maqueta los datos del registro previo al alta
     * @param array $registro Registro en proceso
     * @return array|stdClass
     */
    private function data_alta(array $registro): array|stdClass
    {
        $fc_relacion_nc = (new fc_relacion_nc(link: $this->link))->registro(
            registro_id: $registro['fc_relacion_nc_id'], retorno_obj: true);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion',data:  $fc_relacion_nc);
        }

        $fc_factura = (new fc_factura(link: $this->link))->registro(
            registro_id: $registro['fc_factura_id'], retorno_obj: true);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_factura',data:  $fc_factura);
        }

        $data = new stdClass();
        $data->fc_relacion_nc = $fc_relacion_nc;
        $data->fc_factura = $fc_factura;

        return $data;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $registro_previo =$this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro previo',data:  $registro_previo);
        }
        $r_elimina =  parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina);
        }

        $regenera = (new fc_factura(link: $this->link))->regenera_saldos(fc_factura_id:  $registro_previo['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto regenera',data:  $regenera);
        }
        return $r_elimina;
    }

    private function init_row_alta(array $registro){
        $data = $this->data_alta(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener data',data:  $data);
        }
        if(!isset($registro['descripcion'])){
            $descripcion = $data->fc_relacion_nc->fc_relacion_nc_id.$data->fc_factura->fc_factura_id.time().mt_rand(10,99);
            $registro['descripcion'] = $descripcion;
        }
        return $registro;
    }


}