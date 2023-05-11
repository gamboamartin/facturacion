<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;


class fc_csd extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_csd';
        $columnas = array($tabla=>false,'org_sucursal'=>$tabla,'org_empresa'=>'org_sucursal',
            'dp_calle_pertenece'=>'org_sucursal','cat_sat_regimen_fiscal'=>'org_empresa',
            'dp_colonia_postal'=>'dp_calle_pertenece');
        $campos_obligatorios = array('codigo','serie','org_sucursal_id','descripcion_select','alias','codigo_bis',
            'no_certificado');

        $no_duplicados = array('serie','codigo','descripcion_select','alias','codigo_bis');

        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');
        $campos_view['serie'] = array('type' => 'inputs');
        $campos_view['no_certificado'] = array('type' => 'inputs');
        $campos_view['password'] = array('type' => 'passwords');

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'CSD';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->registro = $this->campos_base_temp(data: $this->registro,modelo: $this);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $sucursal = (new org_sucursal($this->link))->get_sucursal(org_sucursal_id: $this->registro["org_sucursal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data:  $sucursal);
        }

        $this->registro['codigo'] = $this->get_codigo_aleatorio();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $this->registro);
        }

        $this->registro['descripcion_select'] =  $this->registro['codigo'].' '."{$sucursal['org_empresa_razon_social']}";
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $this->registro);
            }

        $this->registro = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta csd',data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    protected function campos_base_temp(array $data, modelo $modelo, int $id = -1,
                                   array $keys_integra_ds = array('codigo', 'descripcion')): array
    {
        if(isset($data['status'])){
            return $data;
        }

        $sucursal = (new org_sucursal($this->link))->get_sucursal(org_sucursal_id: $data["org_sucursal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data:  $sucursal);
        }

        if(!isset($data['codigo'])){
            $data['codigo'] =  $data['serie'];
        }

        if(!isset($data['codigo_bis'])){
            $data['codigo_bis'] =  $data['codigo'];
        }

        if(!isset($data['descripcion'])){
            $data['descripcion'] =  "{$sucursal['org_empresa_rfc']} - ";
            $data['descripcion'] .= "{$sucursal['org_empresa_razon_social']} - ";
            $data['descripcion'] .= "{$data['serie']}";
        }

        if(!isset($data['descripcion_select'])){
            $data['descripcion_select'] =  "{$data['codigo']} - ";
            $data['descripcion_select'] .= "{$sucursal['org_empresa_rfc']} - ";
            $data['descripcion_select'] .= "{$sucursal['org_empresa_razon_social']}";
        }

        if(!isset($data['alias'])){
            $data['alias'] = $data['codigo'];
        }
        return $data;
    }

    public function get_csd(int $fc_csd_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener CSD',data:  $registro);
        }

        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $registro = $this->campos_base_temp(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $sucursal = (new org_sucursal($this->link))->get_sucursal(org_sucursal_id: $registro["org_sucursal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data:  $sucursal);
        }

        $fc_scd = (new fc_csd(link: $this->link))->registro(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd_codigo', data: $fc_scd);
        }

        $registro['descripcion_select'] =  $fc_scd['fc_csd_codigo'].' '."{$sucursal['org_empresa_razon_social']}";
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $registro);
        }

        $registro = $this->validaciones(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar csd',data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function validaciones(array $data): bool|array
    {
        if(isset($data['status'])){
            return $data;
        }

        $keys = array('serie');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('org_sucursal_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }

}