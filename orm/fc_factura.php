<?php
namespace models;
use base\orm\data_format;
use base\orm\modelo;
use DateTime;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
        $columnas = array($tabla=>false,'fc_csd'=>$tabla, 'cat_sat_forma_pago'=>$tabla,'cat_sat_metodo_pago'=>$tabla,
            'cat_sat_moneda'=>$tabla, 'com_tipo_cambio'=>$tabla, 'cat_sat_uso_cfdi'=>$tabla,
            'cat_sat_tipo_de_comprobante'=>$tabla, 'cat_sat_regimen_fiscal'=>$tabla, 'com_sucursal'=>$tabla,
            'com_cliente'=>'com_sucursal', 'dp_calle_pertenece'=>$tabla, 'dp_calle' => 'dp_calle_pertenece',
            'dp_colonia_postal'=>'dp_calle_pertenece', 'dp_colonia'=>'dp_colonia_postal', 'dp_cp'=>'dp_colonia_postal',
            'dp_municipio'=>'dp_cp', 'dp_estado'=>'dp_municipio','dp_pais'=>'dp_estado');
        $campos_obligatorios = array('folio', 'fc_csd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id',
            'cat_sat_moneda_id', 'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'cat_sat_tipo_de_comprobante_id',
            'dp_calle_pertenece_id', 'cat_sat_regimen_fiscal_id', 'com_sucursal_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());

    }

    public function alta_bd(): array|stdClass
    {

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



    private function defaults_alta_bd(array $registro, stdClass $registro_csd): array
    {

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

    private function descripcion_select_default(array $registro, stdClass $registro_csd,
                                                stdClass $registro_com_sucursal): string
    {
        $descripcion_select = $registro['folio'].' ';
        $descripcion_select .= $registro_csd->org_empresa_razon_social.' ';
        $descripcion_select .= $registro_com_sucursal->com_cliente_razon_social;
        return $descripcion_select;
    }

    public function get_factura_sub_total(int $fc_factura_id): float|array
    {
        $filtro['fc_factura.id'] = $fc_factura_id;

        $fc_partida = (new fc_partida($this->link))->filtro_and( filtro: $filtro);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas de factura',
                data: $fc_partida);
        }

        $subtotal = 0.0;


        foreach ($fc_partida->registros as $valor) {
            $subtotal += (new fc_partida($this->link))->calculo_sub_total_partida($valor['fc_partida_id']);

        }

        return $subtotal;
    }

    public function get_descuento(int $fc_factura_id): float|array
    {
        $filtro['fc_factura.id'] = $fc_factura_id;


        $fc_partida = (new fc_partida($this->link))->filtro_and( filtro: $filtro);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas de factura',
                data: $fc_partida);
        }

        $descuento = 0.0;

        foreach ($fc_partida->registros as $valor) {
            $descuento += $valor['fc_partida_descuento'];
        }
        return $descuento;
    }

    private function init_data_alta_bd(array $registro): array
    {
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


        $registro = $this->defaults_alta_bd(registro:$registro,registro_csd:  $registro_csd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar registro',data: $registro);
        }

       return $registro;
    }

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

    private function limpia_si_existe(string $key, array $registro): array
    {
        if(isset($registro[$key])){
            unset($registro[$key]);
        }
        return $registro;
    }
}