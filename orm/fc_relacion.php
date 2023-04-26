<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_relacion extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_relacion';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'cat_sat_tipo_relacion' => $tabla);
        $campos_obligatorios = array('fc_factura_id', 'cat_sat_tipo_relacion_id');

        $columnas_extra = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas Relacionadas';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $this->registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener factura', data: $fc_factura);
        }

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $this->registro['fc_factura_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $cat_sat_tipo_relacion = (new cat_sat_tipo_relacion(link: $this->link))->registro(registro_id: $this->registro['cat_sat_tipo_relacion_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener cat_sat_tipo_relacion', data: $cat_sat_tipo_relacion);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_factura['fc_factura_folio'].' '.$cat_sat_tipo_relacion['cat_sat_tipo_relacion_descripcion'];
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta registro', data: $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $fc_relacion = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $fc_relacion);
        }

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_relacion->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $filtro['fc_relacion.id'] = $id;
        $del = (new fc_factura_relacionada(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $del);
        }
        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    /**
     * Obtiene las facturas relacionadas
     * @param array $fc_relacion Relacion Base
     * @return array
     * @version 7.19.3
     */
    final public function facturas_relacionadas(array $fc_relacion): array
    {
        $keys = array('fc_relacion_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $fc_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar $fc_relacion', data: $valida);
        }
        $filtro = array();
        $filtro['fc_relacion.id'] = $fc_relacion['fc_relacion_id'];
        $r_fc_factura_relacionada = (new fc_factura_relacionada(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_factura_relacionada', data: $r_fc_factura_relacionada);
        }

        return $r_fc_factura_relacionada->registros;
    }


    private function genera_relacionado(array $fc_relacion, array $relacionados){
        $cat_sat_tipo_relacion_codigo = $fc_relacion['cat_sat_tipo_relacion_codigo'];
        $relacionados[$cat_sat_tipo_relacion_codigo] = array();

        $fc_facturas_relacionadas = $this->facturas_relacionadas(fc_relacion: $fc_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_facturas_relacionadas', data: $fc_facturas_relacionadas);
        }

        $relacionados = $this->integra_relacionados(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            fc_facturas_relacionadas: $fc_facturas_relacionadas, relacionados: $relacionados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }
        return $relacionados;
    }

    final public function get_relaciones(int $fc_factura_id){


        $fc_relaciones = $this->relaciones(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_relaciones', data: $fc_relaciones);
        }
        $relacionados = $this->relacionados(fc_relaciones: $fc_relaciones);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }

        return $relacionados;

    }

    /**
     *
     * @param string $cat_sat_tipo_relacion_codigo
     * @param array $fc_factura_relacionada
     * @param array $relacionados
     * @return array
     */
    private function integra_relacionado(string $cat_sat_tipo_relacion_codigo, array $fc_factura_relacionada,
                                         array $relacionados): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        $keys = array('fc_factura_uuid');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro: $fc_factura_relacionada);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_factura_relacionada', data: $valida);
        }
        $relacionados[$cat_sat_tipo_relacion_codigo][] = $fc_factura_relacionada['fc_factura_uuid'];
        return $relacionados;
    }

    private function integra_relacionados(string $cat_sat_tipo_relacion_codigo, array $fc_facturas_relacionadas,
                                          array $relacionados){
        foreach ($fc_facturas_relacionadas as $fc_factura_relacionada){
            $relacionados = $this->integra_relacionado(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
                fc_factura_relacionada: $fc_factura_relacionada, relacionados: $relacionados);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
            }
        }
        return $relacionados;
    }

    final public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $fc_relacion = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $fc_relacion);
        }

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_relacion->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_modifica_bd =  parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

    /**
     * Obtiene las relaciones de una factura
     * @param int $fc_factura_id Factura a obtener datos
     * @return array
     * @version 6.33.0
     */
    final public function relaciones(int $fc_factura_id): array
    {
        $filtro = array();
        $filtro['fc_factura.id'] = $fc_factura_id;

        $r_fc_relacion = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $r_fc_relacion);
        }
        return $r_fc_relacion->registros;
    }

    private function relacionados(array $fc_relaciones){
        $relacionados = array();
        foreach ($fc_relaciones as $fc_relacion){
            $relacionados = $this->genera_relacionado(fc_relacion: $fc_relacion,relacionados:  $relacionados);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
            }
        }
        return $relacionados;
    }


}