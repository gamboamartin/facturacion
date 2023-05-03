<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\cat_sat\models\cat_sat_conf_imps;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use PDO;
use stdClass;

class _partida extends  _base{

    protected _data_impuestos|stdClass $modelo_retencion;
    protected _data_impuestos|stdClass $modelo_traslado;

    protected _transacciones_fc|stdClass $modelo_entidad;
    protected _cuenta_predial|stdClass $modelo_predial;
    public function __construct(PDO $link, string $tabla, _transacciones_fc|stdClass $modelo_entidad = new stdClass(),
                                _cuenta_predial|stdClass $modelo_predial = new stdClass(),
                                _data_impuestos|stdClass $modelo_retencion = new stdClass(),
                                _data_impuestos|stdClass $modelo_traslado = new stdClass(),
                                bool $aplica_bitacora = false, bool $aplica_seguridad = false,
                                bool $aplica_transaccion_inactivo = true, array $campos_encriptados = array(),
                                array $campos_obligatorios = array(), array $columnas = array(),
                                array $campos_view = array(), array $columnas_extra = array(),
                                array $extension_estructura = array(), array $no_duplicados = array(),
                                array $renombres = array(), array $sub_querys = array(), array $tipo_campos = array(),
                                bool $validation = false, array $campos_no_upd = array(), array $parents = array(),
                                bool $temp = false, array $childrens = array(), array $defaults = array(),
                                array $parents_data = array(), array $atributos_criticos = array())
    {



        parent::__construct(link: $link,tabla:  $tabla,aplica_bitacora:  $aplica_bitacora,
            aplica_seguridad:  $aplica_seguridad,aplica_transaccion_inactivo:  $aplica_transaccion_inactivo,
            campos_encriptados: $campos_encriptados,campos_obligatorios:  $campos_obligatorios,columnas:  $columnas,
            campos_view:  $campos_view,columnas_extra:  $columnas_extra,extension_estructura:  $extension_estructura,
            no_duplicados: $no_duplicados,renombres:  $renombres,sub_querys:  $sub_querys,tipo_campos:  $tipo_campos,
            validation: $validation,campos_no_upd:  $campos_no_upd,parents:  $parents,temp:  $temp,
            childrens: $childrens,defaults:  $defaults, parents_data: $parents_data,
            atributos_criticos:  $atributos_criticos);

        $this->modelo_retencion = $modelo_retencion;
        $this->modelo_traslado = $modelo_traslado;
        $this->modelo_entidad = $modelo_entidad;
        $this->modelo_predial = $modelo_predial;

    }

    private function acciones_conf_retenido(bool $aplica_cat_sat_conf_imps, int $cat_sat_conf_imps_id,
                                            stdClass $fc_registro_partida): array|stdClass
    {
        $conf_retenidos = (new fc_conf_retenido($this->link))->get_configuraciones(
            com_producto_id: $this->registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. retenciones', data: $conf_retenidos);
        }
        $conf_descripcion = 'fc_conf_retenido_descripcion';
        if($aplica_cat_sat_conf_imps){
            $conf_retenidos->registros = (new cat_sat_conf_imps(link: $this->link))->get_retenciones(
                cat_sat_conf_imps_id: $cat_sat_conf_imps_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener conf. retenciones', data: $conf_retenidos);
            }
            $conf_retenidos->registros = $conf_retenidos->registros;
            $conf_retenidos->n_registros = count($conf_retenidos->registros);
            $conf_descripcion = 'cat_sat_retencion_conf_descripcion';
        }

        if ($conf_retenidos->n_registros === 0) {
            return $conf_retenidos;
        }

        foreach ($conf_retenidos->registros as $configuracion) {
            $retenido = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: $conf_descripcion, fc_registro_partida: $fc_registro_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos retenidos', data: $retenido);
            }

            $class = get_class($this->modelo_retencion);
            if($class === 'stdClass'){
                $this->modelo_retencion = new fc_retenido(link: $this->link);
            }
            $alta_retenido = $this->modelo_retencion->alta_registro(registro: $retenido);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta retenidos', data: $alta_retenido);
            }
        }

        return $alta_retenido;
    }

    private function acciones_conf_traslado(bool $aplica_cat_sat_conf_imps, int $cat_sat_conf_imps_id,
                                            stdClass $fc_registro_partida): array|stdClass
    {
        $conf_traslados = (new fc_conf_traslado($this->link))->get_configuraciones(
            com_producto_id: $this->registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_traslados);
        }
        $conf_descripcion = 'fc_conf_traslado_descripcion';
        if($aplica_cat_sat_conf_imps){
            $conf_traslados->registros = (new cat_sat_conf_imps(link: $this->link))->get_traslados(
                cat_sat_conf_imps_id: $cat_sat_conf_imps_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_traslados);
            }
            $conf_traslados->n_registros = count($conf_traslados->registros);
            $conf_descripcion = 'cat_sat_traslado_conf_descripcion';
        }

        if ($conf_traslados->n_registros === 0) {
            return $conf_traslados;
        }

        foreach ($conf_traslados->registros as $configuracion) {
            $traslado = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: $conf_descripcion, fc_registro_partida: $fc_registro_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos traslados', data: $traslado);
            }

            $class = get_class($this->modelo_traslado);
            if($class === 'stdClass'){
                $this->modelo_traslado = new fc_traslado(link: $this->link);
            }

            $alta_traslado = $this->modelo_traslado->alta_registro(registro: $traslado);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta traslados', data: $alta_traslado);
            }
        }

        return $conf_traslados;
    }


    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $class = get_class($this->modelo_entidad);
        if($class === 'stdClass'){
            $this->modelo_entidad = new fc_factura(link: $this->link);
        }

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(
            registro_id: $this->registro[$this->modelo_entidad->tabla.'_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

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

        $aplica_cat_sat_conf_imps = false;
        $cat_sat_conf_imps_id = -1;
        if(isset($this->registro['cat_sat_conf_imps_id']) && $this->registro['cat_sat_conf_imps_id']>0){
            $cat_sat_conf_imps_id = $this->registro['cat_sat_conf_imps_id'];
            $aplica_cat_sat_conf_imps = true;
        }


        $r_alta_bd = parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar partida', data: $r_alta_bd);
        }

        $fc_registro_partida = $this->registro(registro_id: $r_alta_bd->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }


        $traslado = $this->acciones_conf_traslado(aplica_cat_sat_conf_imps: $aplica_cat_sat_conf_imps,
            cat_sat_conf_imps_id: $cat_sat_conf_imps_id, fc_registro_partida: $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. traslado', data: $traslado);
        }

        $retenido = $this->acciones_conf_retenido(aplica_cat_sat_conf_imps: $aplica_cat_sat_conf_imps,
            cat_sat_conf_imps_id: $cat_sat_conf_imps_id,fc_registro_partida: $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. retenido', data: $retenido);
        }


        if(count($data_predial)>0){

            if($fc_registro_partida->com_producto_aplica_predial === 'activo'){
                $key_id = $this->tabla.'_id';
                $data_predial[$key_id] = $fc_registro_partida->$key_id;

                $r_fc_cuenta_predial = $this->modelo_predial->alta_registro(registro: $data_predial);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al insertar predial', data: $r_fc_cuenta_predial);
                }

            }

        }

        return $r_alta_bd;
    }

    public function calculo_imp_retenido(int $registro_partida_id)
    {
        $filtro[$this->key_filtro_id] = $registro_partida_id;
        $retenido = $this->modelo_retencion->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $retenido);
        }

        $subtotal = $this->subtotal_partida(registro_partida_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }

        if ((int)$retenido->n_registros > 0) {
            return round($subtotal * (float)$retenido->registros[0]['cat_sat_factor_factor'],2);
        }

        return 0;
    }

    /**
     * Calcula los impuestos trasladados de una partida
     * @param int $registro_partida_id Partida a calcular
     * @return float|array
     */
    public function calculo_imp_trasladado(int $registro_partida_id):float
    {
        $filtro[$this->key_filtro_id] = $registro_partida_id;

        $class = get_class($this->modelo_traslado);
        if($class === 'stdClass'){
            $this->modelo_traslado = new fc_traslado(link: $this->link);
        }

        $traslado = $this->modelo_traslado->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $traslado);
        }

        $subtotal = $this->subtotal_partida(registro_partida_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }


        if ((int)$traslado->n_registros > 0) {
            return round($subtotal * (float)$traslado->registros[0]['cat_sat_factor_factor'],2);
        }

        return 0;
    }

    public function data_partida_obj(int $registro_partida_id): array|stdClass
    {
        $fc_partida = $this->registro(registro_id: $registro_partida_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $fc_partida);
        }

        $data = new stdClass();
        $key = $this->tabla;
        $data->$key = $fc_partida;

        return $data;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $fc_partida = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener fc_partida', data: $fc_partida);
        }
        $key_entidad_id = $this->modelo_entidad->tabla.'_id';
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(registro_id: $fc_partida->$key_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $filtro[$this->tabla.'.id'] = $id;
        $r_fc_retenido = $this->modelo_retencion->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_retenido', $r_fc_retenido);
        }
        $r_fc_traslado = $this->modelo_traslado->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_traslado', $r_fc_traslado);
        }
        $r_fc_cuenta_predial = $this->modelo_predial->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_fc_cuenta_predial);
        }

        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    /**
     * Obtiene una partida
     * @param int $registro_partida_id Partida a validar
     * @return array|stdClass
     */
    final public function get_partida(int $registro_partida_id): array|stdClass
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_partida_id debe ser mayor a 0', data: $registro_partida_id);
        }

        $registro = $this->registro(registro_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $registro);
        }

        return $registro;
    }

    /**
     * Obtiene las partidas de una factura
     * @param int $registro_entidad_id Factura a validar
     * @return array
     */
    public function get_partidas(int $registro_entidad_id): array
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0', data: $registro_entidad_id);
        }

        $class = get_class($this->modelo_entidad);
        if($class === 'stdClass'){
            $this->modelo_entidad = new fc_factura(link: $this->link);
        }

        $filtro[$this->modelo_entidad->key_filtro_id] = $registro_entidad_id;
        $r_fc_partida = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        return $r_fc_partida->registros;
    }

    private function hijo_retenido(array $hijo): array
    {
        $hijo[$this->modelo_retencion->tabla]['filtros'][$this->key_filtro_id] = $this->key_id;
        $hijo[$this->modelo_retencion->tabla]['filtros_con_valor'] = array();
        $hijo[$this->modelo_retencion->tabla]['nombre_estructura'] = $this->modelo_retencion->tabla;
        $hijo[$this->modelo_retencion->tabla]['namespace_model'] = 'gamboamartin\\facturacion\\models';
        return $hijo;
    }

    /**
     * Maqueta el elemento para un children de factura
     * @param array $hijo Hijo a maquetar
     * @return array
     * @version 8.47.3
     */
    private function hijo_traslado(array $hijo): array
    {
        if(!isset($this->modelo_traslado->tabla)){
            return $this->error->error(mensaje: 'Error no existe tabla definida traslado', data: $this->modelo_traslado);
        }

        $tabla = trim($this->modelo_traslado->tabla);
        if($tabla === ''){
            return $this->error->error(mensaje: 'Error la tabla esta vacia', data: $tabla);
        }

        $hijo[$this->modelo_traslado->tabla]['filtros'][$this->key_filtro_id] = $this->key_id;
        $hijo[$this->modelo_traslado->tabla]['filtros_con_valor'] = array();
        $hijo[$this->modelo_traslado->tabla]['nombre_estructura'] = $this->modelo_traslado->tabla;
        $hijo[$this->modelo_traslado->tabla]['namespace_model'] = 'gamboamartin\\facturacion\\models';
        return $hijo;
    }

    private function hijos_partida(){
        $hijo = array();

        $hijo = $this->hijo_traslado(hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }
        $hijo = $this->hijo_retenido(hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }
        return $hijo;
    }

    private function integra_button_partida(html_controler $html, int $indice, array $partida,
                                            stdClass $r_fc_registro_partida, int $registro_entidad_id){

        $params = $this->params_button_partida(registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener params', data: $params);
        }
        $link_elimina_partida = $html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
            registro_id: $partida[$this->key_id], seccion: $this->tabla, style: 'danger',icon: 'bi bi-trash',
            muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_partida);
        }
        $r_fc_registro_partida->registros[$indice]['elimina_bd'] = $link_elimina_partida;
        return $r_fc_registro_partida;
    }

    private function integra_buttons_partida( array $filtro, array $hijo, html_controler $html,
                                              int $registro_entidad_id){
        $r_fc_partida = $this->filtro_and(filtro: $filtro, hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        foreach ($r_fc_partida->registros as $indice => $partida) {
            $r_fc_partida = $this->integra_button_partida(html: $html, indice: $indice, partida: $partida,
                r_fc_registro_partida: $r_fc_partida, registro_entidad_id: $registro_entidad_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $r_fc_partida);
            }
        }
        return $r_fc_partida;
    }

    /**
     * Por mover a base previos si existe algo asi
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
    private function maqueta_datos(array $configuracion, string $conf_descripcion, stdClass $fc_registro_partida): array
    {
        $traslado = array();
        $traslado['descripcion'] = $configuracion[$conf_descripcion];
        $traslado['descripcion'] .= " " . $this->registro['descripcion'];
        $traslado['cat_sat_tipo_factor_id'] = $configuracion['cat_sat_tipo_factor_id'];
        $traslado['cat_sat_factor_id'] = $configuracion['cat_sat_factor_id'];
        $traslado['cat_sat_tipo_impuesto_id'] = $configuracion['cat_sat_tipo_impuesto_id'];
        $key_id = $this->tabla.'_id';
        $traslado[$key_id] = $fc_registro_partida->$key_id;

        return $traslado;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $partida = $this->get_partida(registro_partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $partida);
        }

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(registro_id: $this->modelo_entidad->key_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        if (!isset($registro['codigo'])) {
            $registro['codigo'] = $partida[$this->tabla."_codigo"];
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

    final public function params_button_partida(int $registro_entidad_id): array
    {
        if(!isset($this->modelo_entidad->tabla)){
            return $this->error->error(mensaje: 'Error al no esta la tabla definida de la entidad',
                data: $this->modelo_entidad);
        }
        $params = array();
        $params['seccion_retorno'] = $this->modelo_entidad->tabla;
        $params['accion_retorno'] = 'modifica';
        $params['id_retorno'] = $registro_entidad_id;
        return $params;
    }

    public function partidas( html_controler $html, int $registro_entidad_id, $hijo = array()): array|stdClass
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0', data: $registro_entidad_id);
        }

        $filtro[$this->modelo_entidad->key_filtro_id] = $registro_entidad_id;
        $hijo = $this->hijos_partida();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }


        $r_fc_partida = $this->integra_buttons_partida(filtro: $filtro, hijo: $hijo,
            html: $html, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $r_fc_partida);
        }

        return $r_fc_partida;
    }

    /**
     * Calcula el subtotal de una partida
     * @param int $registro_partida_id Partida a validar
     * @return float|array
     */
    final public function subtotal_partida(int $registro_partida_id): float|array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $registro_partida_id);
        }

        $data = $this->registro(registro_id: $registro_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros de la partida', data: $data);
        }

        $key_cantidad = $this->tabla.'_cantidad';
        $key_valor_unitario = $this->tabla.'_valor_unitario';

        return round($data->$key_cantidad * $data->$key_valor_unitario, 4);
    }

    /**
     * Calcula el total de una partida
     * @param int $registro_partida_id Partida a validar
     * @return float|array
     */
    public function total_partida(int $registro_partida_id): float|array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $registro_partida_id);
        }

        $subtotal = $this->subtotal_partida(registro_partida_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al calcular el subtotal de la partida', data: $subtotal);
        }

        $data = $this->registro(registro_id: $registro_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros de la partida', data: $data);
        }

        $key_descuento = $this->tabla.'_descuento';
        return round($subtotal - $data->$key_descuento, 2);
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

        $keys = array('com_producto_id', $this->modelo_entidad->tabla.'_id');
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

}