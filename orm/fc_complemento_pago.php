<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_unidad;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use gamboamartin\xml_cfdi_4\fechas;
use gamboamartin\xml_cfdi_4\xml;
use PDO;
use stdClass;

class fc_complemento_pago extends _transacciones_fc
{

    private string $com_producto_codigo_default = '84111506';
    private string $cat_sat_unidad_codigo_default = 'ACT';
    public function __construct(PDO $link)
    {
        $tabla = 'fc_complemento_pago';
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



        $fc_complemento_pago_sub_total = "($tabla.sub_total_base - $tabla.total_descuento)";


        $fc_partida_cp_operacion = "IFNULL(fc_partida_cp_operacion.sub_total_base,0) - IFNULL(fc_partida_cp_operacion.descuento,0)";
        $where_pc_partida_operacion = "fc_partida_cp_operacion.fc_complemento_pago_id = fc_complemento_pago.id AND fc_partida_cp_operacion.id = fc_partida_cp.id";

        $from_impuesto = $this->from_impuesto(entidad_partida: 'fc_partida_cp', tipo_impuesto: 'fc_traslado_cp');
        if(errores::$error){
            $error = $this->error->error(mensaje: 'Error al crear from',data:  $from_impuesto);
            print_r($error);
            exit;
        }

        $fc_complemento_pago_traslados = "(
	SELECT
		SUM((
			SELECT
				ROUND(SUM( $fc_partida_cp_operacion ),4) 
			FROM
				$from_impuesto
			WHERE
				$where_pc_partida_operacion
				) * cat_sat_factor.factor 
		) 
	FROM
		fc_traslado_cp
		LEFT JOIN fc_partida_cp ON fc_partida_cp.id = fc_traslado_cp.fc_partida_cp_id
		LEFT JOIN cat_sat_factor ON cat_sat_factor.id = fc_traslado_cp.cat_sat_factor_id 
	WHERE
		fc_partida_cp.fc_complemento_pago_id = fc_complemento_pago.id 
	)";

        $from_impuesto = $this->from_impuesto(entidad_partida: 'fc_partida_cp', tipo_impuesto: 'fc_retenido_cp');
        if(errores::$error){
            $error = $this->error->error(mensaje: 'Error al crear from',data:  $from_impuesto);
            print_r($error);
            exit;
        }


        $fc_complemento_pago_retenciones = "(
	SELECT
		SUM((
			SELECT
				ROUND(SUM( $fc_partida_cp_operacion ),4) 
			FROM
				$from_impuesto
			WHERE
				$where_pc_partida_operacion
				) * cat_sat_factor.factor 
		) 
	FROM
		fc_retenido_cp
		LEFT JOIN fc_partida_cp ON fc_partida_cp.id = fc_retenido_cp.fc_partida_cp_id
		LEFT JOIN cat_sat_factor ON cat_sat_factor.id = fc_retenido_cp.cat_sat_factor_id 
	WHERE
		fc_partida_cp.fc_complemento_pago_id = fc_complemento_pago.id 
	)";

        $fc_complemento_pago_total = "ROUND(IFNULL($fc_complemento_pago_sub_total,0)+IFNULL(ROUND($fc_complemento_pago_traslados,2),0)-IFNULL(ROUND($fc_complemento_pago_retenciones,2),0),2)";


        $fc_complemento_pago_uuid = "(SELECT IFNULL(fc_cfdi_sellado_cp.uuid,'') FROM fc_cfdi_sellado_cp WHERE fc_cfdi_sellado_cp.fc_complemento_pago_id = fc_complemento_pago.id)";

        $fc_complemento_pago_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_complemento_pago_etapa ON fc_complemento_pago_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_complemento_pago_etapa.fc_complemento_pago_id = fc_complemento_pago.id ORDER BY fc_complemento_pago_etapa.id DESC LIMIT 1)";


        $columnas_extra['fc_complemento_pago_sub_total_base'] = "IFNULL($tabla.sub_total_base,0)";
        $columnas_extra['fc_complemento_pago_descuento'] = "$tabla.total_descuento";

        $columnas_extra['fc_complemento_pago_sub_total'] = "IFNULL($fc_complemento_pago_sub_total,0)";
        $columnas_extra['fc_complemento_pago_traslados'] = "IFNULL($fc_complemento_pago_traslados,0)";
        $columnas_extra['fc_complemento_pago_retenciones'] = "IFNULL($fc_complemento_pago_retenciones,0)";
        $columnas_extra['fc_complemento_pago_total'] = "IFNULL($fc_complemento_pago_total,0)";
        $columnas_extra['fc_complemento_pago_uuid'] = "IFNULL($fc_complemento_pago_uuid,'SIN UUID')";
        $columnas_extra['fc_complemento_pago_etapa'] = "$fc_complemento_pago_etapa";




        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Pagos';


        $this->key_fc_id = 'fc_complemento_pago_id';


    }

    private function actualiza_total(int $cat_sat_factor_id, string $cat_sat_tipo_impuesto_codigo, int $fc_pago_id){
        $cat_sat_factor = (new cat_sat_factor(link: $this->link))->registro(
            registro_id: $cat_sat_factor_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_factor',data:  $cat_sat_factor);
        }


        $upd = (new fc_traslado_dr_part(link: $this->link))->upd_fc_pago_total(cat_sat_factor: $cat_sat_factor,
            cat_sat_tipo_impuesto_codigo: $cat_sat_tipo_impuesto_codigo, fc_pago_id: $fc_pago_id, tipo_impuesto: '');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
        }
        return $upd;
    }

    private function actualiza_totales(array $fc_traslados_dr_part){
        $upds = array();
        foreach ($fc_traslados_dr_part as $fc_traslado_dr_part){
            $upd = $this->actualiza_total(cat_sat_factor_id: $fc_traslado_dr_part['cat_sat_factor_id'],
                cat_sat_tipo_impuesto_codigo: $fc_traslado_dr_part['cat_sat_tipo_impuesto_codigo'],
                fc_pago_id: $fc_traslado_dr_part['fc_pago_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
            }
            $upds[] = $upd;
        }
        return $upds;
    }

    public function alta_bd(): array|stdClass
    {
        $this->modelo_email = new fc_email_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }

        $alta_fc_partida_cp = $this->alta_fc_partida(fc_complemento_pago_id: $r_alta_bd->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar partida',data:  $alta_fc_partida_cp);
        }

        $fc_pago['fc_complemento_pago_id'] = $r_alta_bd->registro_id;
        $fc_pago['version'] = '2.0';

        $r_alta_fc_pago = (new fc_pago(link: $this->link))->alta_registro(registro: $fc_pago);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar fc_pago',data:  $r_alta_fc_pago);
        }


        return $r_alta_bd;
    }

    private function alta_fc_partida(int $fc_complemento_pago_id){
        $fc_partida_cp_ins = $this->genera_partida_default(fc_complemento_pago_id: $fc_complemento_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar partida',data:  $fc_partida_cp_ins);
        }

        $alta_fc_partida_cp = (new fc_partida_cp(link: $this->link))->alta_registro(registro: $fc_partida_cp_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar partida',data:  $alta_fc_partida_cp);
        }
        return $alta_fc_partida_cp;
    }

    private function aplica_actualizacion_totales(int $fc_complemento_pago_id){
        $fc_traslados_dr_part = $this->get_fc_traslados_dr_part(fc_complemento_pago_id: $fc_complemento_pago_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr_part', data: $fc_traslados_dr_part);
        }
        $upd = $this->actualiza_totales(fc_traslados_dr_part: $fc_traslados_dr_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
        }
        return $upd;
    }

    private function complemento_fc_impuesto_dr(array $Complemento, int $fc_impuesto_dr_id,
                                                int $indice_fc_docto_relacionado, int $indice_fc_pago,
                                                int $indice_fc_pago_pago){

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR = new stdClass();

        $fc_traslados_dr = $this->fc_traslados_dr(fc_impuesto_dr_id: $fc_impuesto_dr_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr', data: $fc_traslados_dr);
        }

        foreach ($fc_traslados_dr as $fc_traslado_dr) {

            $Complemento = $this->complemento_fc_traslados_dr_part(Complemento: $Complemento,
                fc_traslado_dr_id: $fc_traslado_dr['fc_traslado_dr_id'],
                indice_fc_docto_relacionado:  $indice_fc_docto_relacionado,
                indice_fc_pago:  $indice_fc_pago, indice_fc_pago_pago: $indice_fc_pago_pago);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
            }
        }

        $fc_retenciones_dr = $this->fc_retenciones_dr(fc_impuesto_dr_id: $fc_impuesto_dr_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr', data: $fc_traslados_dr);
        }

        foreach ($fc_retenciones_dr as $fc_retencion_dr) {

            $Complemento = $this->complemento_fc_retenciones_dr_part(Complemento: $Complemento,
                fc_retencion_dr_id: $fc_retencion_dr['fc_retencion_dr_id'],
                indice_fc_docto_relacionado:  $indice_fc_docto_relacionado,
                indice_fc_pago:  $indice_fc_pago, indice_fc_pago_pago: $indice_fc_pago_pago);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
            }
        }

        return $Complemento;
    }

    private function complemento_fc_impuestos_dr(array $Complemento, int $fc_docto_relacionado_id,
                                                 int $indice_fc_docto_relacionado, int $indice_fc_pago,
                                                 int $indice_fc_pago_pago){
        $fc_impuestos_dr = $this->fc_impuestos_dr(fc_docto_relacionado_id: $fc_docto_relacionado_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_impuestos_dr', data: $fc_impuestos_dr);
        }

        foreach ($fc_impuestos_dr as $fc_impuesto_dr) {

            $Complemento = $this->complemento_fc_impuesto_dr(Complemento: $Complemento,
                fc_impuesto_dr_id: $fc_impuesto_dr['fc_impuesto_dr_id'],
                indice_fc_docto_relacionado: $indice_fc_docto_relacionado,
                indice_fc_pago: $indice_fc_pago,indice_fc_pago_pago:  $indice_fc_pago_pago);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
            }
        }
        return $Complemento;
    }



    private function complemento_fc_impuesto_dr_part(array $Complemento, int $indice_fc_docto_relacionado,
                                                     int $indice_fc_impuesto_dr_part, int $indice_fc_pago,
                                                     int $indice_fc_pago_pago, string $key_impuesto_dr,
                                                     string $nodo_dr_key, array $row_dr_part){
        $tasa_o_cuota_dr = $this->tasa_o_cuota(row_dr_part: $row_dr_part);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener tasa_o_cuota_dr', data: $tasa_o_cuota_dr);
        }

        $key_base_dr = 'fc_'.$key_impuesto_dr.'_dr_part';

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR
            ->$nodo_dr_key[$indice_fc_impuesto_dr_part] = new stdClass();

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR->$nodo_dr_key[$indice_fc_impuesto_dr_part]
            ->BaseDR = $row_dr_part[$key_base_dr.'_base_dr'];

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR->$nodo_dr_key[$indice_fc_impuesto_dr_part]
            ->ImpuestoDR = $row_dr_part['cat_sat_tipo_impuesto_codigo'];

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR->$nodo_dr_key[$indice_fc_impuesto_dr_part]
            ->TipoFactorDR = $row_dr_part['cat_sat_tipo_factor_codigo'];

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR->$nodo_dr_key[$indice_fc_impuesto_dr_part]
            ->TasaOCuotaDR = $tasa_o_cuota_dr;

        $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]
            ->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpuestosDR->$nodo_dr_key[$indice_fc_impuesto_dr_part]
            ->ImporteDR = $row_dr_part[$key_base_dr.'_importe_dr'];

        return $Complemento;
    }


    private function complemento_fc_retenciones_dr_part(array $Complemento, int $fc_retencion_dr_id,
                                                      int $indice_fc_docto_relacionado, int $indice_fc_pago,
                                                      int $indice_fc_pago_pago){
        $fc_retenciones_dr_part = $this->fc_retenciones_dr_part(fc_retencion_dr_id: $fc_retencion_dr_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr_part', data: $fc_retenciones_dr_part);
        }

        foreach ($fc_retenciones_dr_part as $indice_fc_retencion_dr_part=>$fc_retencion_dr_part) {

            $Complemento = $this->complemento_fc_impuesto_dr_part(Complemento: $Complemento,
                indice_fc_docto_relacionado:  $indice_fc_docto_relacionado,
                indice_fc_impuesto_dr_part: $indice_fc_retencion_dr_part, indice_fc_pago: $indice_fc_pago,
                indice_fc_pago_pago: $indice_fc_pago_pago, key_impuesto_dr: 'retencion', nodo_dr_key: 'RetencionesDR',
                row_dr_part: $fc_retencion_dr_part);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
            }

        }
        return $Complemento;
    }



    private function complemento_fc_traslados_dr_part(array $Complemento, int $fc_traslado_dr_id,
                                                      int $indice_fc_docto_relacionado, int $indice_fc_pago,
                                                      int $indice_fc_pago_pago){
        $fc_traslados_dr_part = $this->fc_traslados_dr_part(fc_traslado_dr_id: $fc_traslado_dr_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr_part', data: $fc_traslados_dr_part);
        }

        foreach ($fc_traslados_dr_part as $indice_fc_traslado_dr_part=>$fc_traslado_dr_part) {

            $Complemento = $this->complemento_fc_impuesto_dr_part(Complemento: $Complemento,
                indice_fc_docto_relacionado:  $indice_fc_docto_relacionado,
                indice_fc_impuesto_dr_part: $indice_fc_traslado_dr_part, indice_fc_pago: $indice_fc_pago,
                indice_fc_pago_pago:  $indice_fc_pago_pago, key_impuesto_dr: 'traslado',nodo_dr_key:  'TrasladosDR',
                row_dr_part:  $fc_traslado_dr_part);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
            }

        }
        return $Complemento;
    }

    final protected function data_factura(array $row_entidad): array|stdClass
    {
        $data = parent::data_factura(row_entidad: $row_entidad); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener data factura',data:  $data);
        }

        if(!is_array($data->conceptos)){
            return $this->error->error(mensaje: 'Error data conceptos debe ser un array',data:  $data);
        }


        if(isset($data->comprobante['forma_pago'])){
            unset($data->comprobante['forma_pago']);
        }
        if(isset($data->comprobante['sub_total'])){
            if((float)$data->comprobante['sub_total'] === 0.0){
                $data->comprobante['sub_total'] = 0;
            }
        }
        if(isset($data->comprobante['descuento'])){
            if((float)$data->comprobante['descuento'] === 0.0){
                unset($data->comprobante['descuento']);
            }
        }
        if(isset($data->comprobante['metodo_pago'])){
            unset($data->comprobante['metodo_pago']);
        }
        if(isset($data->comprobante['total'])){
            if((float)$data->comprobante['total'] === 0.0){
                $data->comprobante['total'] = 0;
            }
        }


        foreach ($data->conceptos as $indice_concepto=>$concepto){
            if(isset($concepto->no_identificacion)){
                unset($data->conceptos[$indice_concepto]->no_identificacion);
            }
            if(isset($concepto->unidad)){
                unset($data->conceptos[$indice_concepto]->unidad);
            }
            if(isset($concepto->clave_unidad)){
                $data->conceptos[$indice_concepto]->clave_unidad = 'ACT';
            }
            if(isset($concepto->descuento)){
                unset($data->conceptos[$indice_concepto]->descuento);
            }

            if(isset($concepto->valor_unitario)){
                if((float)$concepto->valor_unitario === 0.0){
                    $data->conceptos[$indice_concepto]->valor_unitario = 0;
                }
            }
            if(isset($concepto->importe)){
                if((float)$concepto->importe === 0.0){
                    $data->conceptos[$indice_concepto]->importe = 0;
                }
            }

        }

        //print_r($data->conceptos);exit;

        $Complemento = array();

        $fc_pagos = $this->fc_pagos(fc_complemento_pago_id: $this->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pagos', data: $fc_pagos);
        }

        foreach ($fc_pagos as $indice_fc_pago=>$fc_pago) {
            $Complemento[$indice_fc_pago] = new stdClass();
            $Complemento[$indice_fc_pago]->Pagos20 = new stdClass();

            $Complemento[$indice_fc_pago]->Pagos20->Version = $fc_pago['fc_pago_version'];
            $Complemento[$indice_fc_pago]->Pagos20->Totales = new stdClass();

            $fc_pago_totales = $this->fc_pago_totales(fc_pago_id: $fc_pago['fc_pago_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener fc_pago_totales', data: $fc_pago_totales);
            }

            foreach ($fc_pago_totales as $fc_pago_total) {


                if((round($fc_pago_total['fc_pago_total_total_traslados_base_iva_16'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosBaseIVA16 = $fc_pago_total['fc_pago_total_total_traslados_base_iva_16'];
                }
                if((round($fc_pago_total['fc_pago_total_total_traslados_base_iva_08'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosBaseIVA08 = $fc_pago_total['fc_pago_total_total_traslados_base_iva_08'];
                }
                if((round($fc_pago_total['fc_pago_total_total_traslados_base_iva_00'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosBaseIVA08 = $fc_pago_total['fc_pago_total_total_traslados_base_iva_00'];
                }

                if((round($fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_16'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosImpuestoIVA16 = $fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_16'];
                }
                if((round($fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_08'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosImpuestoIVA08 = $fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_08'];
                }
                if((round($fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_00'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalTrasladosImpuestoIVA00 = $fc_pago_total['fc_pago_total_total_traslados_impuesto_iva_00'];
                }

                if((round($fc_pago_total['fc_pago_total_total_retenciones_iva'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalRetencionesIVA = $fc_pago_total['fc_pago_total_total_retenciones_iva'];
                }
                if((round($fc_pago_total['fc_pago_total_total_retenciones_ieps'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalRetencionesIEPS = $fc_pago_total['fc_pago_total_total_retenciones_ieps'];
                }
                if((round($fc_pago_total['fc_pago_total_total_retenciones_isr'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->TotalRetencionesISR = $fc_pago_total['fc_pago_total_total_retenciones_isr'];
                }

                if((round($fc_pago_total['fc_pago_total_monto_total_pagos'],2) > 0.0)){
                    $Complemento[$indice_fc_pago]->Pagos20->Totales->MontoTotalPagos = $fc_pago_total['fc_pago_total_monto_total_pagos'];
                }


            }

            $fc_pago_pagos = $this->fc_pago_pagos(fc_pago_id: $fc_pago['fc_pago_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener fc_pago_pagos', data: $fc_pago_pagos);
            }

            foreach ($fc_pago_pagos as $indice_fc_pago_pago=>$fc_pago_pago) {

                $fecha_pago = (new fechas())->fecha_hora_min_sec_t($fc_pago_pago['fc_pago_pago_fecha_pago']);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al maquetar fecha de pago', data: $fecha_pago);
                }

                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago] = new stdClass();
                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->FechaPago = $fecha_pago;
                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->FormaDePagoP = $fc_pago_pago['cat_sat_forma_pago_codigo'];
                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->MonedaP = $fc_pago_pago['cat_sat_moneda_codigo'];
                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->TipoCambioP = $fc_pago_pago['com_tipo_cambio_monto'];
                $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->Monto = $fc_pago_pago['fc_pago_pago_monto'];



                $fc_doctos_relacionados = $this->fc_doctos_relacionados(fc_pago_pago_id: $fc_pago_pago['fc_pago_pago_id']);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al obtener fc_doctos_relacionados', data: $fc_doctos_relacionados);
                }



                foreach ($fc_doctos_relacionados as $indice_fc_docto_relacionado=>$fc_docto_relacionado) {

                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado] = new stdClass();
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->IdDocumento = $fc_docto_relacionado['fc_factura_uuid'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->Serie = $fc_docto_relacionado['fc_factura_serie'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->Folio = $fc_docto_relacionado['fc_factura_folio'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->MonedaDR = $fc_docto_relacionado['cat_sat_moneda_codigo'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->EquivalenciaDR = $fc_docto_relacionado['fc_docto_relacionado_equivalencia_dr'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->NumParcialidad = $fc_docto_relacionado['fc_docto_relacionado_num_parcialidad'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpSaldoAnt = $fc_docto_relacionado['fc_docto_relacionado_imp_saldo_ant'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpPagado = $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->ImpSaldoInsoluto = $fc_docto_relacionado['fc_docto_relacionado_imp_saldo_insoluto'];
                    $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->DoctoRelacionado[$indice_fc_docto_relacionado]->ObjetoImpDR = $fc_docto_relacionado['cat_sat_obj_imp_codigo'];



                    $Complemento = $this->complemento_fc_impuestos_dr(Complemento: $Complemento,
                        fc_docto_relacionado_id:  $fc_docto_relacionado['fc_docto_relacionado_id'],
                        indice_fc_docto_relacionado: $indice_fc_docto_relacionado, indice_fc_pago: $indice_fc_pago,
                        indice_fc_pago_pago: $indice_fc_pago_pago);
                    if (errores::$error) {
                        return $this->error->error(mensaje: 'Error al obtener traslado_dr_part', data: $Complemento);
                    }



                }

                $Complemento = $this->integra_fc_impuestos_p(Complemento: $Complemento,
                    fc_pago_pago_id:  $fc_pago_pago['fc_pago_pago_id'],indice_fc_pago:  $indice_fc_pago,
                    indice_fc_pago_pago:  $indice_fc_pago_pago);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al integrar integra_fc_traslados_p_part', data: $Complemento);
                }

            }
        }

        $data->Complemento =$Complemento;


        return $data;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_email = new fc_email_cp(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);


        $filtro[$this->key_filtro_id] = $this->registro_id;
        $del = (new fc_pago(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar pago',data:  $del);
        }


        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    private function fc_doctos_relacionados(int $fc_pago_pago_id){
        $filtro = array();
        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;
        $r_fc_docto_relacionado = (new fc_docto_relacionado(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_docto_relacionado', data: $r_fc_docto_relacionado);
        }
        return $r_fc_docto_relacionado->registros;
    }

    private function fc_impuestos_dr(int $fc_docto_relacionado_id){
        $filtro = array();
        $filtro['fc_docto_relacionado.id'] = $fc_docto_relacionado_id;
        $r_fc_impuesto_dr = (new fc_impuesto_dr(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_dr', data: $r_fc_impuesto_dr);
        }
        return $r_fc_impuesto_dr->registros;
    }

    private function fc_impuestos_p(int $fc_pago_pago_id){
        $filtro = array();
        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;
        $r_fc_impuesto_p = (new fc_impuesto_p(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_p', data: $r_fc_impuesto_p);
        }
        return $r_fc_impuesto_p->registros;
    }

    private function fc_pago_pagos(int $fc_pago_id){
        $filtro = array();
        $filtro['fc_pago.id'] = $fc_pago_id;
        $r_fc_pago_pago = (new fc_pago_pago(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_pago', data: $r_fc_pago_pago);
        }
        return $r_fc_pago_pago->registros;
    }

    private function fc_pago_totales(int $fc_pago_id){
        $filtro = array();
        $filtro['fc_pago.id'] = $fc_pago_id;
        $r_fc_pago_total = (new fc_pago_total(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_total', data: $r_fc_pago_total);
        }
        return $r_fc_pago_total->registros;
    }

    private function fc_pagos(int $fc_complemento_pago_id){
        $filtro = array();
        $filtro['fc_complemento_pago.id'] = $fc_complemento_pago_id;
        $r_fc_pago = (new fc_pago(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago', data: $r_fc_pago);
        }
        return $r_fc_pago->registros;
    }

    private function fc_partida_cp_ins(int $cat_sat_unidad_id, int $com_producto_id, int $fc_complemento_pago_id): array
    {
        $fc_partida_cp_ins['com_producto_id'] = $com_producto_id;
        $fc_partida_cp_ins['cantidad'] = 1;
        $fc_partida_cp_ins['descripcion'] = 'Pago';
        $fc_partida_cp_ins['valor_unitario'] = 0;
        $fc_partida_cp_ins['descuento'] = 0;
        $fc_partida_cp_ins['cat_sat_unidad_id'] = $cat_sat_unidad_id;
        $fc_partida_cp_ins['fc_complemento_pago_id'] = $fc_complemento_pago_id;

        return $fc_partida_cp_ins;
    }

    private function fc_retenciones_dr(int $fc_impuesto_dr_id){
        $filtro = array();
        $filtro['fc_impuesto_dr.id'] = $fc_impuesto_dr_id;
        $r_fc_retencion_dr = (new fc_retencion_dr(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_retencion_dr', data: $r_fc_retencion_dr);
        }
        return $r_fc_retencion_dr->registros;
    }

    private function fc_retenciones_dr_part(int $fc_retencion_dr_id){
        $filtro = array();
        $filtro['fc_retencion_dr.id'] = $fc_retencion_dr_id;
        $r_fc_retencion_dr_part = (new fc_retencion_dr_part(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_retencion_dr_part', data: $r_fc_retencion_dr_part);
        }
        return $r_fc_retencion_dr_part->registros;
    }


    private function fc_traslados_dr(int $fc_impuesto_dr_id){
        $filtro = array();
        $filtro['fc_impuesto_dr.id'] = $fc_impuesto_dr_id;
        $r_fc_traslado_dr = (new fc_traslado_dr(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_traslado_dr', data: $r_fc_traslado_dr);
        }
        return $r_fc_traslado_dr->registros;
    }

    private function fc_traslados_dr_part(int $fc_traslado_dr_id){
        $filtro = array();
        $filtro['fc_traslado_dr.id'] = $fc_traslado_dr_id;
        $r_fc_traslado_dr_part = (new fc_traslado_dr_part(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_traslado_dr_part', data: $r_fc_traslado_dr_part);
        }
        return $r_fc_traslado_dr_part->registros;
    }


    private function get_fc_impuestos_p(int $fc_impuesto_p_id, _p $modelo_p){
        $filtro = array();
        $filtro['fc_impuesto_p.id'] = $fc_impuesto_p_id;
        $r_fc_impuesto_p = $modelo_p->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_p', data: $r_fc_impuesto_p);
        }
        return $r_fc_impuesto_p->registros;
    }

    private function integra_fc_impuestos_p(array $Complemento, int $fc_pago_pago_id, int $indice_fc_pago, int $indice_fc_pago_pago){
        $fc_impuestos_p = $this->fc_impuestos_p(fc_pago_pago_id: $fc_pago_pago_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_impuestos_p', data: $fc_impuestos_p);
        }
        foreach ($fc_impuestos_p as $fc_impuesto_p) {

            $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->ImpuestosP = new stdClass();

            $Complemento = $this->integra_fc_traslados_p(Complemento: $Complemento,
                fc_impuesto_p_id:  $fc_impuesto_p['fc_impuesto_p_id'],indice_fc_pago:  $indice_fc_pago,
                indice_fc_pago_pago:  $indice_fc_pago_pago);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar integra_fc_traslados_p_part', data: $Complemento);
            }

            $Complemento = $this->integra_fc_retenciones_p(Complemento: $Complemento,
                fc_impuesto_p_id:  $fc_impuesto_p['fc_impuesto_p_id'],indice_fc_pago:  $indice_fc_pago,
                indice_fc_pago_pago:  $indice_fc_pago_pago);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar integra_fc_retenciones_p_part', data: $Complemento);
            }

        }
        return $Complemento;
    }

    private function integra_fc_retenciones_p(array $Complemento, int $fc_impuesto_p_id, int $indice_fc_pago, int $indice_fc_pago_pago){

        $modelo_p = new fc_retencion_p(link: $this->link);

        $fc_retenciones_p = $this->get_fc_impuestos_p(fc_impuesto_p_id: $fc_impuesto_p_id, modelo_p: $modelo_p);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_retenciones_p', data: $fc_retenciones_p);
        }

        $modelo_part = new fc_retencion_p_part(link: $this->link);
        foreach ($fc_retenciones_p as $fc_retencion_p) {

            $Complemento = $this->integra_fc_rows_p_part(Complemento: $Complemento,
                fc_tipo_impuesto_p_id: $fc_retencion_p['fc_retencion_p_id'], indice_fc_pago: $indice_fc_pago,
                indice_fc_pago_pago: $indice_fc_pago_pago, modelo_part: $modelo_part, tipo_impuesto: 'retencion');
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar integra_fc_retenciones_p_part', data: $Complemento);
            }
        }
        return $Complemento;
    }

    private function integra_fc_traslados_p(array $Complemento, int $fc_impuesto_p_id, int $indice_fc_pago, int $indice_fc_pago_pago){

        $modelo_p = new fc_traslado_p(link: $this->link);

        $fc_traslados_p = $this->get_fc_impuestos_p(fc_impuesto_p_id: $fc_impuesto_p_id, modelo_p: $modelo_p);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_p', data: $fc_traslados_p);
        }

        $modelo_part = new fc_traslado_p_part(link: $this->link);
        foreach ($fc_traslados_p as $fc_traslado_p) {

            $Complemento = $this->integra_fc_rows_p_part(Complemento: $Complemento,
                fc_tipo_impuesto_p_id: $fc_traslado_p['fc_traslado_p_id'], indice_fc_pago: $indice_fc_pago,
                indice_fc_pago_pago: $indice_fc_pago_pago, modelo_part: $modelo_part, tipo_impuesto: 'traslado');
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar integra_fc_traslados_p_part', data: $Complemento);
            }
        }
        return $Complemento;
    }

    private function get_fc_traslados_dr_part(int $fc_complemento_pago_id){
        $filtro['fc_complemento_pago.id'] = $fc_complemento_pago_id;
        $r_fc_traslado_dr_part = (new fc_traslado_dr_part(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_traslado_dr_part', data: $r_fc_traslado_dr_part);
        }
        return $r_fc_traslado_dr_part->registros;
    }



    private function get_fc_impuestos_p_part(int $fc_tipo_impuesto_p_id, string $key_tipo_impuesto,
                                             fc_traslado_p_part|fc_retencion_p_part $modelo_p_part){
        $filtro = array();
        $filtro[$key_tipo_impuesto.'.id'] = $fc_tipo_impuesto_p_id;
        $r_fc_impuesto_p_part = $modelo_p_part->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_p_part', data: $r_fc_impuesto_p_part);
        }
        return $r_fc_impuesto_p_part->registros;
    }



    private function genera_partida_default(int $fc_complemento_pago_id){
        $data_partida = $this->partida_default();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partida default data',data:  $data_partida);
        }

        $fc_partida_cp_ins = $this->fc_partida_cp_ins(cat_sat_unidad_id: $data_partida->cat_sat_unidad['cat_sat_unidad_id'],
            com_producto_id:  $data_partida->com_producto['com_producto_id'], fc_complemento_pago_id: $fc_complemento_pago_id);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar partida',data:  $fc_partida_cp_ins);
        }

        return $fc_partida_cp_ins;
    }





    /**
     * @param array $fc_row_p_part Registro de tipo fc_traslado_p_part
     * @param string $tipo_impuesto
     * @return stdClass|array
     */
    private function integra_fc_row_p_part(array $fc_row_p_part, string $tipo_impuesto): stdClass|array
    {
        $keys = array('fc_'.$tipo_impuesto.'_p_part_base_p','cat_sat_tipo_impuesto_codigo','cat_sat_tipo_factor_codigo',
            'cat_sat_factor_factor','fc_'.$tipo_impuesto.'_p_part_importe_p');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $fc_row_p_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_row_p_part', data: $valida);
        }

        $keys = array('fc_'.$tipo_impuesto.'_p_part_base_p','cat_sat_factor_factor','fc_'.$tipo_impuesto.'_p_part_importe_p');

        $valida = $this->validacion->valida_numerics(keys: $keys,row:  $fc_row_p_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_row_p_part', data: $valida);
        }

        $tasa_o_cuota_p = $this->tasa_o_cuota(row_dr_part: $fc_row_p_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar tasa_o_cuota_p', data: $tasa_o_cuota_p);
        }


        $row_p_part = new stdClass();
        $row_p_part->BaseP = $fc_row_p_part['fc_'.$tipo_impuesto.'_p_part_base_p'];
        $row_p_part->ImpuestoP = $fc_row_p_part['cat_sat_tipo_impuesto_codigo'];
        $row_p_part->TipoFactorP = $fc_row_p_part['cat_sat_tipo_factor_codigo'];
        $row_p_part->TasaOCuotaP = $tasa_o_cuota_p;
        $row_p_part->ImporteP = $fc_row_p_part['fc_'.$tipo_impuesto.'_p_part_importe_p'];
        return $row_p_part;
    }

    private function integra_fc_rows_p_part(array $Complemento, int $fc_tipo_impuesto_p_id, int $indice_fc_pago,
                                            int $indice_fc_pago_pago,
                                            fc_traslado_p_part|fc_retencion_p_part $modelo_part, string $tipo_impuesto){

        $key_tipo_impuesto = 'fc_'.$tipo_impuesto.'_p';
        $fc_rows_p_part = $this->get_fc_impuestos_p_part(fc_tipo_impuesto_p_id: $fc_tipo_impuesto_p_id,
            key_tipo_impuesto: $key_tipo_impuesto,modelo_p_part: $modelo_part);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_rows_p_part', data: $fc_rows_p_part);
        }

        $key_nodo = '';
        if($tipo_impuesto === 'retencion'){
            $key_nodo = 'RetencionesP';
        }
        if($tipo_impuesto === 'traslado'){
            $key_nodo = 'TrasladosP';
        }

        foreach ($fc_rows_p_part as $indice_row_p_part=>$fc_row_p_part) {
            $row_p_part = $this->integra_fc_row_p_part(fc_row_p_part: $fc_row_p_part,tipo_impuesto: $tipo_impuesto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar row_p_part', data: $row_p_part);
            }
            $Complemento[$indice_fc_pago]->Pagos20->Pago[$indice_fc_pago_pago]->ImpuestosP->$key_nodo[$indice_row_p_part] = $row_p_part;
        }
        return $Complemento;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }

        $upd = $this->aplica_actualizacion_totales(fc_complemento_pago_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
        }


        return $r_modifica_bd;
    }

    private function partida_default(){
        $com_producto = (new com_producto(link: $this->link))->registro_by_codigo(
            codigo: $this->com_producto_codigo_default);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener producto',data:  $com_producto);
        }
        $cat_sat_unidad = (new cat_sat_unidad(link: $this->link))->registro_by_codigo(
            codigo: $this->cat_sat_unidad_codigo_default);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_unidad',data:  $cat_sat_unidad);
        }
        $data = new stdClass();
        $data->com_producto = $com_producto;
        $data->cat_sat_unidad = $cat_sat_unidad;

        return $data;


    }

    private function tasa_o_cuota(array $row_dr_part): string
    {
        $tasa_o_cuota_dr = round($row_dr_part['cat_sat_factor_factor'],6);
        return number_format($tasa_o_cuota_dr,6,'.','');
    }



}