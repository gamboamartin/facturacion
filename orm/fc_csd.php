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

        $inserta_etapa = (new _cert())->inserta_etapa(fc_csd_id: $r_alta_bd->registro_id,link:  $this->link,
            pr_etapa_descripcion:  'ALTA', pr_proceso_descripcion: 'CSD');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
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
            $data['codigo'] =  $this->get_codigo_aleatorio();
        }

        if(!isset($data['codigo_bis'])){
            $data['codigo_bis'] =  $data['codigo'];
        }

        if(!isset($data['descripcion'])){
            $data['descripcion'] =  "{$sucursal['org_empresa_rfc']} - ";
            $data['descripcion'] .= "{$sucursal['org_empresa_razon_social']} - ";
        }

        if(!isset($data['descripcion_select'])){
            $data['descripcion_select'] =  "{$data['codigo']} - ";
            $data['descripcion_select'] .= "{$sucursal['org_empresa_razon_social']}";
        }

        if(!isset($data['alias'])){
            $data['alias'] = $data['codigo'];
        }
        return $data;
    }

    final public function data(int $fc_csd_id){
        $fc_csd = $this->registro(registro_id: $fc_csd_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd', data: $fc_csd);
        }

        $ruta_cer = (new fc_cer_csd(link: $this->link))->ruta_cer(fc_csd_id: $fc_csd->fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener ruta cer', data: $ruta_cer);
        }

        $ruta_key = (new fc_key_csd(link: $this->link))->ruta_key(fc_csd_id: $fc_csd->fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener ruta cer', data: $ruta_cer);
        }
        $fc_cer_csd = (new fc_cer_csd(link: $this->link))->row_by_csd(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_cer_csd', data: $fc_cer_csd);
        }
        $fc_key_csd = (new fc_key_csd(link: $this->link))->row_by_csd(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_key_csd', data: $fc_key_csd);
        }

        $data = new stdClass();
        $data->ruta_cer = $ruta_cer;
        $data->ruta_key = $ruta_key;
        $data->fc_csd_password = $fc_csd->fc_csd_password;
        $data->fc_cer_csd = $fc_cer_csd;
        $data->fc_key_csd = $fc_key_csd;
        return $data;
    }

    final public function genera_pems(int $fc_csd_id)
    {
        $out = new stdClass();
        $data_csd = $this->data(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener datos', data: $data_csd);
        }
        $out->fc_csd = $data_csd;

        $data = (new fc_key_csd(link: $this->link))->genera_pem_full(fc_key_csd_id: $data_csd->fc_key_csd['fc_key_csd_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pem', data: $data);
        }
        $out->fc_key_csd = $data;
        $data = (new fc_cer_csd(link: $this->link))->genera_pem_full(fc_cer_csd_id: $data_csd->fc_cer_csd['fc_cer_csd_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pem', data: $data);
        }
        $out->fc_cer_csd = $data;

        return $out;

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
        $fc_csd = (new fc_csd(link: $this->link))->registro(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd_codigo', data: $fc_csd);
        }

        $registro['codigo'] = $fc_csd['fc_csd_codigo'];

        if ($registro['codigo'] == ''){
            $registro['codigo'] = $this->get_codigo_aleatorio();
        }

        $registro = $this->campos_base_temp(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $sucursal = (new org_sucursal($this->link))->get_sucursal(org_sucursal_id: $registro["org_sucursal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data:  $sucursal);
        }

        $registro['descripcion_select'] =  $registro['codigo'].' '."{$sucursal['org_empresa_razon_social']}";
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

    final public function modifica_etapa(string $etapa, int $id)
    {
        $registro['etapa'] = $etapa;
        $upd = parent::modifica_bd(registro: $registro,id:  $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar etapa',data: $upd);
        }
        return $upd;

    }


    final public function tiene_documentos_completos(int $fc_csd_id)
    {
        $existe_key = $this->tiene_file_key(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $existe_key);
        }
        $existe_cer = $this->tiene_file_cer(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $existe_cer);
        }

        $tiene_ambos = false;
        if($existe_cer && $existe_key){
            $tiene_ambos = true;
        }
        return $tiene_ambos;


    }

    final public function tiene_documentos_completos_pem(int $fc_csd_id)
    {
        $existe_key = $this->tiene_file_key_pem(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $existe_key);
        }
        $existe_cer = $this->tiene_file_cer_pem(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $existe_cer);
        }

        $tiene_ambos = false;
        if($existe_cer && $existe_key){
            $tiene_ambos = true;
        }
        return $tiene_ambos;


    }
    final public function tiene_file_cer(int $fc_csd_id)
    {
        $filtro['fc_csd.id'] = $fc_csd_id;
        $existe = (new fc_cer_csd(link: $this->link))->existe(filtro: $filtro );
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar cer',data:  $existe);
        }
        return $existe;

    }

    final public function tiene_file_cer_pem(int $fc_csd_id)
    {
        $filtro['fc_csd.id'] = $fc_csd_id;
        $existe = (new fc_cer_pem(link: $this->link))->existe(filtro: $filtro );
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar cer',data:  $existe);
        }
        return $existe;

    }

    final public function tiene_file_key(int $fc_csd_id)
    {
        $filtro['fc_csd.id'] = $fc_csd_id;
        $existe = (new fc_key_csd(link: $this->link))->existe(filtro: $filtro );
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar key',data:  $existe);
        }
        return $existe;

    }

    final public function tiene_file_key_pem(int $fc_csd_id)
    {
        $filtro['fc_csd.id'] = $fc_csd_id;
        $existe = (new fc_key_pem(link: $this->link))->existe(filtro: $filtro );
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar key',data:  $existe);
        }
        return $existe;

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