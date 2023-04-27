<?php

namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\errores\errores;
use stdClass;


class _transacciones_fc extends modelo
{

    public modelo $modelo_etapa;
    protected modelo $modelo_email;

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


    /**
     * Inicializa los datos del emisor para alta
     * @param array $registro Registro en proceso
     * @param stdClass $registro_csd Registro de tipo CSD
     * @return array
     */
    private function default_alta_emisor_data(array $registro, stdClass $registro_csd): array
    {
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

    private function descripcion_select_default(array    $registro, stdClass $registro_csd,
                                                stdClass $registro_com_sucursal): string
    {
        $descripcion_select = $registro['folio'] . ' ';
        $descripcion_select .= $registro_csd->org_empresa_razon_social . ' ';
        $descripcion_select .= $registro_com_sucursal->com_cliente_razon_social;
        return $descripcion_select;
    }

    /**
     * Limpia los parametros de una factura
     * @param array $registro registro en proceso
     * @return array
     * @version 0.127.26
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
     * @version 0.115.26
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

    private function ultimo_folio(int $fc_csd_id){
        $filtro['fc_csd.id'] = $fc_csd_id;
        $r_registro = $this->filtro_and(filtro: $filtro, limit: 1,order: array($this->tabla.'.folio'=>'DESC'));
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_registro', data: $r_registro);
        }


        $fc_csd = (new fc_csd(link: $this->link))->registro(registro_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener csd', data: $fc_csd);
        }

        $fc_csd_serie = $fc_csd['fc_csd_serie'];

        $number_folio = 1;
        if((int)$r_registro->n_registros > 0){
            $fc_factura = $r_registro->registros[0];

            $fc_folio = $fc_factura['fc_factura_folio'];
            $data_explode = $fc_csd_serie.'-';
            $fc_folio_explode = explode($data_explode, $fc_folio);
            if(isset($fc_folio_explode[1])){
                if(is_numeric($fc_folio_explode[1])){
                    $number_folio = (int)$fc_folio_explode[1] + 1;
                }
            }
        }

        $long_nf = strlen($number_folio);

        $n_ceros = 6;

        $i = $long_nf;
        $folio_str = '';
        while($i<$n_ceros){
            $folio_str.='0';
            $i++;
        }
        $folio_str.=$number_folio;


        return $fc_csd_serie.'-'.$folio_str;

    }


}
