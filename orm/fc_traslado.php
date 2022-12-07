<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_traslado extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_traslado';
        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida');
        $campos_obligatorios = array('codigo','fc_partida_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        $campos_view['fc_partida_id'] = array('type' => 'selects', 'model' => new fc_partida($link));
        $campos_view['cat_sat_tipo_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_factor($link));
        $campos_view['cat_sat_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_factor($link));
        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['cat_sat_tipo_impuesto_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_impuesto($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        if(!isset($this->registro['codigo'])){
            $this->registro['codigo'] =  $this->get_codigo_aleatorio();
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $this->registro);
            }
        }

        $this->registro = $this->campos_base(data: $this->registro,modelo: $this);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $this->registro = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $this->registro);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar traslados', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    public function get_codigo_aleatorio(int $longitud = 6): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';

        for($i = 0; $i < $longitud; $i++) {
            $random_character = $chars[mt_rand(0, strlen($chars) - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    public function get_traslado(int $fc_traslado_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_traslado_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data:  $registro);
        }

        return $registro;
    }

    public function get_traslados(int $fc_partida_id): array|stdClass|int
    {
        $filtro['fc_partida.id']  = $fc_partida_id;
        $registro = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslados',data:  $registro);
        }

        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $traslado = $this->get_traslado(fc_traslado_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data: $traslado);
        }

        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $traslado["fc_traslado_codigo"];
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro',data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $registro = $this->validaciones(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar traslados',data:  $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function validaciones(array $data): array
    {
        $keys = array('descripcion','codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('fc_partida_id', 'cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }
}