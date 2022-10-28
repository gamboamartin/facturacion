<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use models\com_producto;
use PDO;
use stdClass;

class fc_partida extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_partida';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla, 'com_producto' => $tabla,
            'cat_sat_producto' => 'com_producto','cat_sat_unidad' => 'com_producto',
            'cat_sat_tipo_factor' => 'com_producto','cat_sat_factor' => 'com_producto');
        $campos_obligatorios = array('codigo','com_producto_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

    public function alta_bd(): array|stdClass
    {
        $registro_imp = $this->registro;

        $key_imp = array('cat_sat_tipo_factor_id','cat_sat_factor_id','cat_sat_tipo_impuesto_id');
        foreach ($key_imp as $key){
            if(isset($this->registro[$key])){
                unset($this->registro[$key]);
            }
        }

        $keys = array('cantidad');
        foreach ($keys as $key){
            if(!isset($this->registro[$key])){
                return $this->error->error(mensaje: 'Error debe de existir '. $key, data: $this->registro);
            }

            if((int)$this->registro[$key] <= 0){
                return $this->error->error(mensaje: 'Error el campo '.$key.' no puede ser menor o igual a 0',
                    data: $this->registro);
            }
        }

        $registro = $this->init_partida_alta_campos(registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar registro', data: $registro);
        }
        $this->registro = $registro;


        $r_alta_bd =  parent::alta_bd(); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar partidas', data: $r_alta_bd);
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


    private function init_partida_alta(string $key, array $registro): array
    {
        if(!isset($registro[$key]) || trim($registro[$key]) === '') {

            $valida = $this->valida_partida_alta(registro: $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
            }

            $com_producto = (new com_producto($this->link))->registro(registro_id: $registro['com_producto_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener producto', data: $com_producto);
            }

            $factura = new fc_factura($this->link);
            $keys_registro = array('fc_factura_id');
            $keys_row = array();

            $registro = $this->asigna_codigo(keys_registro: $keys_registro,keys_row:  $keys_row,
                modelo:  $factura,registro:  $registro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al asignar codigo', data: $registro);
            }

            $registro[$key] .= '-'.$com_producto['cat_sat_producto_descripcion'];

            $registro = $this->asigna_codigo_bis(registro:  $registro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al asignar codigo', data: $registro);
            }
        }
        return $registro;
    }

    private function init_partida_alta_campos(array $registro): array
    {
        $keys_init = array('codigo','codigo_bis');
        foreach ($keys_init as $key){
            $registro = $this->init_partida_alta(key: $key, registro: $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al inicializar registro', data: $registro);
            }
        }

        if(!isset($registro['descripcion_select'])){
            $registro['descripcion_select'] = $registro['descripcion'].' - '.$registro['codigo'];
        }
        if(!isset($registro['alias'])){
            $registro['alias'] = $registro['descripcion_select'];
        }

        return $registro;

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

    /**
     * Valida campos necesarios para la generacion de un codigo automatico
     * @param array $registro Registro en proceso
     * @return bool|array
     * @version 0.74.24
     */
    private function valida_partida_alta(array $registro): bool|array
    {
        $keys = array('fc_factura_id','com_producto_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        return true;
    }

}