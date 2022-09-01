<?php
namespace models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_partida extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
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

            $registro[$key] = $registro['fc_factura_id'].' '.
                $com_producto['cat_sat_producto_descripcion'];
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
     *
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