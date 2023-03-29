<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use PDO;
use stdClass;

class fc_partida extends _base
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_partida';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'com_producto' => $tabla,
            'cat_sat_producto' => 'com_producto', 'cat_sat_unidad' => 'com_producto', 'cat_sat_obj_imp' => 'com_producto');
        $campos_obligatorios = array('codigo', 'com_producto_id');

        $columnas_extra = array();

        $sq_importes = (new _facturacion())->importes_base();
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes', data: $sq_importes);
            print_r($error);
            exit;
        }

        $sq_importe_total_traslado = (new _facturacion())->impuesto_partida(tabla_impuesto: 'fc_traslado');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_traslado', data: $sq_importe_total_traslado);
            print_r($error);
            exit;
        }
        $sq_importe_total_retenido = (new _facturacion())->impuesto_partida(tabla_impuesto: 'fc_retenido');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_retenido', data: $sq_importe_total_retenido);
            print_r($error);
            exit;
        }

        $columnas_extra['fc_partida_importe'] = $sq_importes->fc_partida_importe;
        $columnas_extra['fc_partida_importe_con_descuento'] = $sq_importes->fc_partida_importe_con_descuento;
        $columnas_extra['fc_partida_importe_total_traslado'] = $sq_importe_total_traslado;
        $columnas_extra['fc_partida_importe_total_retenido'] = $sq_importe_total_retenido;
        $columnas_extra['fc_partida_importe_total'] = "$sq_importes->fc_partida_importe_con_descuento 
        + $sq_importe_total_traslado - $sq_importe_total_retenido";

        $columnas_extra['fc_partida_n_traslados'] = "(SELECT COUNT(*) FROM fc_traslado 
        WHERE fc_traslado.fc_partida_id = fc_partida.id)";
        $columnas_extra['fc_partida_n_retenidos'] = "(SELECT COUNT(*) FROM fc_retenido 
        WHERE fc_retenido.fc_partida_id = fc_partida.id)";

        $no_duplicados = array('codigo', 'descripcion_select', 'alias', 'codigo_bis');

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Partida';
    }

    private function acciones_conf_traslado(stdClass $fc_partida): array|stdClass
    {
        $conf_traslados = (new fc_conf_traslado($this->link))->get_configuraciones(
            com_producto_id: $this->registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_traslados);
        }

        if ($conf_traslados->n_registros === 0) {
            return $conf_traslados;
        }

        foreach ($conf_traslados->registros as $configuracion) {
            $traslado = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: "fc_conf_traslado_descripcion", fc_partida: $fc_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos traslados', data: $traslado);
            }

            $alta_traslado = (new fc_traslado($this->link))->alta_registro(registro: $traslado);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta traslados', data: $alta_traslado);
            }
        }

        return $conf_traslados;
    }

    private function acciones_conf_retenido(stdClass $fc_partida): array|stdClass
    {
        $conf_retenidos = (new fc_conf_retenido($this->link))->get_configuraciones(
            com_producto_id: $this->registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_retenidos);
        }

        if ($conf_retenidos->n_registros === 0) {
            return $conf_retenidos;
        }

        foreach ($conf_retenidos->registros as $configuracion) {
            $retenido = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: "fc_conf_retenido_descripcion", fc_partida: $fc_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos retenidos', data: $retenido);
            }

            $alta_retenido = (new fc_retenido($this->link))->alta_registro(registro: $retenido);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta retenidos', data: $alta_retenido);
            }
        }

        return $alta_retenido;
    }


    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro = $this->init_alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $validacion = $this->validaciones(data: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar datos', data: $validacion);
        }
        $data_predial = array();
        if(isset($this->registro['cuenta_predial'])){
            $data_predial['cuenta_predial'] = $this->registro['cuenta_predial'];
            unset($this->registro['cuenta_predial']);
        }

        $this->registro = $this->limpia_campos(registro: $this->registro,
            campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar partida', data: $r_alta_bd);
        }

        $fc_partida = $this->registro(registro_id: $r_alta_bd->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_partida);
        }

        $traslado = $this->acciones_conf_traslado(fc_partida: $fc_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. traslado', data: $traslado);
        }

        $retenido = $this->acciones_conf_retenido(fc_partida: $fc_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. retenido', data: $retenido);
        }

        if(count($data_predial)>0){

            if($fc_partida->com_producto_aplica_predial === 'activo'){
                $data_predial['fc_partida_id'] = $fc_partida->fc_partida_id;

                $r_fc_cuenta_predial = (new fc_cuenta_predial(link: $this->link))->alta_registro(registro: $data_predial);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al insertar predial', data: $r_fc_cuenta_predial);
                }

            }

        }

        return $r_alta_bd;
    }

    /**
     * Calcula los impuestos trasladados de una partida
     * @param int $fc_partida_id Partida a calcular
     * @return float|array
     * @version 4.11.0
     */
    public function calculo_imp_trasladado(int $fc_partida_id):float
    {
        $filtro['fc_partida.id'] = $fc_partida_id;
        $traslado = (new fc_traslado($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $traslado);
        }

        $subtotal = $this->subtotal_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }


        if ((int)$traslado->n_registros > 0) {
            return round($subtotal * (float)$traslado->registros[0]['cat_sat_factor_factor'],2);
        }

        return 0;
    }

    public function calculo_imp_retenido(int $fc_partida_id)
    {
        $filtro['fc_partida.id'] = $fc_partida_id;
        $retenido = (new fc_retenido($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $retenido);
        }

        $subtotal = $this->subtotal_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }

        if ((int)$retenido->n_registros > 0) {
            return round($subtotal * (float)$retenido->registros[0]['cat_sat_factor_factor'],4);
        }

        return 0;
    }

    public function data_partida_obj(int $fc_partida_id): array|stdClass
    {
        $fc_partida = $this->registro(registro_id: $fc_partida_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $fc_partida);
        }

        $data = new stdClass();
        $data->fc_partida = $fc_partida;

        return $data;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $filtro['fc_partida.id'] = $id;
        $r_fc_retenido = (new fc_retenido(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_retenido', $r_fc_retenido);
        }
        $r_fc_traslado = (new fc_traslado(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_traslado', $r_fc_traslado);
        }

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    /**
     * Por mover a base revbios si existe algo asi
     * @param array $registro
     * @param array $campos_limpiar
     * @return array
     */
    private function limpia_campos(array $registro, array $campos_limpiar): array
    {
        foreach ($campos_limpiar as $valor) {
            if (isset($registro[$valor])) {
                unset($registro[$valor]);
            }
        }
        return $registro;
    }

    private function maqueta_datos(array $configuracion, string $conf_descripcion, stdClass $fc_partida): array
    {
        $traslado = array();
        $traslado['descripcion'] = $configuracion[$conf_descripcion];
        $traslado['descripcion'] .= " " . $this->registro['descripcion'];
        $traslado['cat_sat_tipo_factor_id'] = $configuracion['cat_sat_tipo_factor_id'];
        $traslado['cat_sat_factor_id'] = $configuracion['cat_sat_factor_id'];
        $traslado['cat_sat_tipo_impuesto_id'] = $configuracion['cat_sat_tipo_impuesto_id'];
        $traslado['fc_partida_id'] = $fc_partida->fc_partida_id;

        return $traslado;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $partida = $this->get_partida(fc_partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $partida);
        }

        if (!isset($registro['codigo'])) {
            $registro['codigo'] = $partida["fc_partida_codigo"];
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro', data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro, modelo: $this, id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $registro = $this->limpia_campos(registro: $registro,
            campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar partida', data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    public function partidas(int $fc_factura_id, html_controler $html, $hijo = array()): array|stdClass
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;

        $hijo = array();
        $hijo['fc_traslado']['filtros']['fc_partida.id'] = 'fc_partida_id';
        $hijo['fc_traslado']['filtros_con_valor'] = array();
        $hijo['fc_traslado']['nombre_estructura'] = 'fc_traslado';
        $hijo['fc_traslado']['namespace_model'] = 'gamboamartin\\facturacion\\models';

        $hijo['fc_retenido']['filtros']['fc_partida.id'] = 'fc_partida_id';
        $hijo['fc_retenido']['filtros_con_valor'] = array();
        $hijo['fc_retenido']['nombre_estructura'] = 'fc_retenido';
        $hijo['fc_retenido']['namespace_model'] = 'gamboamartin\\facturacion\\models';


        $r_fc_partida = $this->filtro_and(filtro: $filtro, hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        foreach ($r_fc_partida->registros as $indice => $partida) {


            $params = array();
            $params['seccion_retorno'] = 'fc_factura';
            $params['accion_retorno'] = 'modifica';
            $params['id_retorno'] = $fc_factura_id;
            $link_elimina_partida = $html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
                registro_id: $partida['fc_partida_id'], seccion: 'fc_partida', style: 'danger',icon: 'bi bi-trash',
                muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
            if (errores::$error) {
                $error = $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_partida);
                print_r($error);
                die('Error');
            }


            $r_fc_partida->registros[$indice]['elimina_bd'] = $link_elimina_partida;
        }
        return $r_fc_partida;
    }


    private function valida_cantidades(array $data): bool|array
    {
        $keys = array('cantidad');
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return $this->error->error(mensaje: "Error debe de existir: $key", data: $data);
            }

            if ((int)$data[$key] <= 0) {
                return $this->error->error(mensaje: "Error $key no puede ser menor o igual a 0", data: $this->registro);
            }
        }
        return true;
    }

    private function validaciones(array $data): bool|array
    {
        $keys = array('descripcion', 'codigo');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('com_producto_id', 'fc_factura_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: "Error al validar foraneas", data: $valida);
        }

        $valida = $this->valida_cantidades($data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error validar cantidades', data: $valida);
        }

        $com_producto = (new com_producto(link: $this->link))->registro(
            registro_id: $this->registro['com_producto_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener com_producto', data: $com_producto);
        }

        $keys = array('com_producto_aplica_predial');
        $valida = $this->validacion->valida_statuses(keys: $keys,registro:  $com_producto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar com_producto', data: $valida);
        }

        if($com_producto->com_producto_aplica_predial === 'activo'){
            $keys = array('cuenta_predial');

            $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $data);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
            }
        }



        return true;
    }




    // --- FUNCIONES PROBADAS  ---

    /**
     * Obtiene una partida
     * @param int $fc_partida_id Partida a validar
     * @return array|stdClass
     * @version 2.29.0
     */
    public function get_partida(int $fc_partida_id): array|stdClass
    {
        if ($fc_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_partida_id debe ser mayor a 0', data: $fc_partida_id);
        }

        $registro = $this->registro(registro_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $registro);
        }

        return $registro;
    }

    /**
     * Obtiene las partidas de una factura
     * @param int $fc_factura_id Factura a validar
     * @return array
     * @version 1.30.0
     */
    public function get_partidas(int $fc_factura_id): array
    {
        if ($fc_factura_id <= 0) {
            return $this->error->error(mensaje: 'Error $fc_factura_id debe ser mayor a 0', data: $fc_factura_id);
        }

        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_partida = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        return $r_fc_partida->registros;
    }


    /**
     * Calcula el subtotal de una partida
     * @param int $fc_partida_id Partida a validar
     * @return float|array
     * @version 1.30.0
     */
    final public function subtotal_partida(int $fc_partida_id): float|array
    {
        if ($fc_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $fc_partida_id);
        }

        $data = $this->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros de la partida', data: $data);
        }

        return round($data->fc_partida_cantidad * $data->fc_partida_valor_unitario, 4);
    }

    /**
     * Calcula el total de una partida
     * @param int $fc_partida_id Partida a validar
     * @return float|array
     * @version v1.31.0
     */
    public function total_partida(int $fc_partida_id): float|array
    {
        if ($fc_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $fc_partida_id);
        }

        $subtotal = $this->subtotal_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al calcular el subtotal de la partida', data: $subtotal);
        }

        $data = $this->registro(registro_id: $fc_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros de la partida', data: $data);
        }

        return round($subtotal - $data->fc_partida_descuento, 2);
    }
}