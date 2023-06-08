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


        $fc_partida_sub_total_base = "ROUND( $fc_partida_cantidad * $fc_partida_valor_unitario, 2 ) ";


        $fc_ligue_partida_factura = " fc_partida.fc_factura_id = fc_factura.id ";


        $fc_factura_sub_total_base = "ROUND((SELECT SUM( $fc_partida_sub_total_base) FROM fc_partida WHERE $fc_ligue_partida_factura),4)";

        $fc_factura_sub_total = "($fc_factura_sub_total_base - $tabla.total_descuento)";


        $fc_partida_operacion = "IFNULL(fc_partida_operacion.cantidad,0) * IFNULL(fc_partida_operacion.valor_unitario,0) - IFNULL(fc_partida_operacion.descuento,0)";
        $where_pc_partida_operacion = "fc_partida_operacion.fc_factura_id = fc_factura.id AND fc_partida_operacion.id = fc_partida.id";

        $from_impuesto = $this->from_impuesto(entidad_partida: 'fc_partida', tipo_impuesto: 'fc_traslado');
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

        $from_impuesto = $this->from_impuesto(entidad_partida: 'fc_partida', tipo_impuesto: 'fc_retenido');
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

        $columnas_extra['fc_factura_descuento'] = "IFNULL($tabla.total_descuento,0)";

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


        $this->key_fc_id = 'fc_factura_id';


    }

    public function alta_bd(): array|stdClass
    {
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);


        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

    final public function n_parcialidades(int $fc_factura_id){
        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['fc_docto_relacionado.status'] = 'activo';
        $filtro['fc_factura.status'] = 'activo';
        $filtro['fc_complemento_pago.status'] = 'activo';
        $n_parcialidades = (new fc_docto_relacionado(link: $this->link))->cuenta(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener parcialidades',data:  $n_parcialidades);
        }
        return $n_parcialidades;

    }

    final public function saldo(int $fc_factura_id){

        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }
        $total_pagos = $this->total_pagos(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener total de pagos',data:  $total_pagos);
        }

        $saldo = round($fc_factura['fc_factura_total'],2) - round($total_pagos,2);
        return round($saldo,2);

    }

    final public function total_pagos(int $fc_factura_id){

        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['fc_docto_relacionado.status'] = 'activo';
        $filtro['fc_factura.status'] = 'activo';
        $filtro['fc_complemento_pago.status'] = 'activo';
        $campos['total_pagos'] = 'fc_docto_relacionado.imp_pagado';
        $r_fc_docto_relacionado = (new fc_docto_relacionado(link: $this->link))->suma(campos: $campos, filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pagos',data:  $r_fc_docto_relacionado);
        }
        return round($r_fc_docto_relacionado['total_pagos'],2);

    }


}