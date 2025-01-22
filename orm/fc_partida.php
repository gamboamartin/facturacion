<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_partida extends _partida
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_partida';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'com_producto' => $tabla,'cat_sat_obj_imp'=>$tabla,
            'cat_sat_producto' => 'com_producto', 'cat_sat_unidad' => 'com_producto',
            'com_sucursal'=>'fc_factura','com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('codigo', 'com_producto_id');

        $columnas_extra = array();

        $columnas_extra['fc_partida_n_traslados'] = "(SELECT COUNT(*) FROM fc_traslado 
        WHERE fc_traslado.fc_partida_id = fc_partida.id)";
        $columnas_extra['fc_partida_n_retenidos'] = "(SELECT COUNT(*) FROM fc_retenido 
        WHERE fc_retenido.fc_partida_id = fc_partida.id)";

        $no_duplicados = array('codigo', 'descripcion_select', 'alias', 'codigo_bis');

        $atributos_criticos = array('sub_total','total_traslados','total_retenciones','sub_total_base','total');


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas,
            columnas_extra: $columnas_extra, no_duplicados: $no_duplicados, atributos_criticos: $atributos_criticos);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Partida';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}