<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use base\orm\modelo;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_partida extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_partida';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla, 'com_producto' => $tabla,
            'cat_sat_producto' => 'com_producto','cat_sat_unidad' => 'com_producto',
            'cat_sat_tipo_factor' => 'com_producto','cat_sat_factor' => 'com_producto');
        $campos_obligatorios = array('codigo','com_producto_id');

        $campos_view['com_producto_id'] = array('type' => 'selects', 'model' => new com_producto($link));
        $campos_view['fc_factura_id'] = array('type' => 'selects', 'model' => new fc_factura($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');
        $campos_view['cantidad'] = array('type' => 'inputs');
        $campos_view['valor_unitario'] = array('type' => 'inputs');
        $campos_view['descuento'] = array('type' => 'inputs');
        $campos_view['subtotal'] = array('type' => 'inputs');
        $campos_view['total'] = array('type' => 'inputs');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view,no_duplicados: $no_duplicados,tipo_campos: array());

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

        $validacion = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $this->registro = $this->limpia_campos(registro: $this->registro,
            campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id','cat_sat_tipo_impuesto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $this->registro);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar partida', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    public function calculo_sub_total_partida(int $fc_partida_id): float| array
    {
        $data = $this->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $data);
        }
        return $data->fc_partida_cantidad * $data->fc_partida_valor_unitario;
    }

    public function calculo_imp_trasladado(int $fc_partida_id){
        $filtro['fc_partida.id'] = $fc_partida_id;
        $traslado = (new fc_traslado($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $traslado);
        }

        $subtotal = $this->calculo_sub_total_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }


        if((int)$traslado->n_registros > 0){
            return $subtotal * (float)$traslado->registros[0]['cat_sat_factor_factor'];
        }

        return 0;
    }

    public function calculo_imp_retenido(int $fc_partida_id){
        $filtro['fc_partida.id'] = $fc_partida_id;
        $retenido = (new fc_retenido($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $retenido);
        }

        $subtotal = $this->calculo_sub_total_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }

        if((int)$retenido->n_registros > 0){
            return $subtotal * (float)$retenido->registros[0]['cat_sat_factor_factor'];
        }

        return 0;
    }

    public function data_partida_obj(int $fc_partida_id): array|stdClass
    {
        $fc_partida = $this->registro(registro_id: $fc_partida_id, columnas_en_bruto: true,retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partida',data:  $fc_partida);
        }

        $data = new stdClass();
        $data->fc_partida = $fc_partida;

        return $data;
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

    public function get_partida(int $fc_partida_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partida',data:  $registro);
        }

        return $registro;
    }

    private function limpia_campos(array $registro, array $campos_limpiar): array
    {
        foreach ($campos_limpiar as $valor) {
            if (isset($registro[$valor])) {
                unset($registro[$valor]);
            }
        }
        return $registro;
    }

   public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                               array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
   {
       $partida = $this->get_partida(fc_partida_id: $id);
       if(errores::$error){
           return $this->error->error(mensaje: 'Error al obtener partida',data: $partida);
       }

       if(!isset($registro['codigo'])){
           $registro['codigo'] =  $partida["fc_partida_codigo"];
           if(errores::$error){
               return $this->error->error(mensaje: 'Error al obtener el codigo del registro',data: $registro);
           }
       }

       $registro = $this->campos_base(data: $registro,modelo: $this,id: $id);
       if(errores::$error){
           return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
       }

       $validacion = $this->validaciones(data: $registro);
       if(errores::$error){
           return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
       }

       $registro = $this->limpia_campos(registro: $registro,
           campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id','cat_sat_tipo_impuesto_id'));
       if (errores::$error) {
           return $this->error->error(mensaje: 'Error al limpiar campos', data: $registro);
       }

       $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
       if(errores::$error){
           return $this->error->error(mensaje: 'Error al modificar partida',data:  $r_modifica_bd);
       }

       return $r_modifica_bd;
   }

    public function partidas(int $fc_factura_id): array|stdClass
    {
        if($fc_factura_id <=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_partida = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }
        return $r_fc_partida;
    }

    private function valida_cantidades(array $data): bool|array
    {
        $keys = array('cantidad','valor_unitario');
        foreach ($keys as $key){
            if(!isset($data[$key])){
                return $this->error->error(mensaje: "Error debe de existir: $key", data: $data);
            }

            if((int)$data[$key] <= 0){
                return $this->error->error(mensaje: "Error $key no puede ser menor o igual a 0", data: $this->registro);
            }
        }
        return true;
    }

    private function validaciones(array $data): bool|array
    {
        $keys = array('descripcion','codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('com_producto_id', 'fc_factura_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        $valida = $this->valida_cantidades($data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error validar cantidades', data: $valida);
        }

        return true;
    }
}