<?php

namespace gamboamartin\facturacion\models;


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

class fc_complemento_pago extends _transacciones_fc
{

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

        $fc_partida_cp_cantidad = ' ROUND( IFNULL( fc_partida_cp.cantidad,0 ),2) ';
        $fc_partida_cp_valor_unitario = ' ROUND( IFNULL( fc_partida_cp.valor_unitario,0),2) ';
        $fc_partida_cp_descuento = ' ROUND( IFNULL(fc_partida_cp.descuento,0 ),2 )';

        $fc_partida_cp_sub_total_base = "ROUND( $fc_partida_cp_cantidad * $fc_partida_cp_valor_unitario, 2 ) ";


        $fc_ligue_partida_factura = " fc_partida_cp.fc_complemento_pago_id = fc_complemento_pago.id ";


        $fc_complemento_pago_sub_total_base = "ROUND((SELECT SUM( $fc_partida_cp_sub_total_base) FROM fc_partida_cp WHERE $fc_ligue_partida_factura),4)";
        $fc_complemento_pago_descuento = "ROUND((SELECT SUM( $fc_partida_cp_descuento ) FROM fc_partida_cp WHERE $fc_ligue_partida_factura),4)";
        $fc_complemento_pago_sub_total = "($fc_complemento_pago_sub_total_base - $fc_complemento_pago_descuento)";


        $fc_partida_cp_operacion = "IFNULL(fc_partida_cp_operacion.cantidad,0) * IFNULL(fc_partida_cp_operacion.valor_unitario,0) - IFNULL(fc_partida_cp_operacion.descuento,0)";
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

        $columnas_extra['fc_complemento_pago_sub_total_base'] = "IFNULL($fc_complemento_pago_sub_total_base,0)";
        $columnas_extra['fc_complemento_pago_descuento'] = "IFNULL($fc_complemento_pago_descuento,0)";
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

        $this->etiqueta = 'Factura';


        $this->key_fc_id = 'fc_complemento_pago_id';


    }

    public function alta_bd(): array|stdClass
    {
        $this->modelo_email = new fc_email_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
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


        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }


}