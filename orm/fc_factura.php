<?php

namespace gamboamartin\facturacion\models;


use base\orm\modelo;

use config\generales;
use config\pac;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\comercial\models\com_tmp_cte_dp;
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

class fc_factura extends _transacciones_fc
{

    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura';
        $columnas = array($tabla => false, 'fc_csd' => $tabla, 'cat_sat_forma_pago' => $tabla, 'cat_sat_metodo_pago' => $tabla,
            'cat_sat_moneda' => $tabla, 'com_tipo_cambio' => $tabla, 'cat_sat_uso_cfdi' => $tabla,
            'cat_sat_tipo_de_comprobante' => $tabla, 'cat_sat_regimen_fiscal' => $tabla, 'com_sucursal' => $tabla,
            'com_cliente' => 'com_sucursal', 'dp_calle_pertenece' => $tabla, 'dp_calle' => 'dp_calle_pertenece',
            'dp_colonia_postal' => 'dp_calle_pertenece', 'dp_colonia' => 'dp_colonia_postal', 'dp_cp' => 'dp_colonia_postal',
            'dp_municipio' => 'dp_cp', 'dp_estado' => 'dp_municipio', 'dp_pais' => 'dp_estado', 'org_sucursal' => 'fc_csd',
            'org_empresa' => 'org_sucursal');


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

        $fc_partida_cantidad = ' ROUND( IFNULL( fc_partida.cantidad,0 ),2) ';
        $fc_partida_valor_unitario = ' ROUND( IFNULL( fc_partida.valor_unitario,0),2) ';
        $fc_partida_descuento = ' ROUND( IFNULL(fc_partida.descuento,0 ),2 )';

        $fc_partida_sub_total_base = "ROUND( $fc_partida_cantidad * $fc_partida_valor_unitario, 2 ) ";


        $fc_ligue_partida_factura = " fc_partida.fc_factura_id = fc_factura.id ";


        $fc_factura_sub_total_base = "ROUND((SELECT SUM( $fc_partida_sub_total_base) FROM fc_partida WHERE $fc_ligue_partida_factura),4)";
        $fc_factura_descuento = "ROUND((SELECT SUM( $fc_partida_descuento ) FROM fc_partida WHERE $fc_ligue_partida_factura),4)";
        $fc_factura_sub_total = "($fc_factura_sub_total_base - $fc_factura_descuento)";


        $fc_partida_operacion = "IFNULL(fc_partida_operacion.cantidad,0) * IFNULL(fc_partida_operacion.valor_unitario,0) - IFNULL(fc_partida_operacion.descuento,0)";
        $where_pc_partida_operacion = "fc_partida_operacion.fc_factura_id = fc_factura.id AND fc_partida_operacion.id = fc_partida.id";

        $from_impuesto = $this->from_impuesto(tipo_impuesto: 'fc_traslado');
        if(errores::$error){
            $error = $this->error->error(mensaje: 'Error al crear from',data:  $from_impuesto);
            print_r($error);
            exit;
        }

        $fc_factura_traslados = "(
	SELECT
		SUM((
			SELECT
				ROUND(SUM( $fc_partida_operacion ),4) 
			FROM
				$from_impuesto
			WHERE
				$where_pc_partida_operacion
				) * cat_sat_factor.factor 
		) 
	FROM
		fc_traslado
		LEFT JOIN fc_partida ON fc_partida.id = fc_traslado.fc_partida_id
		LEFT JOIN cat_sat_factor ON cat_sat_factor.id = fc_traslado.cat_sat_factor_id 
	WHERE
		fc_partida.fc_factura_id = fc_factura.id 
	)";

        $from_impuesto = $this->from_impuesto(tipo_impuesto: 'fc_retenido');
        if(errores::$error){
            $error = $this->error->error(mensaje: 'Error al crear from',data:  $from_impuesto);
            print_r($error);
            exit;
        }


        $fc_factura_retenciones = "(
	SELECT
		SUM((
			SELECT
				ROUND(SUM( $fc_partida_operacion ),4) 
			FROM
				$from_impuesto
			WHERE
				$where_pc_partida_operacion
				) * cat_sat_factor.factor 
		) 
	FROM
		fc_retenido
		LEFT JOIN fc_partida ON fc_partida.id = fc_retenido.fc_partida_id
		LEFT JOIN cat_sat_factor ON cat_sat_factor.id = fc_retenido.cat_sat_factor_id 
	WHERE
		fc_partida.fc_factura_id = fc_factura.id 
	)";

        $fc_factura_total = "ROUND(IFNULL($fc_factura_sub_total,0)+IFNULL(ROUND($fc_factura_traslados,2),0)-IFNULL(ROUND($fc_factura_retenciones,2),0),2)";


        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $fc_factura_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_factura_etapa ON fc_factura_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_factura_etapa.fc_factura_id = fc_factura.id ORDER BY fc_factura_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_factura_sub_total_base'] = "IFNULL($fc_factura_sub_total_base,0)";
        $columnas_extra['fc_factura_descuento'] = "IFNULL($fc_factura_descuento,0)";
        $columnas_extra['fc_factura_sub_total'] = "IFNULL($fc_factura_sub_total,0)";
        $columnas_extra['fc_factura_traslados'] = "IFNULL($fc_factura_traslados,0)";
        $columnas_extra['fc_factura_retenciones'] = "IFNULL($fc_factura_retenciones,0)";
        $columnas_extra['fc_factura_total'] = "IFNULL($fc_factura_total,0)";
        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";
        $columnas_extra['fc_factura_etapa'] = "$fc_factura_etapa";




        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura';

        $modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_etapa = $modelo_etapa;

        $modelo_email = new fc_email(link: $this->link);
        $this->modelo_email = $modelo_email;


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
            $registro['fecha'] = date('Y-m-d');
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

        $r_alta_fc_email = (new _email())->inserta_fc_emails(key_fc_id: 'fc_factura_id',
            modelo_email: $this->modelo_email, link: $this->link, registro_fc: $registro_fc);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar correos', data: $r_alta_fc_email);
        }

        $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $this->modelo_etapa, registro_id: $r_alta_bd->registro_id,
            valida_existencia_etapa: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
        }


        return $r_alta_bd;
    }


    /**
     * Cancela una factura
     * @param int $cat_sat_motivo_cancelacion_id Motivo de cancelacion
     * @param int $fc_factura_id Factura a cancelar
     * @return array|stdClass
     */
    final public function cancela_bd(int $cat_sat_motivo_cancelacion_id, int $fc_factura_id): array|stdClass
    {
        $fc_cancelacion_ins['fc_factura_id'] = $fc_factura_id;
        $fc_cancelacion_ins['cat_sat_motivo_cancelacion_id'] = $cat_sat_motivo_cancelacion_id;

        $r_fc_cancelacion = (new fc_cancelacion(link: $this->link))->alta_registro(registro: $fc_cancelacion_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cancelar factura',data:  $r_fc_cancelacion);
        }

        $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $this->modelo_etapa, registro_id: $fc_factura_id, valida_existencia_etapa: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
        }

        return $r_fc_cancelacion;
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
        if ($descuento < 0.0) {
            return $this->error->error(mensaje: 'Error el descuento previo no puede ser menor a 0', data: $descuento);
        }

        $keys = array('fc_partida_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        $descuento_nuevo = $this->descuento_partida(fc_partida_id: $partida['fc_partida_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener descuento', data: $descuento_nuevo);
        }
        return round($descuento + $descuento_nuevo, 2);
    }


    private function data_factura(array $factura): array|stdClass
    {
        $comprobante = (new _comprobante())->comprobante(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener comprobante', data: $comprobante);
        }

        $emisor = $this->emisor(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $emisor);
        }

        $receptor = $this->receptor(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $receptor);
        }

        $conceptos = $factura['conceptos'];

        $impuestos = (new _impuestos())->impuestos(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener impuestos', data: $impuestos);
        }


        $data = new stdClass();
        $data->comprobante = $comprobante;
        $data->emisor = $emisor;
        $data->receptor = $receptor;
        $data->conceptos = $conceptos;
        $data->impuestos = $impuestos;
        $data->relacionados = $factura['relacionados'];
        return $data;
    }




    /**
     */
    private function del_partidas(array $fc_partidas): array
    {
        $dels = array();
        foreach ($fc_partidas as $fc_partida) {
            $del = (new fc_partida($this->link))->elimina_bd(id: $fc_partida['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
            }
            $dels[] = $del;
        }
        return $dels;
    }


    /**
     * Obtiene y redondea un descuento de una partida
     * @param int $fc_partida_id partida
     * @return float|array
     * @version 0.98.26
     */
    private function descuento_partida(int $fc_partida_id): float|array
    {
        if ($fc_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_partida_id debe ser mayor a 0', data: $fc_partida_id);
        }
        $fc_partida = (new fc_partida($this->link))->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener $fc_partida', data: $fc_partida);
        }

        $descuento = $fc_partida->fc_partida_descuento;

        return round($descuento, 4);


    }

    public function doc_tipo_documento_id(string $extension)
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

    public function elimina_bd(int $id): array|stdClass
    {

        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }


        $del = $this->elimina_partidas(fc_factura_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
        }

        $filtro = array();
        $filtro['fc_factura.id'] = $id;

        $r_fc_factura_documento = (new fc_factura_documento(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_factura_documento);
        }
        $r_fc_email = (new fc_email(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_email);
        }
        $r_fc_factura_etapa = (new fc_factura_etapa(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_factura_etapa);
        }

        $r_elimina_factura = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar factura', data: $r_elimina_factura);
        }
        return $r_elimina_factura;
    }

    /**
     */
    private function elimina_partidas(int $fc_factura_id): array
    {
        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $fc_partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $fc_partidas);
        }

        $del = $this->del_partidas(fc_partidas: $fc_partidas);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $del);
        }
        return $del;
    }


    /**
     * Obtiene el emisor de una factura
     * @param array $factura Factura a integrar
     * @return array
     */
    private function emisor(array $factura): array
    {

        $emisor = array();
        $emisor['rfc'] = $factura['org_empresa_rfc'];
        $emisor['nombre'] = $factura['org_empresa_razon_social'];
        $emisor['regimen_fiscal'] = $factura['cat_sat_regimen_fiscal_codigo'];
        return $emisor;
    }

    final public function envia_factura(int $fc_factura_id){
        $notifica = (new _email())->envia_factura(fc_factura_id: $fc_factura_id,link:  $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al enviar notificacion',data:  $notifica);
        }
        return $notifica;
    }

    /**
     * Obtiene las etapas de una factura
     * @param int $fc_factura_id Factura a verificar etapas
     * @return array
     */
    private function etapas(int $fc_factura_id): array
    {
        $filtro['fc_factura.id'] =  $fc_factura_id;
        $r_fc_factura_etapa = (new fc_factura_etapa(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_factura_etapa', data: $r_fc_factura_etapa);
        }
        return $r_fc_factura_etapa->registros;
    }



    private function from_impuesto(string $tipo_impuesto): string
    {
        return "fc_partida AS fc_partida_operacion LEFT JOIN $tipo_impuesto ON $tipo_impuesto.fc_partida_id = fc_partida_operacion.id";
    }


    public function genera_ruta_archivo_tmp(): array|string
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

    public function genera_xml(int $fc_factura_id, string $tipo): array|stdClass
    {
        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $factura = $this->get_factura(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $factura);
        }

        $data_factura = $this->data_factura(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos de la factura', data: $data_factura);
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
                relacionados: $data_factura->relacionados);
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

        $existe = (new fc_factura_documento(link: $this->link))->existe(array('fc_factura.id' => $this->registro_id));
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
            $fc_factura_documento['fc_factura_id'] = $this->registro_id;
            $fc_factura_documento['doc_documento_id'] = $documento->registro_id;

            $fc_factura_documento = (new fc_factura_documento(link: $this->link))->alta_registro(registro: $fc_factura_documento);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta factura documento', data: $fc_factura_documento);
            }
        }
        else {
            $r_fc_factura_documento = (new fc_factura_documento(link: $this->link))->filtro_and(
                filtro: array('fc_factura.id' => $this->registro_id));
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

    final public function  get_data_relaciones(int $fc_factura_id){

        $relaciones = (new fc_relacion(link: $this->link))->relaciones(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relaciones', data: $relaciones);
        }

        foreach ($relaciones as $indice=>$fc_relacion){
            $relacionadas = (new fc_relacion(link: $this->link))->facturas_relacionadas(fc_relacion: $fc_relacion);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener relacionadas', data: $relacionadas);
            }
            $relaciones[$indice]['fc_facturas_relacionadas'] = $relacionadas;
        }
        return $relaciones;

    }

    /**
     *
     * @param int $fc_factura_id
     * @return array|stdClass|int
     */
    final public function get_factura(int $fc_factura_id): array|stdClass|int
    {
        $hijo = array();
        $hijo['fc_partida']['filtros'] = array();
        $hijo['fc_partida']['filtros_con_valor'] = array('fc_factura.id' => $fc_factura_id);
        $hijo['fc_partida']['nombre_estructura'] = 'partidas';
        $hijo['fc_partida']['namespace_model'] = 'gamboamartin\\facturacion\\models';
        $registro = $this->registro(registro_id: $fc_factura_id, hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $registro);
        }

        $relacionados = (new fc_relacion(link: $this->link))->get_relaciones(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relaciones', data: $relacionados);
        }



        $conceptos = array();

        $total_impuestos_trasladados = 0.0;
        $total_impuestos_retenidos = 0.0;

        $trs_global= array();
        $ret_global= array();
        foreach ($registro['partidas'] as $key => $partida) {

            $traslados = (new fc_traslado($this->link))->get_traslados(fc_partida_id: $partida['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener el traslados de la partida', data: $traslados);
            }

            $retenidos = (new fc_retenido($this->link))->get_retenidos(fc_partida_id: $partida['fc_partida_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener el retenidos de la partida', data: $retenidos);
            }

            $filtro['com_producto.id'] = $partida['com_producto_id'];
            $existe_tmp = (new com_tmp_prod_cs(link: $this->link))->existe(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar si existe existe_tmp', data: $existe_tmp);
            }
            if($existe_tmp){
                $r_com_tmp_prod_cs = (new com_tmp_prod_cs(link: $this->link))->filtro_and(filtro: $filtro);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al obtener producto', data: $r_com_tmp_prod_cs);
                }
                $partida['cat_sat_producto_codigo'] = $r_com_tmp_prod_cs->registros[0]['com_tmp_prod_cs_cat_sat_producto'];
            }




            $registro['partidas'][$key]['traslados'] = $traslados->registros;
            $registro['partidas'][$key]['retenidos'] = $retenidos->registros;

            $concepto = new stdClass();
            $concepto->clave_prod_serv = $partida['cat_sat_producto_codigo'];
            $concepto->cantidad = $partida['fc_partida_cantidad'];
            $concepto->clave_unidad = $partida['cat_sat_unidad_codigo'];
            $concepto->descripcion = $partida['fc_partida_descripcion'];
            $concepto->valor_unitario = number_format($partida['fc_partida_valor_unitario'], 2);;
            $concepto->importe = number_format($partida['fc_partida_importe'], 2);
            $concepto->objeto_imp = $partida['cat_sat_obj_imp_codigo'];
            $concepto->no_identificacion = $partida['com_producto_codigo'];;
            $concepto->unidad = $partida['cat_sat_unidad_descripcion'];

            $descuento = 0.0;
            if(isset($partida['fc_partida_descuento'])){
                $descuento = $partida['fc_partida_descuento'];
            }

            $descuento = (new _comprobante())->monto_dos_dec(monto: $descuento);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar descuento', data: $descuento);
            }

            $concepto->descuento = $descuento;

            $concepto->impuestos = array();
            $concepto->impuestos[0] = new stdClass();
            $concepto->impuestos[0]->traslados = array();
            $concepto->impuestos[0]->retenciones = array();


            $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $traslados, key_importe_impuesto: 'fc_traslado_importe');
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar traslados', data: $impuestos);
            }



            $trs_global = (new _impuestos())->impuestos_globales(impuestos: $traslados, global_imp: $trs_global, key_importe: 'fc_traslado_importe');
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $trs_global);
            }

            $ret_global = (new _impuestos())->impuestos_globales(impuestos: $retenidos, global_imp: $ret_global, key_importe: 'fc_retenido_importe');
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $ret_global);
            }


            $concepto->impuestos[0]->traslados = $impuestos;

            $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $retenidos,  key_importe_impuesto: 'fc_retenido_importe');
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar retenciones', data: $impuestos);
            }

            $concepto->impuestos[0]->retenciones = $impuestos;

            if(isset($partida['com_producto_aplica_predial']) && $partida['com_producto_aplica_predial'] === 'activo'){
                $r_fc_cuenta_predial = (new fc_cuenta_predial(link: $this->link))->filtro_and(filtro: array('fc_factura.id'=>$fc_factura_id));
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al obtener cuenta predial', data: $r_fc_cuenta_predial);
                }
                if($r_fc_cuenta_predial->n_registros === 0){
                    return $this->error->error(mensaje: 'Error no existe predial asignado', data: $r_fc_cuenta_predial);
                }
                if($r_fc_cuenta_predial->n_registros > 1){
                    return $this->error->error(mensaje: 'Error de integridad en predial', data: $r_fc_cuenta_predial);
                }
                $fc_cuenta_predial_numero = $r_fc_cuenta_predial->registros[0]['fc_cuenta_predial_descripcion'];

                $concepto->cuenta_predial = $fc_cuenta_predial_numero;

            }


            $conceptos[] = $concepto;

            $total_impuestos_trasladados += ($partida['fc_partida_importe_total_traslado']);
            $total_impuestos_retenidos += ($partida['fc_partida_importe_total_retenido']);

        }

        $registro['fc_factura_total'] = round($registro['fc_factura_sub_total']
            + $total_impuestos_trasladados - $total_impuestos_retenidos,2);
        $registro['traslados'] = $trs_global;
        $registro['retenidos'] = $ret_global;

        foreach ($registro['traslados'] as $indice=>$value){
            if($value->tipo_factor === 'Exento'){
                unset($registro['traslados'][$indice]->tasa_o_cuota);
                unset($registro['traslados'][$indice]->importe);
            }
        }


        $registro['conceptos'] = $conceptos;
        $registro['total_impuestos_trasladados'] = number_format($total_impuestos_trasladados, 2);
        $registro['total_impuestos_retenidos'] = number_format($total_impuestos_retenidos, 2);
        $registro['relacionados'] = $relacionados;

        return $registro;
    }


    final public function inserta_notificacion(int $registro_id){
        $notificaciones = (new _email())->crear_notificaciones(registro_id: $registro_id,link:  $this->link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar notificaciones', data: $notificaciones);
        }
        return $notificaciones;
    }

    final public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

    /**
     * Obtiene el subtotal de una factura
     * @param int $fc_factura_id Factura a obtener info
     * @return float|array
     * @version 6.7.0
     */
    final public function get_factura_sub_total(int $fc_factura_id): float|array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }
        $fc_factura = $this->registro(registro_id: $fc_factura_id, columnas: array('fc_factura_sub_total'),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        return round($fc_factura->fc_factura_sub_total,2);


    }

    /**
     * Calcula los impuestos trasladados de una factura
     * @param int $fc_factura_id Factura a calcular
     * @return float|array
     * @version 4.14.0
     */
    public function get_factura_imp_trasladados(int $fc_factura_id): float|array
    {
        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }
        $imp_traslado = 0.0;

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
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }

        $imp_traslado = 0.0;

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
     * @version 6.10.0
     */
    final public function get_factura_descuento(int $fc_factura_id): float|array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }

        $fc_factura = $this->registro(registro_id: $fc_factura_id, columnas: array('fc_factura_descuento'),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }
        return round($fc_factura->fc_factura_descuento,2);

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
        $fc_factura = $this->registro(registro_id: $fc_factura_id, columnas: array('fc_factura_total'),
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }
        return round($fc_factura->fc_factura_total,2);
    }

    /**
     * Obtiene las partidas de una factura
     * @param int $fc_factura_id Factura a validar
     * @return array
     * @version 0.83.26
     */
    private function get_partidas(int $fc_factura_id): array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }

        $filtro['fc_factura.id'] = $fc_factura_id;

        $r_fc_partida = (new fc_partida($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        return $r_fc_partida->registros;
    }





    private function permite_transaccion(int $fc_factura_id){
        $fc_factura_etapas = $this->etapas(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_factura_etapas', data: $fc_factura_etapas);
        }
        $permite_transaccion = $this->valida_permite_transaccion(fc_factura_etapas: $fc_factura_etapas);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener permite_transaccion', data: $permite_transaccion);
        }
        return $permite_transaccion;
    }

    private function receptor(array $factura): array
    {
        $com_sucursal = (new com_sucursal(link: $this->link))->registro(registro_id: $factura['com_sucursal_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener com_sucursal', data: $com_sucursal);
        }

        $domicilio_fiscal_receptor = $com_sucursal['dp_cp_descripcion'];
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
        }



        $receptor = array();
        $receptor['rfc'] = $com_sucursal['com_cliente_rfc'];
        $receptor['nombre'] = $com_sucursal['com_cliente_razon_social'];
        $receptor['domicilio_fiscal_receptor'] = $domicilio_fiscal_receptor; //'91779'; dp_cp_descripcion de com_sucursal.dp_calle_pertenece hacia cp
        $receptor['regimen_fiscal_receptor'] = $com_sucursal['cat_sat_regimen_fiscal_codigo'];
        $receptor['uso_cfdi'] = $factura['cat_sat_uso_cfdi_codigo'];
        return $receptor;
    }

    public function ruta_archivos(string $directorio = ""): array|string
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
        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $registro_id);
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
     * @param int $fc_factura_id Factura
     * @return float|int|array
     * @version 0.96.26
     */
    public function sub_total(int $fc_factura_id): float|int|array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }

        $partidas = $this->get_partidas(fc_factura_id: $fc_factura_id);
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
     * @param array $partidas Partidas de una factura
     * @return float|array|int
     * @version 0.118.26
     */
    private function suma_descuento_partida(array $partidas): float|array|int
    {
        $descuento = 0;
        foreach ($partidas as $partida) {
            if (!is_array($partida)) {
                return $this->error->error(mensaje: 'Error partida debe ser un array', data: $partida);
            }

            $descuento_partida = $this->descuento_partida(fc_partida_id: $partida['fc_partida_id']);
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

        $st = (new fc_partida($this->link))->subtotal_partida($fc_partida['fc_partida_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener calculo ', data: $st);
        }
        $subtotal += round($st,4);
        return round($subtotal,4);
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

    public function timbra_xml(int $fc_factura_id): array|stdClass
    {
        $permite_transaccion = $this->verifica_permite_transaccion(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $tipo = (new pac())->tipo;
        $timbrada = (new fc_cfdi_sellado($this->link))->existe(filtro: array('fc_factura.id' => $fc_factura_id));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si la factura esta timbrado', data: $timbrada);
        }

        if ($timbrada) {
            return $this->error->error(mensaje: 'Error: la factura ya ha sido timbrada', data: $timbrada);
        }

        $fc_factura = $this->registro(registro_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        $xml = $this->genera_xml(fc_factura_id: $fc_factura_id, tipo: $tipo);
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

        $factura = $this->get_factura(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $factura);
        }


        $data_factura = $this->data_factura(factura: $factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos de la factura', data: $data_factura);
        }



        $pac_prov = (new pac())->pac_prov;
        $xml_timbrado = (new timbra())->timbra(contenido_xml: $xml_contenido, id_comprobante: '',
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
            fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar QR', data: $alta_qr);
        }

        $alta_txt = $this->guarda_documento(directorio: "textos", extension: "txt", contenido: $xml_timbrado->txt,
            fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar TXT', data: $alta_txt);
        }

        $datos_xml = $this->get_datos_xml(ruta_xml: $xml->doc_documento_ruta_absoluta);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener datos del XML', data: $datos_xml);
        }

        $cfdi_sellado = (new fc_cfdi_sellado($this->link))->maqueta_datos(codigo: $datos_xml['cfdi_comprobante']['NoCertificado'],
            descripcion: $datos_xml['cfdi_comprobante']['NoCertificado'], fc_factura_id: $fc_factura_id,
            comprobante_sello: $datos_xml['cfdi_comprobante']['Sello'], comprobante_certificado: $datos_xml['cfdi_comprobante']['Certificado'],
            comprobante_no_certificado: $datos_xml['cfdi_comprobante']['NoCertificado'], complemento_tfd_sl: "",
            complemento_tfd_fecha_timbrado: $datos_xml['tfd']['FechaTimbrado'],
            complemento_tfd_no_certificado_sat: $datos_xml['tfd']['NoCertificadoSAT'], complemento_tfd_rfc_prov_certif: $datos_xml['tfd']['RfcProvCertif'],
            complemento_tfd_sello_cfd: $datos_xml['tfd']['SelloCFD'], complemento_tfd_sello_sat: $datos_xml['tfd']['SelloSAT'],
            uuid: $datos_xml['tfd']['UUID'], complemento_tfd_tfd: "", cadena_complemento_sat: $xml_timbrado->txt);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar datos para cfdi sellado', data: $cfdi_sellado);
        }

        $alta = (new fc_cfdi_sellado($this->link))->alta_registro(registro: $cfdi_sellado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta cfdi sellado', data: $alta);
        }

        $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: __FUNCTION__, fecha: '',
            modelo: $this, modelo_etapa: $this->modelo_etapa, registro_id: $fc_factura_id, valida_existencia_etapa: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
        }

        return $cfdi_sellado;
    }

    private function guarda_documento(string $directorio, string $extension, string $contenido, int $fc_factura_id): array|stdClass
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

        $tipo_documento = (new fc_factura(link: $this->link))->doc_tipo_documento_id(extension: $extension);
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

        $registro['fc_factura_id'] = $fc_factura_id;
        $registro['doc_documento_id'] = $documento->registro_id;
        $factura_documento = (new fc_factura_documento($this->link))->alta_registro(registro: $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al guardar relacion factura con documento', data: $factura_documento);
        }

        return $documento;
    }

    /**
     * Obtiene el total de una factura
     * @param int $fc_factura_id Identificador de factura
     * @return float|array
     * @version 0.127.26
     */
    public function total(int $fc_factura_id): float|array
    {

        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }

        $sub_total = $this->sub_total(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sub total', data: $sub_total);
        }
        $descuento = $this->get_factura_descuento(fc_factura_id: $fc_factura_id);
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

    private function valida_permite_transaccion(array $fc_factura_etapas): bool
    {
        $permite_transaccion = true;
        foreach ($fc_factura_etapas as $fc_factura_etapa){
            /**
             * AJUSTAR MEDIANTE CONF
             */
            if($fc_factura_etapa['pr_etapa_descripcion'] === 'TIMBRADO'){
                $permite_transaccion = false;
            }
        }
        return $permite_transaccion;
    }

    final public function verifica_permite_transaccion(int $fc_factura_id){
        $permite_transaccion = $this->permite_transaccion(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener permite_transaccion', data: $permite_transaccion);
        }

        if(!$permite_transaccion){
            return $this->error->error(mensaje: 'Error no se permite la eliminacion', data: $permite_transaccion);
        }
        return $permite_transaccion;
    }
}