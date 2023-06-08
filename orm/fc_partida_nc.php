<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_partida_nc extends _partida
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_partida_nc';
        $columnas = array($tabla => false, 'fc_nota_credito' => $tabla, 'com_producto' => $tabla,
            'cat_sat_producto' => 'com_producto', 'cat_sat_unidad' => 'com_producto', 'cat_sat_obj_imp' => 'com_producto');
        $campos_obligatorios = array('codigo', 'com_producto_id');

        $columnas_extra = array();

        $sq_importes = (new _facturacion())->importes_base(name_entidad_partida: 'fc_partida_nc');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes', data: $sq_importes);
            print_r($error);
            exit;
        }

        $sq_importe_total_traslado = (new _facturacion())->impuesto_partida(
            name_entidad_partida: 'fc_partida_nc', tabla_impuesto: 'fc_traslado_nc');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_traslado', data: $sq_importe_total_traslado);
            print_r($error);
            exit;
        }
        $sq_importe_total_retenido = (new _facturacion())->impuesto_partida(
            name_entidad_partida: 'fc_partida_nc', tabla_impuesto: 'fc_retenido_nc');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_retenido', data: $sq_importe_total_retenido);
            print_r($error);
            exit;
        }

        // print_r($sq_importes);exit;
        $columnas_extra['fc_partida_nc_importe'] = "$tabla.sub_total_base";
        $columnas_extra['fc_partida_nc_importe_con_descuento'] = $sq_importes->fc_partida_entidad_importe_con_descuento;
        $columnas_extra['fc_partida_nc_importe_total_traslado'] = $sq_importe_total_traslado;
        $columnas_extra['fc_partida_nc_importe_total_retenido'] = $sq_importe_total_retenido;
        $columnas_extra['fc_partida_nc_importe_total'] = "$sq_importes->fc_partida_entidad_importe_con_descuento 
        + $sq_importe_total_traslado - $sq_importe_total_retenido";

        $columnas_extra['fc_partida_nc_n_traslados'] = "(SELECT COUNT(*) FROM fc_traslado_nc 
        WHERE fc_traslado_nc.fc_partida_nc_id = fc_partida_nc.id)";
        $columnas_extra['fc_partida_nc_n_retenidos'] = "(SELECT COUNT(*) FROM fc_retenido_nc 
        WHERE fc_retenido_nc.fc_partida_nc_id = fc_partida_nc.id)";

        $no_duplicados = array('codigo', 'descripcion_select', 'alias', 'codigo_bis');


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas,
            columnas_extra: $columnas_extra, no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Partida';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_retencion = new fc_retenido_nc(link: $this->link);
        $this->modelo_traslado = new fc_traslado_nc(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_nc(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_retencion = new fc_retenido_nc(link: $this->link);
        $this->modelo_traslado = new fc_traslado_nc(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_nc(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}