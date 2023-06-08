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

class fc_nota_credito extends _transacciones_fc
{

    public function __construct(PDO $link)
    {
        $tabla = 'fc_nota_credito';
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



        $fc_nota_credito_traslados = "(SELECT SUM(fc_partida_nc.total_traslados) FROM fc_partida_nc WHERE fc_partida_nc.fc_nota_credito_id = fc_nota_credito.id)";
        $fc_nota_credito_retenciones = "(SELECT SUM(fc_partida_nc.total_retenciones) FROM fc_partida_nc WHERE fc_partida_nc.fc_nota_credito_id = fc_nota_credito.id)";


        $fc_nota_credito_total = "ROUND(IFNULL($tabla.sub_total,0)+IFNULL(ROUND($fc_nota_credito_traslados,2),0)-IFNULL(ROUND($fc_nota_credito_retenciones,2),0),2)";


        $fc_nota_credito_uuid = "(SELECT IFNULL(fc_cfdi_sellado_nc.uuid,'') FROM fc_cfdi_sellado_nc WHERE fc_cfdi_sellado_nc.fc_nota_credito_id = fc_nota_credito.id)";

        $fc_nota_credito_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_nota_credito_etapa ON fc_nota_credito_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_nota_credito_etapa.fc_nota_credito_id = fc_nota_credito.id ORDER BY fc_nota_credito_etapa.id DESC LIMIT 1)";


        $columnas_extra['fc_nota_credito_descuento'] = "IFNULL($tabla.total_descuento,0)";

        $columnas_extra['fc_nota_credito_traslados'] = "IFNULL($fc_nota_credito_traslados,0)";
        $columnas_extra['fc_nota_credito_retenciones'] = "IFNULL($fc_nota_credito_retenciones,0)";
        $columnas_extra['fc_nota_credito_total'] = "IFNULL($fc_nota_credito_total,0)";
        $columnas_extra['fc_nota_credito_uuid'] = "IFNULL($fc_nota_credito_uuid,'SIN UUID')";
        $columnas_extra['fc_nota_credito_etapa'] = "$fc_nota_credito_etapa";




        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Notas de crédito';


        $this->key_fc_id = 'fc_nota_credito_id';


    }

    public function alta_bd(): array|stdClass
    {
        $this->modelo_email = new fc_email_nc(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_nc(link: $this->link);
        $this->modelo_documento = new fc_nota_credito_documento(link: $this->link);
        $this->modelo_email = new fc_email_nc(link: $this->link);


        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }



}