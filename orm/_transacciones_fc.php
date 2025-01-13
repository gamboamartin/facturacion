<?php

namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use config\generales;
use config\pac;
use gamboamartin\cat_sat\models\_validacion;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\documento\models\doc_extension_permitido;
use gamboamartin\errores\errores;
use gamboamartin\plugins\files;
use gamboamartin\proceso\models\pr_proceso;
use gamboamartin\xml_cfdi_4\cfdis;
use gamboamartin\xml_cfdi_4\timbra;
use PDO;
use stdClass;


class _transacciones_fc extends modelo
{

    public _etapa $modelo_etapa;
    protected _data_mail $modelo_email;
    protected _doc $modelo_documento;

    protected _relacionada $modelo_relacionada;
    protected _relacion $modelo_relacion;

    protected _data_impuestos $modelo_traslado;
    protected _data_impuestos $modelo_retencion;
    protected _partida $modelo_partida;
    protected _sellado  $modelo_sello;
    protected _notificacion  $modelo_notificacion;

    protected _uuid_ext $modelo_uuid_ext;

    public bool $valida_restriccion = true;

    protected string $key_fc_id = '';

    public function __construct(PDO $link, string $tabla, array $columnas_extra,
                                bool $valida_atributos_criticos = true)
    {
        $columnas = array($tabla => false, 'fc_csd' => $tabla, 'cat_sat_forma_pago' => $tabla,
            'cat_sat_metodo_pago' => $tabla, 'cat_sat_moneda' => $tabla, 'com_tipo_cambio' => $tabla,
            'cat_sat_uso_cfdi' => $tabla, 'cat_sat_tipo_de_comprobante' => $tabla, 'cat_sat_regimen_fiscal' => $tabla,
            'com_sucursal' => $tabla, 'com_cliente' => 'com_sucursal', 'dp_calle_pertenece' => $tabla,
            'dp_calle' => 'dp_calle_pertenece', 'dp_colonia_postal' => 'dp_calle_pertenece',
            'dp_colonia' => 'dp_colonia_postal', 'dp_cp' => 'dp_colonia_postal', 'dp_municipio' => 'dp_cp',
            'dp_estado' => 'dp_municipio', 'dp_pais' => 'dp_estado', 'org_sucursal' => 'fc_csd',
            'org_empresa' => 'org_sucursal','com_tipo_cliente'=>'com_cliente');

        $renombres['cat_sat_tipo_persona_cliente']['nombre_original'] = 'cat_sat_tipo_persona';
        $renombres['cat_sat_tipo_persona_cliente']['enlace'] = 'com_cliente';
        $renombres['cat_sat_tipo_persona_cliente']['key'] = 'id';
        $renombres['cat_sat_tipo_persona_cliente']['key_enlace'] = 'cat_sat_tipo_persona_id';

        $renombres['cat_sat_tipo_persona_empresa']['nombre_original'] = 'cat_sat_tipo_persona';
        $renombres['cat_sat_tipo_persona_empresa']['enlace'] = 'org_empresa';
        $renombres['cat_sat_tipo_persona_empresa']['key'] = 'id';
        $renombres['cat_sat_tipo_persona_empresa']['key_enlace'] = 'cat_sat_tipo_persona_id';

        $renombres['cat_sat_regimen_fiscal_cliente']['nombre_original'] = 'cat_sat_regimen_fiscal';
        $renombres['cat_sat_regimen_fiscal_cliente']['enlace'] = 'com_cliente';
        $renombres['cat_sat_regimen_fiscal_cliente']['key'] = 'id';
        $renombres['cat_sat_regimen_fiscal_cliente']['key_enlace'] = 'cat_sat_regimen_fiscal_id';

        $renombres['cat_sat_regimen_fiscal_empresa']['nombre_original'] = 'cat_sat_regimen_fiscal';
        $renombres['cat_sat_regimen_fiscal_empresa']['enlace'] = 'org_empresa';
        $renombres['cat_sat_regimen_fiscal_empresa']['key'] = 'id';
        $renombres['cat_sat_regimen_fiscal_empresa']['key_enlace'] = 'cat_sat_regimen_fiscal_id';


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

        $campos_obligatorios = array('folio', 'fc_csd_id', 'cat_sat_forma_pago_id', 'cat_sat_metodo_pago_id',
            'cat_sat_moneda_id', 'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'cat_sat_tipo_de_comprobante_id',
            'dp_calle_pertenece_id', 'cat_sat_regimen_fiscal_id', 'com_sucursal_id', 'exportacion');

        $no_duplicados = array('codigo', 'descripcion_select', 'alias', 'codigo_bis');

        $atributos_criticos[] = 'total_descuento';
        $atributos_criticos[] = 'sub_total_base';
        $atributos_criticos[] = 'sub_total';
        $atributos_criticos[] = 'total_traslados';
        $atributos_criticos[] = 'total_retenciones';
        $atributos_criticos[] = 'aplica_saldo';
        $atributos_criticos[] = 'total';
        $atributos_criticos[] = 'monto_pago_nc';
        $atributos_criticos[] = 'monto_pago_cp';
        $atributos_criticos[] = 'saldo';
        $atributos_criticos[] = 'monto_saldo_aplicado';
        $atributos_criticos[] = 'folio_fiscal';
        $atributos_criticos[] = 'etapa';
        $atributos_criticos[] = 'es_plantilla';

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas,
            campos_view: $campos_view, columnas_extra: $columnas_extra, no_duplicados: $no_duplicados,
            renombres: $renombres, atributos_criticos: $atributos_criticos,
            valida_atributos_criticos: $valida_atributos_criticos);


    }


    private function ajusta_traslado_exento(string $indice, array $registro, stdClass $value){
        if($value->tipo_factor === 'Exento'){
            $registro = $this->limpia_traslado_exento(indice: $indice,registro:  $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al limpiar registro', data: $registro);
            }

        }
        return $registro;
    }

    private function ajusta_traslados_exentos(array $registro){
        foreach ($registro['traslados'] as $indice=>$value){
            $registro = $this->ajusta_traslado_exento(indice: $indice,registro:  $registro,value:  $value);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al limpiar registro', data: $registro);
            }
        }
        return $registro;

    }


    /**
     * @return array|stdClass
     */
    public function alta_bd(): array|stdClass
    {

        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }


        $registro = $this->init_data_alta_bd(registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar registro', data: $registro);
        }

        if(!isset($registro['fecha'])){
            $registro['fecha'] = date('Y-m-d H:i:s');
        }

        $es_fecha = $this->validacion->valida_pattern(key:'fecha', txt: $registro['fecha']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fecha', data: $es_fecha);
        }
        if($es_fecha){
            $registro['fecha'] =  $registro['fecha'].' '.date('H:i:s');
        }

        $verifica = (new _validacion())->valida_metodo_pago(link: $this->link, registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al verifica registro', data: $verifica);
        }

        $this->registro = $registro;

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta accion', data: $r_alta_bd);
        }

        $registro_fc = $this->registro(registro_id: $r_alta_bd->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $r_alta_bd);
        }

        $r_alta_fc_email = (new _email())->inserta_fc_emails(key_fc_id: $this->key_fc_id,
            modelo_email: $this->modelo_email, link: $this->link, registro_fc: $registro_fc);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar correos', data: $r_alta_fc_email);
        }


        $r_alta_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $this->modelo_etapa, registro_id: $r_alta_bd->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_etapa);
        }


        return $r_alta_bd;
    }

    /**
     * Cancela una factura
     * @param int $cat_sat_motivo_cancelacion_id Motivo de cancelacion
     * @param _cancelacion $modelo_cancelacion Modelo para integrar la cancelacion
     * @param _etapa $modelo_etapa
     * @param int $registro_id Factura a cancelar
     * @return array|stdClass
     */
    public function cancela_bd(int $cat_sat_motivo_cancelacion_id, _cancelacion $modelo_cancelacion,
                               _etapa $modelo_etapa, int $registro_id): array|stdClass
    {
        $fc_cancelacion_ins[$this->key_id] = $registro_id;
        $fc_cancelacion_ins['cat_sat_motivo_cancelacion_id'] = $cat_sat_motivo_cancelacion_id;

        $r_fc_cancelacion = $modelo_cancelacion->alta_registro(registro: $fc_cancelacion_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cancelar factura',data:  $r_fc_cancelacion);
        }

        $modelo_etapa->verifica_permite_transaccion = false;

        $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
        }

        return $r_fc_cancelacion;
    }

    /**
     * Carga un descuento nuevo a un descuento previo
     * @param float $descuento Descuento previo
     * @param _partida $modelo_partida
     * @param array $partida Partida a sumar descuento
     * @return float|array
     * @version 0.117.27
     */
    private function carga_descuento(float $descuento, _partida $modelo_partida, array $partida): float|array
    {
        if ($descuento < 0.0) {
            return $this->error->error(mensaje: 'Error el descuento previo no puede ser menor a 0', data: $descuento);
        }

        $key_partida_id = $modelo_partida->key_id;

        $keys = array($key_partida_id);
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }


        $descuento_nuevo = $this->descuento_partida(modelo_partida: $modelo_partida,
            registro_partida_id: $partida[$key_partida_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener descuento', data: $descuento_nuevo);
        }
        return round($descuento + $descuento_nuevo, 2);
    }

    private function cuenta_predial(stdClass $concepto, _cuenta_predial $modelo_predial, array $partida, int $registro_id){
        if(isset($partida['com_producto_aplica_predial']) && $partida['com_producto_aplica_predial'] === 'activo'){
            $fc_cuenta_predial_numero = $this->fc_cuenta_predial_numero(modelo_predial: $modelo_predial,registro_id:  $registro_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener cuenta predial', data: $fc_cuenta_predial_numero);
            }
            $concepto->cuenta_predial = $fc_cuenta_predial_numero;
        }
        return $concepto;
    }

    protected function data_factura(array $row_entidad): array|stdClass
    {
        if(count($row_entidad) === 0){
            return $this->error->error(mensaje: 'Error la factura pasada no tiene registros', data: $row_entidad);
        }

        $keys = array('conceptos');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al valida row_entidad', data: $valida);
        }

        $comprobante = (new _comprobante())->comprobante(name_entidad: $this->tabla, row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener comprobante', data: $comprobante);
        }

        $emisor = $this->emisor(row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $emisor);
        }

        $receptor = $this->receptor(row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $receptor);
        }

        $conceptos = $row_entidad['conceptos'];

        $impuestos = (new _impuestos())->impuestos(row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener impuestos', data: $impuestos);
        }

        if(!isset($row_entidad['relacionados'])){
            $row_entidad['relacionados'] = array();
        }



        $data = new stdClass();
        $data->comprobante = $comprobante;
        $data->emisor = $emisor;
        $data->receptor = $receptor;
        $data->conceptos = $conceptos;
        $data->impuestos = $impuestos;
        $data->relacionados = $row_entidad['relacionados'];

        return $data;
    }

    /**
     * Obtiene los datos para maquetar un folio
     * @param int $fc_csd_id CSD en ejecucion
     * @return array|stdClass
     * @version 10.69.3
     */
    private function data_para_folio(int $fc_csd_id): array|stdClass
    {
        if($fc_csd_id <= 0){
            return $this->error->error(mensaje: 'Error fc_csd_id debe ser mayor a 0', data: $fc_csd_id);
        }

        $filtro['fc_csd.id'] = $fc_csd_id;
        $r_registro = $this->filtro_and(filtro: $filtro, limit: 1,order: array($this->tabla.'.folio'=>'DESC'));
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_registro', data: $r_registro);
        }

        $fc_csd_serie = $this->fc_csd_serie(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_csd_serie', data: $fc_csd_serie);
        }
        $data = new stdClass();
        $data->r_registro = $r_registro;
        $data->fc_csd_serie = $fc_csd_serie;

        return $data;
    }

    private function data_partida(_partida $modelo_partida, _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, array $partida){
        $imp_partida = $this->get_impuestos_partida(modelo_partida: $modelo_partida,
            modelo_retencion:  $modelo_retencion,modelo_traslado:  $modelo_traslado,partida:  $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener el impuestos de la partida', data: $imp_partida);
        }

        $partida = $this->integra_producto_tmp(partida: $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar producto temporal', data: $partida);
        }
        $imp_partida->partida = $partida;
        return $imp_partida;
    }

    /**
     * Inicializa los datos del emisor para alta
     * @param array $registro Registro en proceso
     * @param stdClass $registro_csd Registro de tipo CSD
     * @return array
     * @version 10.6.0
     */
    private function default_alta_emisor_data(array $registro, stdClass $registro_csd): array
    {
        $keys = array('dp_calle_pertenece_id','cat_sat_regimen_fiscal_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro_csd);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro_csd', data: $valida);
        }

        $registro['dp_calle_pertenece_id'] = $registro_csd->dp_calle_pertenece_id;
        $registro['cat_sat_regimen_fiscal_id'] = $registro_csd->cat_sat_regimen_fiscal_id;
        return $registro;
    }

    private function defaults_alta_bd(array $registro, stdClass $registro_csd): array
    {

        $keys = array('com_sucursal_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        $keys = array('serie', 'folio');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        $registro_com_sucursal = (new com_sucursal($this->link))->registro(
            registro_id: $registro['com_sucursal_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sucursal', data: $registro_com_sucursal);
        }
        if (!isset($registro['codigo'])) {
            $registro['codigo'] = $registro['serie'] . ' ' . $registro['folio'];
        }
        if (!isset($registro['codigo_bis'])) {
            $registro['codigo_bis'] = $registro['serie'] . ' ' . $registro['folio'];
        }
        if (!isset($registro['descripcion'])) {
            $descripcion = $this->descripcion_select_default(registro: $registro, registro_csd: $registro_csd,
                registro_com_sucursal: $registro_com_sucursal);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error generar descripcion', data: $descripcion);
            }
            $registro['descripcion'] = $descripcion;
        }
        if (!isset($registro['descripcion_select'])) {
            $descripcion_select = $this->descripcion_select_default(registro: $registro, registro_csd: $registro_csd,
                registro_com_sucursal: $registro_com_sucursal);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error generar descripcion', data: $descripcion_select);
            }
            $registro['descripcion_select'] = $descripcion_select;
        }
        if (!isset($registro['alias'])) {
            $registro['alias'] = $registro['descripcion_select'];
        }

        $hora = date('h:i:s');
        if (isset($registro['fecha'])) {
            $registro['fecha'] = $registro['fecha'] . ' ' . $hora;
        }
        return $registro;
    }

    /**
     */
    private function del_partidas(array $fc_partidas, _partida $modelo_partida): array
    {
        $dels = array();
        foreach ($fc_partidas as $fc_partida) {
            $del = $modelo_partida->elimina_bd(id: $fc_partida[$modelo_partida->key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
            }
            $dels[] = $del;
        }
        return $dels;
    }

    private function descripcion_select_default(array    $registro, stdClass $registro_csd,
                                                stdClass $registro_com_sucursal): string
    {
        $descripcion_select = $registro['folio'] . ' ';
        $descripcion_select .= $registro_csd->org_empresa_razon_social . ' ';
        $descripcion_select .= $registro_com_sucursal->com_cliente_razon_social;
        return $descripcion_select;
    }

    private function descuento(stdClass $data_partida, string $key_descuento){
        $descuento = 0.0;
        if(isset($data_partida->partida[$key_descuento])){
            $descuento = $data_partida->partida[$key_descuento];
        }

        $descuento = (new _comprobante())->monto_dos_dec(monto: $descuento);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar descuento', data: $descuento);
        }
        return $descuento;
    }

    /**
     * Obtiene y redondea un descuento de una partida
     * @param _partida $modelo_partida Modelo de la partida
     * @param int $registro_partida_id partida
     * @return float|array
     * @version 0.98.26
     */
    private function descuento_partida(_partida $modelo_partida, int $registro_partida_id): float|array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_partida_id debe ser mayor a 0', data: $registro_partida_id);
        }
        $partida = $modelo_partida->registro(registro_id: $registro_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $partida);
        }

        $key_out = $modelo_partida->tabla.'_descuento';

        $descuento = $partida->$key_out;

        return round($descuento, 4);


    }

    final public function doc_tipo_documento_id(string $extension)
    {
        $filtro['doc_extension.descripcion'] = $extension;
        $existe_extension = (new doc_extension_permitido($this->link))->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension del documento', data: $existe_extension);
        }
        if (!$existe_extension) {
            return $this->error->error(mensaje: "Error la extension: $extension no esta permitida", data: $existe_extension);
        }

        $r_doc_extension_permitido = (new doc_extension_permitido($this->link))->filtro_and(filtro: $filtro, limit: 1);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension del documento', data: $r_doc_extension_permitido);
        }
        return $r_doc_extension_permitido->registros[0]['doc_tipo_documento_id'];
    }

    final public function duplica(_partida $modelo_partida,_data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, int $registro_id){
        $modelo_partida->modelo_traslado = $modelo_traslado;
        $modelo_partida->modelo_retencion = $modelo_retencion;

        $row_entidad_id = (new _duplica())->inserta_row_entidad(modelo_entidad: $this, registro_id: $registro_id);
        if (errores::$error) {
            $this->link->rollBack();
            return $this->error->error(mensaje: 'Error al insertar registro', data: $row_entidad_id);
        }

        $r_alta_bd_part = (new _duplica())->genera_partidas(modelo_entidad: $this, modelo_partida: $modelo_partida,
            registro_id: $registro_id, row_entidad_id: $row_entidad_id);
        if (errores::$error) {

            return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_bd_part);
        }
        return $row_entidad_id;
    }

    public function elimina_bd(int $id): array|stdClass
    {


        $this->modelo_documento->valida_restriccion = $this->valida_restriccion;
        $this->modelo_sello->valida_restriccion = $this->valida_restriccion;
        $this->modelo_relacionada->valida_restriccion = $this->valida_restriccion;

        if($this->valida_restriccion) {
            $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa, registro_id: $id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }

        $del = $this->elimina_partidas(modelo_etapa: $this->modelo_etapa, modelo_partida: $this->modelo_partida,
            name_entidad: $this->tabla, registro_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
        }

        $filtro = array();
        $filtro[$this->key_filtro_id] = $id;

        $r_fc_factura_documento = $this->modelo_documento->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_factura_documento);
        }
        $r_fc_email = $this->modelo_email->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_email);
        }
        $r_fc_factura_etapa = $this->modelo_etapa->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_factura_etapa);
        }


        $r_cfdi_sellado = $this->modelo_sello->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_cfdi_sellado);
        }
        $r_fc_factura_relacionada = $this->modelo_relacionada->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_factura_relacionada);
        }
        $r_fc_relacion = $this->modelo_relacion->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_relacion);
        }

        if($this->tabla === 'fc_factura') {
            $r_fc_nc_rel = (new fc_nc_rel(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_nc_rel);
            }
        }

        $r_fc_notificacion = $this->modelo_notificacion->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_notificacion);
        }


        $r_elimina_factura = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar factura', data: $r_elimina_factura);
        }
        return $r_elimina_factura;
    }

    /**
     */
    private function elimina_partidas(_etapa $modelo_etapa, _partida $modelo_partida, string $name_entidad,
                                      int $registro_id): array
    {
        $modelo_partida->valida_restriccion = $this->valida_restriccion;

        if($this->valida_restriccion){
            $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $modelo_etapa, registro_id: $registro_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        $fc_partidas = $this->get_partidas(name_entidad: $name_entidad, modelo_partida: $modelo_partida,
            registro_entidad_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $fc_partidas);
        }

        $del = $this->del_partidas(fc_partidas: $fc_partidas, modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
        }
        return $del;
    }

    /**
     * Obtiene el emisor de una factura
     * @param array $row_entidad Factura a integrar
     * @return array
     * @version 10.25.0
     */
    private function emisor(array $row_entidad): array
    {
        $keys = array('org_empresa_rfc','org_empresa_razon_social','cat_sat_regimen_fiscal_codigo');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar $row_entidad',data:  $valida);
        }

        $valida = $this->validacion->valida_rfc(key: 'org_empresa_rfc', registro: $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar $row_entidad',data:  $valida);
        }


        $emisor = array();
        $emisor['rfc'] = $row_entidad['org_empresa_rfc'];
        $emisor['nombre'] = $row_entidad['org_empresa_razon_social'];
        $emisor['regimen_fiscal'] = $row_entidad['cat_sat_regimen_fiscal_codigo'];
        return $emisor;
    }

    final public function envia_factura(_notificacion $modelo_notificacion, int $registro_id){
        $notifica = (new _email())->envia_factura(key_filter_entidad_id: $this->key_filtro_id, link: $this->link,
            modelo_notificacion: $modelo_notificacion, registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al enviar notificacion',data:  $notifica);
        }
        return $notifica;
    }


    /**
     * Obtiene las etapas de una factura
     * @param _etapa $modelo_etapa Modelo de tipo etapa
     * @param int $registro_id Factura o complemento a verificar etapas
     * @return array
     */
    private function etapas(_etapa $modelo_etapa, int $registro_id): array
    {
        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }
        $key_filtro_id = trim($this->key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(mensaje: 'Error key_filtro_id debe estar inicializado en el modelo',
                data: $key_filtro_id);
        }
        $filtro[$this->key_filtro_id] =  $registro_id;

        $r_etapa = $modelo_etapa->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_etapa', data: $r_etapa);
        }
        return $r_etapa->registros;
    }


    /**
     * Obtiene la serie de un CSD
     * @param int $fc_csd_id CSD de obtencion de serie
     * @return array|string
     * @version 10.50.3
     */
    private function fc_csd_serie(int $fc_csd_id): array|string
    {
        if($fc_csd_id <= 0){
            return $this->error->error(mensaje: 'Error fc_csd_id debe ser mayor a 0', data: $fc_csd_id);
        }
        $columnas[] = 'fc_csd_serie';
        $fc_csd = (new fc_csd(link: $this->link))->registro(registro_id: $fc_csd_id, columnas: $columnas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener csd', data: $fc_csd);
        }

        return trim($fc_csd['fc_csd_serie']);
    }

    private function fc_cuenta_predial_numero(_cuenta_predial $modelo_predial, int $registro_id){
        $r_fc_cuenta_predial = $this->r_fc_cuenta_predial(modelo_predial: $modelo_predial,
            registro_id:  $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener cuenta predial', data: $r_fc_cuenta_predial);
        }
        $key_cuenta_predial_descripcion = $modelo_predial->tabla.'_descripcion';
        return $r_fc_cuenta_predial->registros[0][$key_cuenta_predial_descripcion];
    }



    private function folio_str(string $number_folio): string
    {
        $long_nf = strlen($number_folio);
        $n_ceros = 6;
        $i = $long_nf;
        $folio_str = '';

        while($i<$n_ceros){
            $folio_str.='0';
            $i++;
        }

        $folio_str.=$number_folio;
        return $folio_str;
    }

    final protected function from_impuesto(string $entidad_partida, string $tipo_impuesto): string
    {
        $key_id = $entidad_partida.'_id';
        $base = $entidad_partida.'_operacion';
        return "$entidad_partida AS $base LEFT JOIN $tipo_impuesto ON $tipo_impuesto.$key_id = $base.id";
    }


    final public function genera_ruta_archivo_tmp(): array|string
    {
        $ruta_archivos = $this->ruta_archivos();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar ruta de archivos', data: $ruta_archivos);
        }

        $ruta_archivos_tmp = $this->ruta_archivos_tmp(ruta_archivos: $ruta_archivos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar ruta de archivos', data: $ruta_archivos_tmp);
        }
        return $ruta_archivos_tmp;
    }

    public function genera_xml(_doc $modelo_documento, _etapa $modelo_etapa, _partida $modelo_partida,
                               _cuenta_predial$modelo_predial, _relacion $modelo_relacion,
                               _relacionada $modelo_relacionada, _data_impuestos $modelo_retencion,
                               _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext, int $registro_id, string $tipo): array|stdClass
    {

        $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $factura = $this->get_factura(modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $factura);
        }


        $data_factura = $this->data_factura(row_entidad: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos de la factura', data: $data_factura);
        }

        if(!isset($data_factura->Complemento)){
            $data_factura->Complemento = array();
        }


        if($tipo === 'xml') {
            $ingreso = (new cfdis())->ingreso(comprobante: $data_factura->comprobante, conceptos: $data_factura->conceptos,
                emisor: $data_factura->emisor, impuestos: $data_factura->impuestos, receptor: $data_factura->receptor,
                relacionados: $data_factura->relacionados);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar xml', data: $ingreso);
            }
        }
        else{
            $ingreso = (new cfdis())->ingreso_json(comprobante: $data_factura->comprobante, conceptos: $data_factura->conceptos,
                emisor: $data_factura->emisor, impuestos: $data_factura->impuestos, receptor: $data_factura->receptor,
                complemento: $data_factura->Complemento, relacionados: $data_factura->relacionados);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar xml', data: $ingreso);
            }

        }


        $ruta_archivos_tmp = $this->genera_ruta_archivo_tmp();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener ruta de archivos', data: $ruta_archivos_tmp);
        }

        $documento = array();
        $file = array();
        $file_xml_st = $ruta_archivos_tmp . '/' . $this->registro_id . '.st.xml';
        file_put_contents($file_xml_st, $ingreso);

        $filtro = array();
        $filtro[$this->key_filtro_id] = $this->registro_id;
        $filtro['doc_extension.codigo'] = 'xml';

        $existe = $modelo_documento->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si existe documento', data: $existe);
        }

        $doc_tipo_documento_id = $this->doc_tipo_documento_id(extension: "xml");
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension del documento', data: $doc_tipo_documento_id);
        }

        if (!$existe) {

            $file['name'] = $file_xml_st;
            $file['tmp_name'] = $file_xml_st;

            $documento['doc_tipo_documento_id'] = $doc_tipo_documento_id;
            $documento['descripcion'] = $ruta_archivos_tmp;

            $documento = (new doc_documento(link: $this->link))->alta_documento(registro: $documento, file: $file);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al guardar xml', data: $documento);
            }

            $fc_factura_documento = array();
            $fc_factura_documento[$this->key_id] = $this->registro_id;
            $fc_factura_documento['doc_documento_id'] = $documento->registro_id;

            $fc_factura_documento = $modelo_documento->alta_registro(registro: $fc_factura_documento);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta factura documento', data: $fc_factura_documento);
            }
        }
        else {

            $filtro = array();
            $filtro[$this->key_filtro_id] = $this->registro_id;
            $filtro['doc_extension.descripcion'] = 'xml';

            $r_fc_factura_documento = $modelo_documento->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener factura documento', data: $r_fc_factura_documento);
            }

            if ($r_fc_factura_documento->n_registros > 1) {
                return $this->error->error(mensaje: 'Error solo debe existir una factura_documento', data: $r_fc_factura_documento);
            }
            if ($r_fc_factura_documento->n_registros === 0) {
                return $this->error->error(mensaje: 'Error  debe existir al menos una factura_documento', data: $r_fc_factura_documento);
            }
            $fc_factura_documento = $r_fc_factura_documento->registros[0];

            $doc_documento_id = $fc_factura_documento['doc_documento_id'];

            $registro['descripcion'] = $ruta_archivos_tmp;
            $registro['doc_tipo_documento_id'] = $doc_tipo_documento_id;
            $_FILES['name'] = $file_xml_st;
            $_FILES['tmp_name'] = $file_xml_st;

            $documento = (new doc_documento(link: $this->link))->modifica_bd(registro: $registro, id: $doc_documento_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error  al modificar documento', data: $documento);
            }

            $documento->registro = (new doc_documento(link: $this->link))->registro(registro_id: $documento->registro_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error  al obtener documento', data: $documento);
            }
        }

        $rutas = new stdClass();
        $rutas->file_xml_st = $file_xml_st;
        $rutas->doc_documento_ruta_absoluta = $documento->registro['doc_documento_ruta_absoluta'];

        return $rutas;
    }

    /**
     * Obtiene los datos para relacionar a factura
     * @param _relacion $modelo_relacion Modelo base de relacion
     * @param _relacionada $modelo_relacionada Modelo de detalle de relacion
     * @param _uuid_ext $modelo_uuid_ext
     * @param int $registro_entidad_id Registro base para integrar relacion
     * @return array
     */
    final public function  get_data_relaciones(_relacion $modelo_relacion, _relacionada $modelo_relacionada, _uuid_ext $modelo_uuid_ext,
                                               int $registro_entidad_id): array
    {


        $relaciones = $modelo_relacion->relaciones(modelo_entidad: $this,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relaciones', data: $relaciones);
        }

        foreach ($relaciones as $indice=>$fc_relacion){
            $relacionadas = $modelo_relacion->facturas_relacionadas(
                modelo_relacionada: $modelo_relacionada, row_relacion: $fc_relacion);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener relacionadas', data: $relacionadas);
            }
            $relaciones[$indice]['fc_facturas_relacionadas'] = $relacionadas;
        }

        foreach ($relaciones as $indice=>$fc_relacion){

            $filtro[$modelo_relacion->key_filtro_id] = $fc_relacion[$modelo_relacion->key_id];
            $r_fc_uuid_fc = $modelo_uuid_ext->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener r_fc_uuid_fc', data: $r_fc_uuid_fc);
            }

            $relaciones[$indice]['fc_facturas_externas_relacionadas'] = $r_fc_uuid_fc->registros;
        }

        if($modelo_relacion->tabla === 'fc_relacion_nc') {
            foreach ($relaciones as $indice => $fc_relacion) {

                $cat_sat_tipo_relacion_codigo = trim($fc_relacion['cat_sat_tipo_relacion_codigo']);
                if ($cat_sat_tipo_relacion_codigo === '') {
                    return $this->error->error(mensaje: 'Error cat_sat_tipo_relacion_codigo esta vacio',
                        data: $cat_sat_tipo_relacion_codigo);
                }

                $filtro[$modelo_relacion->key_filtro_id] = $fc_relacion[$modelo_relacion->key_id];
                $r_fc_nc = (new fc_nc_rel(link: $this->link))->filtro_and(filtro: $filtro);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al obtener relacion', data: $r_fc_nc);
                }

                $relaciones[$indice]['fc_facturas_relacionadas_nc'] = $r_fc_nc->registros;
            }
        }


        return $relaciones;

    }

    private function get_datos_xml(string $ruta_xml = ""): array
    {
        $xml = simplexml_load_file($ruta_xml);
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('c', $ns['cfdi']);
        $xml->registerXPathNamespace('t', $ns['tfd']);

        $xml_data = array();
        $xml_data['cfdi_comprobante'] = array();
        $xml_data['cfdi_emisor'] = array();
        $xml_data['cfdi_receptor'] = array();
        $xml_data['cfdi_conceptos'] = array();
        $xml_data['tfd'] = array();

        $nodos = array();
        $nodos[] = '//cfdi:Comprobante';
        $nodos[] = '//cfdi:Comprobante//cfdi:Emisor';
        $nodos[] = '//cfdi:Comprobante//cfdi:Receptor';
        $nodos[] = '//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto';
        $nodos[] = '//t:TimbreFiscalDigital';

        foreach ($nodos as $key => $nodo) {
            foreach ($xml->xpath($nodo) as $value) {
                $data = (array)$value->attributes();
                $data = $data['@attributes'];
                $xml_data[array_keys($xml_data)[$key]] = $data;
            }
        }
        return $xml_data;
    }

    /**
     *
     * @param _partida $modelo_partida Modelo tipo partida
     * @param _cuenta_predial $modelo_predial
     * @param _relacion $modelo_relacion
     * @param _relacionada $modelo_relacionada
     * @param _data_impuestos $modelo_retencion
     * @param _data_impuestos $modelo_traslado
     * @param _uuid_ext $modelo_uuid_ext
     * @param int $registro_id
     * @return array|stdClass|int
     */
    final public function get_factura(_partida $modelo_partida,_cuenta_predial $modelo_predial,
                                      _relacion $modelo_relacion, _relacionada $modelo_relacionada,
                                      _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado,
                                      _uuid_ext $modelo_uuid_ext, int $registro_id): array|stdClass|int
    {
        $hijo = array();
        $hijo[$modelo_partida->tabla]['filtros'] = array();
        $hijo[$modelo_partida->tabla]['filtros_con_valor'] = array($this->key_filtro_id => $registro_id);
        $hijo[$modelo_partida->tabla]['nombre_estructura'] = 'partidas';
        $hijo[$modelo_partida->tabla]['namespace_model'] = 'gamboamartin\\facturacion\\models';
        $registro = $this->registro(registro_id: $registro_id, hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $registro);
        }

        $relacionados = $modelo_relacion->get_relaciones(modelo_entidad: $this,
            modelo_relacionada: $modelo_relacionada, modelo_uuid_ext: $modelo_uuid_ext,
            registro_entidad_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relaciones', data: $relacionados);
        }



        $conceptos = array();

        $total_impuestos_trasladados = 0.0;
        $total_impuestos_retenidos = 0.0;

        $trs_global= array();
        $ret_global= array();
        foreach ($registro['partidas'] as $key => $partida) {


            $data_partida = $this->data_partida(modelo_partida: $modelo_partida,modelo_retencion:  $modelo_retencion,
                modelo_traslado:  $modelo_traslado,partida:  $partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar producto temporal', data: $partida);
            }

            $registro['partidas'][$key]['traslados'] = $data_partida->traslados->registros;
            $registro['partidas'][$key]['retenidos'] = $data_partida->retenidos->registros;

            $key_cantidad = $modelo_partida->tabla.'_cantidad';
            $key_descripcion = $modelo_partida->tabla.'_descripcion';
            $key_valor_unitario = $modelo_partida->tabla.'_valor_unitario';
            $key_importe = $modelo_partida->tabla.'_sub_total_base';
            $key_descuento = $modelo_partida->tabla.'_descuento';

            $concepto = new stdClass();
            $concepto->clave_prod_serv = $data_partida->partida['cat_sat_producto_codigo'];
            $concepto->cantidad = $data_partida->partida[$key_cantidad];
            $concepto->clave_unidad = $data_partida->partida['cat_sat_unidad_codigo'];
            $concepto->descripcion = $data_partida->partida[$key_descripcion];
            $concepto->valor_unitario = number_format($data_partida->partida[$key_valor_unitario], 2);
            $concepto->importe = number_format($data_partida->partida[$key_importe], 2);
            $concepto->objeto_imp = $data_partida->partida['cat_sat_obj_imp_codigo'];
            $concepto->no_identificacion = $data_partida->partida['com_producto_codigo'];
            $concepto->unidad = $data_partida->partida['cat_sat_unidad_descripcion'];



            $descuento = $this->descuento(data_partida: $data_partida,key_descuento:  $key_descuento);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar descuento', data: $descuento);
            }

            $concepto->descuento = $descuento;

            $concepto = $this->inicializa_impuestos_de_concepto(concepto: $concepto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar impuestos', data: $concepto);
            }

            $key_traslado_importe = $modelo_traslado->tabla.'_importe';

            $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->traslados,
                key_importe_impuesto: $key_traslado_importe,name_tabla_partida: $modelo_partida->tabla);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar traslados', data: $impuestos);
            }

            $trs_global = (new _impuestos())->impuestos_globales(impuestos: $data_partida->traslados, global_imp: $trs_global,
                key_importe: $key_traslado_importe, name_tabla_partida: $modelo_partida->tabla);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $trs_global);
            }

            $key_retenido_importe = $modelo_retencion->tabla.'_importe';
            $ret_global = (new _impuestos())->impuestos_globales(impuestos: $data_partida->retenidos, global_imp: $ret_global,
                key_importe: $key_retenido_importe, name_tabla_partida: $modelo_partida->tabla);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $ret_global);
            }

            $concepto->impuestos[0]->traslados = $impuestos;

            $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->retenidos,
                key_importe_impuesto: $key_retenido_importe, name_tabla_partida: $modelo_partida->tabla);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar retenciones', data: $impuestos);
            }

            $concepto->impuestos[0]->retenciones = $impuestos;

            $concepto = $this->cuenta_predial(concepto: $concepto,modelo_predial:  $modelo_predial,
                partida:  $data_partida->partida,registro_id:  $registro_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar cuenta predial', data: $concepto);
            }


            $conceptos[] = $concepto;

            $key_importe_total_traslado = $modelo_partida->tabla.'_total_traslados';
            $key_importe_total_retenido = $modelo_partida->tabla.'_total_retenciones';

            $total_impuestos_trasladados += ($data_partida->partida[$key_importe_total_traslado]);
            $total_impuestos_retenidos += ($data_partida->partida[$key_importe_total_retenido]);

        }

        $key_total = $this->tabla.'_total';
        $key_sub_total = $this->tabla.'_sub_total';
        $key_sub_total_base = $this->tabla.'_sub_total_base';
        $key_descuento = $this->tabla.'_total_descuento';

        $registro['total_descuento'] = number_format($registro[$key_descuento], 2);
        $registro[$key_sub_total_base] = number_format($registro[$key_sub_total_base], 2);

        $registro[$key_total] = round($registro[$key_sub_total]
            + $total_impuestos_trasladados - $total_impuestos_retenidos,2);
        $registro['traslados'] = $trs_global;
        $registro['retenidos'] = $ret_global;


        $registro = $this->ajusta_traslados_exentos(registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar registro', data: $registro);
        }

        $registro['conceptos'] = $conceptos;

        $registro['total_impuestos_trasladados'] = number_format($total_impuestos_trasladados, 2);
        $registro['total_impuestos_retenidos'] = number_format($total_impuestos_retenidos, 2);
        $registro['relacionados'] = $relacionados;

        return $registro;
    }

    /**
     * Obtiene el total de descuento de una factura
     * @param int $registro_id Identificador de factura
     * @return float|array
     * @version 6.10.0
     */
    final public function get_factura_descuento(int $registro_id): float|array
    {
        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $key_descuento = $this->tabla.'_total_descuento';

        $fc_factura = $this->registro(registro_id: $registro_id, columnas: array($key_descuento),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        return round($fc_factura->$key_descuento,2);

    }

    /**
     * Obtiene los impuestos retenidos de una partida
     * Cambiar por campo de impuestos retenidos
     * @param int $registro_id
     * @return float|array
     */
    final public function get_factura_imp_retenidos(int $registro_id): float|array
    {

        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $key_retenciones = $this->tabla.'_total_retenciones';

        $fc_factura = $this->registro(registro_id: $registro_id, columnas: array($key_retenciones),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        return round($fc_factura->$key_retenciones,2);


    }

    /**
     * Calcula los impuestos trasladados de una factura
     * @param int $registro_id Registro en proceso
     * @return float|array
     * @version 4.14.0
     */
    public function get_factura_imp_trasladados(int $registro_id): float|array
    {
        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $key_traslados = $this->tabla.'_total_traslados';

        $fc_factura = $this->registro(registro_id: $registro_id, columnas: array($key_traslados),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        return round($fc_factura->$key_traslados,2);


    }

    /**
     * Obtiene el subtotal de una factura
     * @param int $registro_id Factura o complemento de pago o NC a obtener info
     * @return float|array
     * @version 6.7.0
     */
    final public function get_factura_sub_total(int $registro_id): float|array
    {
        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }
        $key = $this->tabla.'_sub_total';
        $fc_factura = $this->registro(registro_id: $registro_id, columnas: array($key),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }


        return round($fc_factura->$key,2);


    }

    /**
     * Obtiene el total de una factura
     * @param int $fc_factura_id Factura a obtener total
     * @return float|array
     */
    final public function get_factura_total(int $fc_factura_id): float|array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }
        $key_total = $this->tabla.'_total';
        $fc_factura = $this->registro(registro_id: $fc_factura_id, columnas: array($key_total),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }
        return round($fc_factura->$key_total,2);
    }

    private function get_impuestos_partida(_partida $modelo_partida,_data_impuestos $modelo_retencion,
                                           _data_impuestos $modelo_traslado, array $partida){

        $traslados = $modelo_traslado->get_data_rows(name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener el traslados de la partida', data: $traslados);
        }

        $retenidos = $modelo_retencion->get_data_rows(name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener el retenidos de la partida', data: $retenidos);
        }
        $data = new stdClass();
        $data->traslados = $traslados;
        $data->retenidos = $retenidos;
        return $data;
    }

    /**
     * Obtiene las partidas de una factura
     * @param string $name_entidad
     * @param _partida $modelo_partida
     * @param int $registro_entidad_id Factura a validar
     * @return array
     * @version 0.83.26
     */
    private function get_partidas(string $name_entidad, _partida $modelo_partida, int $registro_entidad_id): array
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0', data: $registro_entidad_id);
        }

        $filtro[$name_entidad.'.id'] = $registro_entidad_id;

        $r_fc_partida_cp = $modelo_partida->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida_cp);
        }

        return $r_fc_partida_cp->registros;
    }

    private function guarda_documento(string $directorio, string $extension, string $contenido,
                                      _doc $modelo_documento, int $registro_id): array|stdClass
    {
        $ruta_archivos = $this->ruta_archivos(directorio: $directorio);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener ruta de archivos', data: $ruta_archivos);
        }

        $ruta_archivo = "$ruta_archivos/$this->registro_id.$extension";

        $guarda_archivo = (new files())->guarda_archivo_fisico(contenido_file: $contenido, ruta_file: $ruta_archivo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar archivo', data: $guarda_archivo);
        }

        $tipo_documento = $this->doc_tipo_documento_id(extension: $extension);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar extension del documento', data: $tipo_documento);
        }

        $file['name'] = $guarda_archivo;
        $file['tmp_name'] = $guarda_archivo;

        $documento['doc_tipo_documento_id'] = $tipo_documento;
        $documento['descripcion'] = "$this->registro_id.$extension";
        $documento['descripcion_select'] = "$this->registro_id.$extension";

        $documento = (new doc_documento(link: $this->link))->alta_documento(registro: $documento, file: $file);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar jpg', data: $documento);
        }

        $registro[$this->key_id] = $registro_id;
        $registro['doc_documento_id'] = $documento->registro_id;
        $factura_documento = $modelo_documento->alta_registro(registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar relacion factura con documento', data: $factura_documento);
        }

        return $documento;
    }

    private function inicializa_impuestos_de_concepto(stdClass $concepto): stdClass
    {
        $concepto->impuestos = array();
        $concepto->impuestos[0] = new stdClass();
        $concepto->impuestos[0]->traslados = array();
        $concepto->impuestos[0]->retenciones = array();
        return $concepto;

    }

    /**
     * Inicializa los datos de un registro
     * @param array $registro
     * @return array
     */
    final protected function init_data_alta_bd(array $registro): array
    {
        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }
        $registro_csd = (new fc_csd($this->link))->registro(registro_id: $registro['fc_csd_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc csd', data: $registro_csd);
        }


        $registro = $this->limpia_alta_factura(registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar keys', data: $registro);
        }


        $registro = $this->default_alta_emisor_data(registro: $registro, registro_csd: $registro_csd);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar keys', data: $registro);
        }

        $keys = array('com_sucursal_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }


        if(!isset($registro['folio'])){
            $folio = $this->ultimo_folio(fc_csd_id: $registro['fc_csd_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener csd', data: $folio);
            }
            $registro['folio'] = $folio;
        }

        if(!isset($registro['serie'])){
            $serie = $registro_csd->fc_csd_serie;

            $registro['serie'] = $serie;
        }

        $keys = array('serie', 'folio');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        $registro = $this->defaults_alta_bd(registro: $registro, registro_csd: $registro_csd);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar registro', data: $registro);
        }


        return $registro;
    }

    final public function inserta_notificacion(_doc $modelo_doc, _data_mail $modelo_email,
                                               _notificacion $modelo_notificacion, int $registro_id){

        $notificaciones = (new _email())->crear_notificaciones(modelo_doc: $modelo_doc,
            modelo_email: $modelo_email, modelo_entidad: $this, modelo_notificacion: $modelo_notificacion,
            link: $this->link, registro_entidad_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar notificaciones', data: $notificaciones);
        }
        return $notificaciones;
    }



    /**
     * Ajusta la fecha dependiendo la entrada de fecha
     * @param array $registro Registro en proceso de modificacion
     * @return array
     * @version 10.31.0
     *
     */
    private function integra_fecha(array $registro): array
    {
        if(isset($registro['fecha'])){
            $es_fecha = $this->validacion->valida_pattern(key:'fecha', txt: $registro['fecha']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar fecha', data: $es_fecha);
            }
            if($es_fecha){
                $registro['fecha'] =  $registro['fecha'].' '.date('H:i:s');
            }
        }
        return $registro;
    }

    /**
     * POR ELIMINAR FUNCION Y OBTENER DE COM PRODUCTO
     * @param array $partida
     * @return array
     */
    private function integra_producto_tmp(array $partida): array
    {
        $partida['cat_sat_producto_codigo'] = $partida['com_producto_codigo_sat'];
        return $partida;

    }

    /**
     * Limpia los parametros de una factura
     * @param array $registro registro en proceso
     * @return array
     */
    private function limpia_alta_factura(array $registro): array
    {

        $keys = array('descuento', 'subtotal', 'total', 'impuestos_trasladados', 'impuestos_retenidos');
        foreach ($keys as $key) {
            $registro = $this->limpia_si_existe(key: $key, registro: $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al limpiar key', data: $registro);
            }
        }

        return $registro;
    }


    /**
     * Limpia un key de un registro si es que existe
     * @param string $key Key a limpiar
     * @param array $registro Registro para aplicacion de limpieza
     * @return array
     */
    private function limpia_si_existe(string $key, array $registro): array
    {
        $key = trim($key);
        if ($key === '') {
            return $this->error->error(mensaje: 'Error key esta vacio', data: $key);
        }
        if (isset($registro[$key])) {
            unset($registro[$key]);
        }
        return $registro;
    }


    /**
     * POR DOCUMENTAR EN WIKI
     * Limpia los campos `tasa_o_cuota` y `importe` del elemento de array `traslados`
     * con el índice proporcionado en `$indice`.
     *
     * @param string $indice Índice del elemento del array `traslados` que será procesado.
     * @param array $registro Array que contiene los elementos `traslados`.
     *
     * @return array Retorna el array `$registro` modificado.
     *
     * @throws errores Si `$indice` es menor a 0, se lanza una excepción "Error indice debe ser mayor igual a 0".
     * @version 23.2.0
     */
    private function limpia_traslado_exento(string $indice, array $registro): array
    {
        if($indice<0){
            return $this->error->error(mensaje: 'Error indice debe ser mayor igual a 0', data: $indice);
        }
        unset($registro['traslados'][$indice]->tasa_o_cuota);
        unset($registro['traslados'][$indice]->importe);
        return $registro;
    }

    /**
     * Modifica un registro de tipo factura nota de credito o complemento de pago
     * @param array $registro Registro en proceso
     * @param int $id Identificador
     * @param bool $reactiva Valida si esta inactivo
     * @param bool $verifica_permite_transaccion Si verifica la transaccion no se ejecutara si esta esta timbrada
     * @return array|stdClass
     */
    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                bool $verifica_permite_transaccion = true): array|stdClass
    {

        if($id <= 0){
            return $this->error->error(mensaje: 'Error id debe ser mayor a 0', data: $id);
        }

        if($verifica_permite_transaccion) {
            $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
                registro_id: $id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        $registro = $this->integra_fecha(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar fecha', data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva);
        // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }

        $registro = $this->registro(registro_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $registro);
        }

        $verifica = (new _validacion())->valida_metodo_pago(link: $this->link, registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al verifica registro', data: $verifica);
        }


        return $r_modifica_bd;
    }

    final public function modifica_etapa(string $etapa_descripcion, int $registro_id)
    {
        $data_upd['etapa'] = $etapa_descripcion;
        $upd = parent::modifica_bd(registro: $data_upd, id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualiza etapa registro', data: $upd);
        }
        return $upd;
    }


    /**
     * Obtiene el numero de folio de una factura
     * @param string $fc_csd_serie Serie del CSD
     * @param stdClass $r_registro Registro previo
     * @return int
     */
    private function number_folio(string $fc_csd_serie, stdClass $r_registro): int
    {
        $number_folio = 1;
        if((int)$r_registro->n_registros > 0){
            $fc_factura = $r_registro->registros[0];

            $fc_folio = $fc_factura[$this->tabla.'_folio'];
            $data_explode = $fc_csd_serie.'-';
            $fc_folio_explode = explode($data_explode, $fc_folio);
            if(isset($fc_folio_explode[1])){
                if(is_numeric($fc_folio_explode[1])){
                    $number_folio = (int)$fc_folio_explode[1] + 1;
                }
            }
        }
        return $number_folio;
    }

    /**
     * Obtiene las partidas de una entidad de tipo cfdi
     * @param _partida $modelo_partida Modelo a obtener las partidas
     * @param int $registro_id Registro en proceso
     * @return array
     * @version 10.125.4
     */
    final public function partidas_base(_partida $modelo_partida, int $registro_id): array
    {
        $this->key_filtro_id = trim($this->key_filtro_id);
        if($this->key_filtro_id === ''){
            return $this->error->error(mensaje: 'Error key_filtro_id esta vacio', data: $this->key_filtro_id);
        }
        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }
        $filtro[$this->key_filtro_id] = $registro_id;
        $r_rows_partidas = $modelo_partida->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener rows_partidas', data: $r_rows_partidas);
        }
        return $r_rows_partidas->registros;
    }

    /**
     * Verifica si se permite o no una transaccion dependiendo la etapa en la que se encuentre la entidad
     * @param _etapa $modelo_etapa Modelo de etapa
     * @param int $registro_id Registro en proceso
     * @return array|bool
     */
    private function permite_transaccion(_etapa $modelo_etapa, int $registro_id): bool|array
    {
        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }
        $etapas = $this->etapas(modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_factura_etapas', data: $etapas);
        }
        $permite_transaccion = $this->valida_permite_transaccion(etapas: $etapas);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener permite_transaccion', data: $permite_transaccion);
        }
        return $permite_transaccion;
    }

    private function r_fc_cuenta_predial(_cuenta_predial $modelo_predial, int $registro_id){
        $r_fc_cuenta_predial = $modelo_predial->filtro_and(filtro: array($this->key_filtro_id=>$registro_id));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener cuenta predial', data: $r_fc_cuenta_predial);
        }
        if($r_fc_cuenta_predial->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe predial asignado', data: $r_fc_cuenta_predial);
        }
        if($r_fc_cuenta_predial->n_registros > 1){
            return $this->error->error(mensaje: 'Error de integridad en predial', data: $r_fc_cuenta_predial);
        }
        return $r_fc_cuenta_predial;
    }


    private function receptor(array $row_entidad): array
    {

        $keys = array('com_sucursal_id','cat_sat_uso_cfdi_codigo');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar $row_entidad', data: $valida);
        }

        $com_sucursal = (new com_sucursal(link: $this->link))->registro(registro_id: $row_entidad['com_sucursal_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener com_sucursal', data: $com_sucursal);
        }

        $domicilio_fiscal_receptor = $com_sucursal['com_sucursal_cp'];

        /*
        $com_cliente_id = $com_sucursal['com_cliente_id'];
        $filtro['com_tmp_cte_dp.com_cliente_id'] = $com_cliente_id;

        $existe_tmp_dp = (new com_tmp_cte_dp(link: $this->link))->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si existe', data: $existe_tmp_dp);
        }

        if($existe_tmp_dp){
            $r_tmp_dp = (new com_tmp_cte_dp(link: $this->link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar si existe', data: $r_tmp_dp);
            }
            $domicilio_fiscal_receptor = $r_tmp_dp->registros[0]['com_tmp_cte_dp_dp_cp'];


        }*/

        $receptor = array();
        $receptor['rfc'] = $com_sucursal['com_cliente_rfc'];
        $receptor['nombre'] = $com_sucursal['com_cliente_razon_social'];
        $receptor['domicilio_fiscal_receptor'] = $domicilio_fiscal_receptor; //'91779'; dp_cp_descripcion de com_sucursal.dp_calle_pertenece hacia cp
        $receptor['regimen_fiscal_receptor'] = $com_sucursal['cat_sat_regimen_fiscal_codigo'];
        $receptor['uso_cfdi'] = $row_entidad['cat_sat_uso_cfdi_codigo'];
        return $receptor;
    }





    final public function ruta_archivos(string $directorio = ""): array|string
    {
        $ruta_archivos = (new generales())->path_base . "archivos/$directorio";
        if (!file_exists($ruta_archivos)) {
            mkdir($ruta_archivos, 0777, true);
        }
        if (!file_exists($ruta_archivos)) {
            return $this->error->error(mensaje: "Error no existe $ruta_archivos", data: $ruta_archivos);
        }
        return $ruta_archivos;
    }

    private function ruta_archivos_tmp(string $ruta_archivos): array|string
    {
        $ruta_archivos_tmp = $ruta_archivos . '/tmp';

        if (!file_exists($ruta_archivos_tmp)) {
            mkdir($ruta_archivos_tmp, 0777, true);
        }
        if (!file_exists($ruta_archivos_tmp)) {
            return $this->error->error(mensaje: "Error no existe $ruta_archivos_tmp", data: $ruta_archivos_tmp);
        }
        return $ruta_archivos_tmp;
    }

    final public function status(string $campo, int $registro_id): array|stdClass
    {
        $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_status = parent::status($campo, $registro_id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al cambiar status', data: $r_status);
        }
        return $r_status;
    }

    /**
     * Obtiene el subtotal de una factura
     * @param int $registro_id Factura
     * @param _partida $modelo_partida
     * @param string $name_entidad
     * @return float|int|array
     * @version 0.96.26
     */
    public function sub_total(_partida $modelo_partida, string $name_entidad, int $registro_id): float|int|array
    {
        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $partidas = $this->get_partidas(name_entidad: $name_entidad, modelo_partida: $modelo_partida,
            registro_entidad_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }
        $sub_total = 0;
        foreach ($partidas as $partida) {
            $sub_total += $this->sub_total_partida(fc_partida_id: $partida['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener sub total', data: $sub_total);
            }
            $sub_total = round($sub_total, 2);
        }

        if ($sub_total <= 0.0) {
            return $this->error->error(mensaje: 'Error al obtener sub total debe ser mayor a 0', data: $sub_total);
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
        if ($fc_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_partida_id debe ser mayor a 0', data: $fc_partida_id);
        }
        $fc_partida = (new fc_partida($this->link))->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener $fc_partida', data: $fc_partida);
        }

        $keys = array('fc_partida_cantidad', 'fc_partida_valor_unitario');
        $valida = $this->validacion->valida_double_mayores_0(keys: $keys, registro: $fc_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }


        $cantidad = $fc_partida->fc_partida_cantidad;
        $cantidad = round($cantidad, 4);

        $valor_unitario = $fc_partida->fc_partida_valor_unitario;
        $valor_unitario = round($valor_unitario, 4);

        $sub_total = $cantidad * $valor_unitario;
        return round($sub_total, 4);


    }

    /**
     * Suma el conjunto de partidas para descuento
     * @param _partida $modelo_partida Modelo de tipo partida
     * @param array $partidas Partidas de una factura
     * @return float|array|int
     * @version 0.118.26
     */
    private function suma_descuento_partida(_partida $modelo_partida, array $partidas): float|array|int
    {
        $descuento = 0;
        foreach ($partidas as $partida) {
            if (!is_array($partida)) {
                return $this->error->error(mensaje: 'Error partida debe ser un array', data: $partida);
            }

            $descuento_partida = $this->descuento_partida(modelo_partida: $modelo_partida,
                registro_partida_id: $partida[$modelo_partida->key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener descuento partida', data: $descuento_partida);
            }
            $descuento += $descuento_partida;
        }
        return $descuento;
    }

    /**
     * Suma un subtotal al previo
     * @param array $fc_partida Partida a integrar
     * @param float $subtotal subtotal previo
     * @return array|float
     * @version 2.20.0
     */
    private function suma_sub_total(array $fc_partida, float $subtotal): float|array
    {
        $subtotal = round($subtotal,4);

        $keys = array('fc_partida_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $fc_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar fc_partida ', data: $valida);
        }

        $st = (new fc_partida($this->link))->subtotal_partida(registro_partida_id: $fc_partida['fc_partida_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener calculo ', data: $st);
        }
        $subtotal += round($st,4);
        return round($subtotal,4);
    }

    /**
     * Suma los subtotales acumulando por partida
     * @param array $fc_partidas Partidas de una factura
     * @return array|float
     * @version 5.7.1
     */
    private function suma_sub_totales(array $fc_partidas): float|array
    {
        $subtotal = 0.0;
        foreach ($fc_partidas as $fc_partida) {
            if(!is_array($fc_partida)){
                return $this->error->error(mensaje: 'Error fc_partida debe ser un array', data: $fc_partida);
            }
            $keys = array('fc_partida_id');
            $valida = $this->validacion->valida_ids(keys: $keys, registro: $fc_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar fc_partida ', data: $valida);
            }

            $subtotal = $this->suma_sub_total(fc_partida: $fc_partida,subtotal:  $subtotal);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener calculo ', data: $subtotal);
            }
        }
        return $subtotal;
    }

    public function timbra_xml(_doc $modelo_documento, _etapa $modelo_etapa, _partida $modelo_partida,
                               _cuenta_predial $modelo_predial, _relacion $modelo_relacion,
                               _relacionada $modelo_relacionada, _data_impuestos $modelo_retencion,
                               _sellado $modelo_sello, _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext,
                               int $registro_id): array|stdClass
    {

        $permite_transaccion = $this->verifica_permite_transaccion(modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $tipo = (new pac())->tipo;
        $timbrada = (new fc_cfdi_sellado($this->link))->existe(filtro: array('fc_factura.id' => $registro_id));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si la factura esta timbrado', data: $timbrada);
        }

        if ($timbrada) {
            return $this->error->error(mensaje: 'Error: la factura ya ha sido timbrada', data: $timbrada);
        }

        $fc_factura = $this->registro(registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }


        $xml = $this->genera_xml(modelo_documento: $modelo_documento, modelo_etapa: $modelo_etapa,
            modelo_partida: $modelo_partida, modelo_predial: $modelo_predial, modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada, modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado, modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id, tipo: $tipo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar XML', data: $xml);
        }


        $xml_contenido = file_get_contents($xml->doc_documento_ruta_absoluta);


        $filtro_files['fc_csd.id'] = $fc_factura['fc_csd_id'];

        $r_fc_key_pem = (new fc_key_pem(link: $this->link))->filtro_and(filtro: $filtro_files);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener key', data: $r_fc_key_pem);
        }

        $ruta_key_pem = '';
        if((int)$r_fc_key_pem->n_registros === 1){
            $ruta_key_pem = $r_fc_key_pem->registros[0]['doc_documento_ruta_absoluta'];
        }

        $r_fc_cer_pem = (new fc_cer_pem(link: $this->link))->filtro_and(filtro: $filtro_files);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener cer', data: $r_fc_cer_pem);
        }
        $ruta_cer_pem = '';
        if((int)$r_fc_cer_pem->n_registros === 1){
            $ruta_cer_pem = $r_fc_cer_pem->registros[0]['doc_documento_ruta_absoluta'];
        }

        $factura = $this->get_factura(modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $factura);
        }


        $data_factura = $this->data_factura(row_entidad: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos de la factura', data: $data_factura);
        }



        $pac_prov = (new pac())->pac_prov;
        $xml_timbrado = (new timbra())->timbra(contenido_xml: $xml_contenido,
            ruta_cer_pem: $ruta_cer_pem, ruta_key_pem: $ruta_key_pem, pac_prov: $pac_prov);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al timbrar XML', data: $xml_timbrado,params: array($fc_factura));
        }


        file_put_contents(filename: $xml->doc_documento_ruta_absoluta, data: $xml_timbrado->xml_sellado);

        $qr_code = $xml_timbrado->qr_code;
        if((new pac())->base_64_qr){
            $qr_code = base64_decode($qr_code);
        }

        $alta_qr = $this->guarda_documento(directorio: "codigos_qr", extension: "jpg", contenido: $qr_code,
            modelo_documento: $modelo_documento, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar QR', data: $alta_qr);
        }

        $alta_txt = $this->guarda_documento(directorio: "textos", extension: "txt", contenido: $xml_timbrado->txt,
            modelo_documento: $modelo_documento, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar TXT', data: $alta_txt);
        }

        $datos_xml = $this->get_datos_xml(ruta_xml: $xml->doc_documento_ruta_absoluta);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos del XML', data: $datos_xml);
        }

        $cfdi_sellado = $modelo_sello->maqueta_datos(
            codigo: $datos_xml['cfdi_comprobante']['NoCertificado'],
            descripcion: $datos_xml['cfdi_comprobante']['NoCertificado'],
            comprobante_sello: $datos_xml['cfdi_comprobante']['Sello'],
            comprobante_certificado: $datos_xml['cfdi_comprobante']['Certificado'],
            comprobante_no_certificado: $datos_xml['cfdi_comprobante']['NoCertificado'],
            complemento_tfd_sl: "", complemento_tfd_fecha_timbrado: $datos_xml['tfd']['FechaTimbrado'],
            complemento_tfd_no_certificado_sat: $datos_xml['tfd']['NoCertificadoSAT'],
            complemento_tfd_rfc_prov_certif: $datos_xml['tfd']['RfcProvCertif'],
            complemento_tfd_sello_cfd: $datos_xml['tfd']['SelloCFD'],
            complemento_tfd_sello_sat: $datos_xml['tfd']['SelloSAT'], uuid: $datos_xml['tfd']['UUID'],
            complemento_tfd_tfd: "", cadena_complemento_sat: $xml_timbrado->txt, key_entidad_id: $this->key_id,
            registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar datos para cfdi sellado', data: $cfdi_sellado);
        }

        $alta = $modelo_sello->alta_registro(registro: $cfdi_sellado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta cfdi sellado', data: $alta);
        }

        $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
        }

        return $cfdi_sellado;
    }

    /**
     * Obtiene el total de una factura
     * @param int $registro_id Identificador de factura
     * @param _partida $modelo_partida
     * @param string $name_entidad
     * @return float|array
     * @version 0.127.26
     */
    public function total(_partida $modelo_partida, string $name_entidad, int $registro_id): float|array
    {

        if ($registro_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $sub_total = $this->sub_total(modelo_partida: $modelo_partida,
            name_entidad: $name_entidad, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sub total', data: $sub_total);
        }
        $descuento = $this->get_factura_descuento(registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener descuento', data: $descuento);
        }

        $total = $sub_total - $descuento;

        $total = round($total, 2);
        if ($total <= 0.0) {
            return $this->error->error(mensaje: 'Error total debe ser mayor a 0', data: $total);
        }

        return $total;

    }

    final public function ultima_etapa(_etapa $modelo_etapa, int $registro_id)
    {
        $filtro[$this->key_filtro_id] = $registro_id;
        $order[$modelo_etapa->tabla.'.fecha'] = 'DESC';
        $order[$modelo_etapa->tabla.'.id'] = 'DESC';
        $r_etapas = $modelo_etapa->filtro_and(filtro: $filtro, limit: 1, order: $order);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_etapas', data: $r_etapas);
        }
        $ultima_etapa = new stdClass();
        if($r_etapas->n_registros > 0){
            $ultima_etapa = $r_etapas->registros_obj[0];
        }

        return $ultima_etapa;

    }

    private function ultimo_folio(int $fc_csd_id){

        $data = $this->data_para_folio(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener data para folio', data: $data);
        }
        $number_folio = $this->number_folio(fc_csd_serie: $data->fc_csd_serie,r_registro:  $data->r_registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener number_folio', data: $number_folio);
        }

        $folio_str = $this->folio_str(number_folio: $number_folio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener folio_str', data: $folio_str);
        }

        return $data->fc_csd_serie.'-'.$folio_str;

    }

    /**
     * Valida si una entidad relacionada de facturacion puede ser o no eliminada
     * @param array $etapas Etapas a verificar
     * @return bool
     */
    private function valida_permite_transaccion(array $etapas): bool
    {
        $permite_transaccion = true;
        foreach ($etapas as $etapa){
            /**
             * AJUSTAR MEDIANTE CONF
             */
            if($etapa['pr_etapa_descripcion'] === 'TIMBRADO'){
                $permite_transaccion = false;
            }
            if($etapa['pr_etapa_descripcion'] === 'CANCELADO'){
                $permite_transaccion = false;
            }
        }
        return $permite_transaccion;
    }

    /**
     * Verifica si se puede generar de una transaccion de afectacion de registro
     * @param _etapa $modelo_etapa Modelo de etapa
     * @param int $registro_id registro de entidad
     * @return array|bool
     */
    final public function verifica_permite_transaccion(_etapa $modelo_etapa, int $registro_id): bool|array
    {
        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error registro_id debe ser mayor a 0', data: $registro_id);
        }

        $permite_transaccion = $this->permite_transaccion(modelo_etapa: $modelo_etapa, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener permite_transaccion', data: $permite_transaccion);
        }

        if(!$permite_transaccion){
            return $this->error->error(mensaje: 'Error no se permite la eliminacion', data: $permite_transaccion);
        }
        return $permite_transaccion;
    }




}
