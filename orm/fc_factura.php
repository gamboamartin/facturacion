<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;

use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_factura';
        $columnas = array($tabla=>false,'fc_csd'=>$tabla, 'cat_sat_forma_pago'=>$tabla,'cat_sat_metodo_pago'=>$tabla,
            'cat_sat_moneda'=>$tabla, 'com_tipo_cambio'=>$tabla, 'cat_sat_uso_cfdi'=>$tabla,
            'cat_sat_tipo_de_comprobante'=>$tabla, 'cat_sat_regimen_fiscal'=>$tabla, 'com_sucursal'=>$tabla,
            'com_cliente'=>'com_sucursal', 'dp_calle_pertenece'=>$tabla, 'dp_calle' => 'dp_calle_pertenece',
            'dp_colonia_postal'=>'dp_calle_pertenece', 'dp_colonia'=>'dp_colonia_postal', 'dp_cp'=>'dp_colonia_postal',
            'dp_municipio'=>'dp_cp', 'dp_estado'=>'dp_municipio','dp_pais'=>'dp_estado','org_sucursal'=>'fc_csd',
            'org_empresa'=>'org_sucursal');

        $campos_view['fc_csd_id'] = array('type' => 'selects', 'model' => new fc_csd($link));
        $campos_view['cat_sat_forma_pago_id'] = array('type' => 'selects', 'model' => new cat_sat_forma_pago($link));
        $campos_view['cat_sat_metodo_pago_id'] = array('type' => 'selects', 'model' => new cat_sat_metodo_pago($link));
        $campos_view['cat_sat_moneda_id'] = array('type' => 'selects', 'model' => new cat_sat_moneda($link));
        $campos_view['com_tipo_cambio_id'] = array('type' => 'selects', 'model' => new com_tipo_cambio($link));
        $campos_view['cat_sat_uso_cfdi_id'] = array('type' => 'selects', 'model' => new cat_sat_uso_cfdi($link));
        $campos_view['cat_sat_tipo_de_comprobante_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_de_comprobante($link));
        $campos_view['dp_calle_pertenece_id'] = array('type' => 'selects', 'model' => new dp_calle_pertenece($link));
        $campos_view['cat_sat_regimen_fiscal_id'] = array('type' => 'selects', 'model' => new cat_sat_regimen_fiscal($link));
        $campos_view['com_sucursal_id'] = array('type' => 'selects', 'model' => new com_sucursal($link));
        $campos_view['folio'] = array('type' => 'inputs');
        $campos_view['serie'] = array('type' => 'inputs');
        $campos_view['version'] = array('type' => 'inputs');
        $campos_view['exportacion'] = array('type' => 'inputs');
        $campos_view['fecha'] = array('type' => 'dates');
        $campos_view['subtotal'] = array('type' => 'inputs');
        $campos_view['descuento'] = array('type' => 'inputs');
        $campos_view['impuestos_trasladados'] = array('type' => 'inputs');
        $campos_view['impuestos_retenidos'] = array('type' => 'inputs');
        $campos_view['total'] = array('type' => 'inputs');

        $campos_obligatorios = array('folio', 'fc_csd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id',
            'cat_sat_moneda_id', 'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'cat_sat_tipo_de_comprobante_id',
            'dp_calle_pertenece_id', 'cat_sat_regimen_fiscal_id', 'com_sucursal_id','exportacion');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;
    }

    /**
     * @return array|stdClass
     */
    public function alta_bd(): array|stdClass
    {

        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $registro = $this->init_data_alta_bd(registro:$this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar registro',data: $registro);
        }

        $this->registro = $registro;

        $r_alta_bd =  parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta accion',data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    /**
     * Carga un descuento nuevo a un descuento previo
     * @param float $descuento Descuento previo
     * @param array $partida Partida a sumar descuento
     * @return float|array
     * @version 0.117.27
     */
    private function carga_descuento(float $descuento, array $partida): float|array
    {
        if($descuento < 0.0){
            return $this->error->error(mensaje: 'Error el descuento previo no puede ser menor a 0',data: $descuento);
        }

        $keys = array('fc_partida_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro: $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida',data: $valida);
        }

        $descuento_nuevo = $this->descuento_partida(fc_partida_id: $partida['fc_partida_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener descuento',data: $descuento_nuevo);
        }
        return round($descuento+$descuento_nuevo,2);
    }

    private function defaults_alta_bd(array $registro, stdClass $registro_csd): array
    {

        $keys = array('com_sucursal_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $keys = array('serie','folio');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $registro_com_sucursal = (new com_sucursal($this->link))->registro(
            registro_id: $registro['com_sucursal_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursal',data: $registro_com_sucursal);
        }
        if(!isset($registro['codigo'])) {
            $registro['codigo'] = $registro['serie'] . ' ' . $registro['folio'];
        }
        if(!isset($registro['codigo_bis'])) {
            $registro['codigo_bis'] = $registro['serie'].' ' .$registro['folio'];
        }
        if(!isset($registro['descripcion'])) {
            $descripcion = $this->descripcion_select_default(registro: $registro,registro_csd: $registro_csd,
                registro_com_sucursal: $registro_com_sucursal);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error generar descripcion',data: $descripcion);
            }
            $registro['descripcion'] =$descripcion;
        }
        if(!isset($registro['descripcion_select'])) {
            $descripcion_select = $this->descripcion_select_default(registro: $registro,registro_csd: $registro_csd,
                registro_com_sucursal: $registro_com_sucursal);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error generar descripcion',data: $descripcion_select);
            }
            $registro['descripcion_select'] =$descripcion_select;
        }
        if(!isset($registro['alias'])) {
            $registro['alias'] = $registro['descripcion_select'];
        }
        
        $hora =  date('h:i:s');
        if(isset($registro['fecha'])) {
            $registro['fecha'] = $registro['fecha'].' '.$hora;
        }
        return $registro;
    }

    private function default_alta_emisor_data(array $registro, stdClass $registro_csd): array
    {
        $registro['dp_calle_pertenece_id'] = $registro_csd->dp_calle_pertenece_id;
        $registro['cat_sat_regimen_fiscal_id'] = $registro_csd->cat_sat_regimen_fiscal_id;
        return $registro;
    }

    /**
     */
    private function del_partidas(array $fc_partidas): array
    {
        $dels = array();
        foreach ($fc_partidas as $fc_partida){
            $del = (new fc_partida($this->link))->elimina_bd(id:$fc_partida['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al eliminar partida',data:  $del);
            }
            $dels[] = $del;
        }
        return $dels;
    }

    private function descripcion_select_default(array $registro, stdClass $registro_csd,
                                                stdClass $registro_com_sucursal): string
    {
        $descripcion_select = $registro['folio'].' ';
        $descripcion_select .= $registro_csd->org_empresa_razon_social.' ';
        $descripcion_select .= $registro_com_sucursal->com_cliente_razon_social;
        return $descripcion_select;
    }

    /**
     * Obtiene y redondea un descuento de una partida
     * @param int $fc_partida_id partida
     * @return float|array
     * @version 0.98.26
     */
    private function descuento_partida(int $fc_partida_id): float|array
    {
        if($fc_partida_id <=0 ){
            return $this->error->error(mensaje: 'Error $fc_partida_id debe ser mayor a 0',data: $fc_partida_id);
        }
        $fc_partida = (new fc_partida($this->link))->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener $fc_partida',data: $fc_partida);
        }

        $descuento = $fc_partida->fc_partida_descuento;

        return round($descuento,4);


    }

    public function elimina_bd(int $id): array|stdClass
    {

        $del = $this->elimina_partidas(fc_factura_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar partida',data:  $del);
        }

        $r_elimina_factura =  parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar factura',data:  $r_elimina_factura);
        }
        return $r_elimina_factura;
    }

    /**
     */
    private function elimina_partidas(int $fc_factura_id): array
    {
        $fc_partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data:  $fc_partidas);
        }

        $del = $this->del_partidas(fc_partidas: $fc_partidas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar partida',data:  $del);
        }
        return $del;
    }

    public function get_factura(int $fc_factura_id): array|stdClass|int
    {
        $hijo = array();
        $hijo['fc_partida']['filtros'] = array();
        $hijo['fc_partida']['filtros_con_valor'] = array('fc_factura.id'=> $fc_factura_id);
        $hijo['fc_partida']['nombre_estructura'] = 'partidas';
        $hijo['fc_partida']['namespace_model'] = 'gamboamartin\\facturacion\\models';
        $registro = $this->registro(registro_id: $fc_factura_id,hijo: $hijo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $registro);
        }

        $registro['fc_factura_sub_total'] = $this->get_factura_sub_total(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener el subtotal de la factura',data:  $registro);
        }

        $registro['fc_factura_total'] = $this->get_factura_total(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener el total de la factura',data:  $registro);
        }

        $conceptos = array();

        foreach ($registro['partidas'] as $key => $partida){

            $traslados = (new fc_traslado($this->link))->get_traslados(fc_partida_id: $partida['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el traslados de la partida',data:  $traslados);
            }

            $retenidos = (new fc_retenido($this->link))->get_retenidos(fc_partida_id: $partida['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el retenidos de la partida',data:  $retenidos);
            }

            $registro['partidas'][$key]['traslados'] = $traslados->registros;

            $concepto = new stdClass();
            $concepto->clave_prod_serv = $partida['com_producto_id'];
            $concepto->cantidad = $partida['fc_partida_cantidad'];
            $concepto->clave_unidad = $partida['cat_sat_unidad_codigo'];
            $concepto->descripcion = $partida['com_producto_descripcion'];
            $concepto->valor_unitario = $partida['fc_partida_valor_unitario'];
            $concepto->importe = $partida['fc_partida_importe'];
            $concepto->objeto_imp = $partida['cat_sat_obj_imp_codigo'];
            $concepto->no_identificacion = $partida['com_producto_codigo'];;
            $concepto->unidad = $partida['cat_sat_unidad_descripcion'];
            $concepto->impuestos = array();
            $concepto->impuestos[0] = new stdClass();
            $concepto->impuestos[0]->traslados = array();
            $concepto->impuestos[0]->retenciones = array();

            $impuestos = $this->maqueta_impuesto(impuestos: $traslados);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al maquetar traslados',data:  $impuestos);
            }

            $registro['traslados'] = $impuestos;
            $concepto->impuestos[0]->traslados = $impuestos;

            $impuestos = $this->maqueta_impuesto(impuestos: $retenidos);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al maquetar retenciones',data:  $impuestos);
            }

            $registro['retenidos'] = $impuestos;
            $concepto->impuestos[0]->retenciones = $impuestos;

            $conceptos[] = $concepto;
        }

        $registro['conceptos'] = $conceptos;
        $registro['total_impuestos_trasladados'] = 1;

        return $registro;
    }

    private function maqueta_impuesto(stdClass $impuestos): array{

        $imp = array();

        foreach ($impuestos->registros as $impuesto){
            $impuesto_obj = new stdClass();
            $impuesto_obj->base = $impuesto['fc_partida_cantidad'] * $impuesto['fc_partida_valor_unitario'] - $impuesto['fc_partida_descuento'];
            $impuesto_obj->impuesto = $impuesto['cat_sat_tipo_impuesto_descripcion'];
            $impuesto_obj->tipo_factor = $impuesto['cat_sat_tipo_factor_descripcion'];
            $impuesto_obj->tasa_o_cuota = $impuesto['cat_sat_factor_factor'];
            $impuesto_obj->importe = $impuesto_obj->base * $impuesto_obj->tasa_o_cuota;
            $imp[] = $impuesto_obj;
        }
        return $imp;
    }

    public function get_factura_sub_total(int $fc_factura_id): float|array
    {
        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data: $partidas);
        }

        $subtotal = 0.0;

        foreach ($partidas as $valor) {
            $subtotal += (new fc_partida($this->link))->calculo_sub_total_partida($valor['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener calculo ', data: $subtotal);
            }
        }
        return $subtotal;
    }

    public function get_factura_imp_trasladados(int $fc_factura_id): float|array
    {
        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data: $partidas);
        }
        $imp_traslado= 0.0;

        foreach ($partidas as $partida) {
            $imp_traslado += (new fc_partida($this->link))->calculo_imp_trasladado($partida['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener calculo ', data: $imp_traslado);
            }
        }

        return $imp_traslado;
    }
    
    public function get_factura_imp_retenidos(int $fc_factura_id): float|array
    {
        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data: $partidas);
        }

        $imp_traslado= 0.0;

        foreach ($partidas as $valor) {
            $imp_traslado += (new fc_partida($this->link))->calculo_imp_retenido($valor['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener calculo ', data: $imp_traslado);
            }
        }

        return $imp_traslado;
    }

    /**
     * Obtiene el total de descuento de una factura
     * @param int $fc_factura_id Identificador de factura
     * @return float|array
     * @version 0.119.26
     */
    public function get_factura_descuento(int $fc_factura_id): float|array
    {
        if($fc_factura_id<=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }

        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data: $partidas);
        }

        $descuento = $this->suma_descuento_partida(partidas: $partidas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar descuentos',data: $descuento);
        }

        return $descuento;
    }

    public function get_factura_total(int $fc_factura_id): float|array
    {
        $sub_total = $this->get_factura_sub_total(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sub_total',data:  $sub_total);
        }

        $descuento = $this->get_factura_descuento(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener descuento',data:  $descuento);
        }

        $imp_trasladados = $this->get_factura_imp_trasladados(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener imp_trasladados',data:  $imp_trasladados);
        }

        $imp_retenidos = $this->get_factura_imp_retenidos(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener imp_retenidos',data:  $imp_retenidos);
        }

        return $sub_total - $descuento - $imp_trasladados - $imp_retenidos;
    }

    /**
     * Obtiene las partidas de una factura
     * @param int $fc_factura_id Factura a validar
     * @return array
     * @version 0.83.26
     */
    private function get_partidas(int $fc_factura_id): array
    {
        if($fc_factura_id<=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_partida = (new fc_partida($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data:  $r_fc_partida);
        }

        return $r_fc_partida->registros;
    }

    /**
     * Inicializa los datos de un registro
     * @param array $registro
     * @return array
     */
    private function init_data_alta_bd(array $registro): array
    {
        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }
        $registro_csd = (new fc_csd($this->link))->registro(registro_id: $registro['fc_csd_id'],retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc csd',data: $registro_csd);
        }



        $registro = $this->limpia_alta_factura(registro:$registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar keys',data: $registro);
        }



        $registro = $this->default_alta_emisor_data(registro: $registro, registro_csd: $registro_csd);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar keys',data: $registro);
        }

        $keys = array('com_sucursal_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $keys = array('serie','folio');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }


        $registro = $this->defaults_alta_bd(registro:$registro,registro_csd:  $registro_csd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar registro',data: $registro);
        }

       return $registro;
    }

    /**
     * Limpia los parametros de una factura
     * @param array $registro registro en proceso
     * @return array
     * @version 0.127.26
     */
    private function limpia_alta_factura(array $registro): array
    {

        $keys = array('descuento','subtotal','total', 'impuestos_trasladados','impuestos_retenidos');
        foreach ($keys as $key){
            $registro = $this->limpia_si_existe(key:$key, registro:$registro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al limpiar key',data: $registro);
            }
        }

        return $registro;
    }

    /**
     * Limpia un key de un registro si es que existe
     * @param string $key Key a limpiar
     * @param array $registro Registro para aplicacion de limpieza
     * @return array
     * @version 0.115.26
     */
    private function limpia_si_existe(string $key, array $registro): array
    {
        $key = trim($key);
        if($key === ''){
            return $this->error->error(mensaje: 'Error key esta vacio',data: $key);
        }
        if(isset($registro[$key])){
            unset($registro[$key]);
        }
        return $registro;
    }

    /**
     * Obtiene el subtotal de una factura
     * @param int $fc_factura_id Factura
     * @return float|int|array
     * @version 0.96.26
     */
    public function sub_total(int $fc_factura_id): float|int|array
    {
        if($fc_factura_id<=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }

        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partidas',data: $partidas);
        }
        $sub_total = 0;
        foreach ($partidas as $partida){
            $sub_total += $this->sub_total_partida(fc_partida_id: $partida['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener sub total',data: $sub_total);
            }
            $sub_total = round($sub_total,2);
        }

        if($sub_total<=0.0){
            return $this->error->error(mensaje: 'Error al obtener sub total debe ser mayor a 0',data: $sub_total);
        }

        return $sub_total;

    }

    /**
     * Calcula el subtotal de una partida
     * @param int $fc_partida_id Partida a verificar sub total
     * @return float|array
     * @version 0.95.26
     */
    private function sub_total_partida(int $fc_partida_id): float|array
    {
        if($fc_partida_id <=0 ){
            return $this->error->error(mensaje: 'Error $fc_partida_id debe ser mayor a 0',data: $fc_partida_id);
        }
        $fc_partida = (new fc_partida($this->link))->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener $fc_partida',data: $fc_partida);
        }

        $keys = array('fc_partida_cantidad','fc_partida_valor_unitario');
        $valida = $this->validacion->valida_double_mayores_0(keys: $keys,registro:  $fc_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida',data: $valida);
        }


        $cantidad = $fc_partida->fc_partida_cantidad;
        $cantidad = round($cantidad,4);

        $valor_unitario = $fc_partida->fc_partida_valor_unitario;
        $valor_unitario = round($valor_unitario,4);

        $sub_total = $cantidad * $valor_unitario;
        return round($sub_total,4);


    }

    /**
     * Suma el conjunto de partidas para descuento
     * @param array $partidas Partidas de una factura
     * @return float|array|int
     * @version 0.118.26
     */
    private function suma_descuento_partida(array $partidas): float|array|int
    {
        $descuento = 0;
        foreach ($partidas as $partida){
            if(!is_array($partida)){
                return $this->error->error(mensaje: 'Error partida debe ser un array',data: $partida);
            }

            $descuento_partida = $this->descuento_partida(fc_partida_id: $partida['fc_partida_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener descuento partida',data: $descuento_partida);
            }
            $descuento += $descuento_partida;
        }
        return $descuento;
    }

    /**
     * Obtiene el total de una factura
     * @param int $fc_factura_id Identificador de factura
     * @return float|array
     * @version 0.127.26
     */
    public function total(int $fc_factura_id): float|array
    {

        if($fc_factura_id<=0){
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }

        $sub_total = $this->sub_total(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sub total',data: $sub_total);
        }
        $descuento = $this->get_factura_descuento(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener descuento',data: $descuento);
        }

        $total = $sub_total - $descuento;

        $total = round($total,2);
        if($total<=0.0){
            return $this->error->error(mensaje: 'Error total debe ser mayor a 0',data: $total);
        }

        return $total;

    }
}