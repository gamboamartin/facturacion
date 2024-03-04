<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use base\orm\modelo;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\comercial\models\com_tipo_producto;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_conf_retenido extends _base {
    public function __construct(PDO $link){
        $tabla = 'fc_conf_retenido';
        $columnas = array($tabla=>false,'com_producto' => $tabla,'cat_sat_tipo_factor' => $tabla,'cat_sat_factor' => $tabla,
            'cat_sat_tipo_impuesto'=>$tabla);
        $campos_obligatorios = array();

        $campos_view['com_tipo_producto_id'] = array('type' => 'selects', 'model' => new com_tipo_producto($link));
        $campos_view['com_producto_id'] = array('type' => 'selects', 'model' => new com_producto($link));
        $campos_view['cat_sat_tipo_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_factor($link));
        $campos_view['cat_sat_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_factor($link));
        $campos_view['cat_sat_tipo_impuesto_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_impuesto($link));

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Configuracion Retencion';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $registro = $this->init_alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $this->registro = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $this->registro);
        }

        $this->registro = $this->limpia_campos(registro: $this->registro, campos_limpiar: array('com_tipo_producto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $this->registro);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar conf. retenido', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    protected function campos_base(array $data, modelo $modelo, int $id = -1,
                                   array $keys_integra_ds = array('codigo', 'descripcion')): array
    {
        if(!isset($data['descripcion'])){
            $producto =  (new com_producto($this->link))->get_producto(com_producto_id: $data['com_producto_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener producto',data:  $data);
            }

            $data['descripcion'] =  $data['codigo'];
            $data['descripcion'] .=  " ".$producto['com_producto_descripcion'];
        }

        $data = parent::campos_base($data, $modelo, $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        return $data;
    }

    /**
     * Obtiene las configuraciones de retenciones
     * @param int $com_producto_id Producto en ejecucion
     * @return array|stdClass|int
     */
    final public function get_configuraciones(int $com_producto_id): array|stdClass|int
    {
        $filtro['fc_conf_retenido.status'] = 'activo';
        $filtro['com_producto.id'] = $com_producto_id;
        $registro = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al configuraciones de traslado',data:  $registro);
        }

        return $registro;
    }

    public function get_conf_retenido(int $fc_conf_retenido_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_conf_retenido_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener conf. retenido',data:  $registro);
        }

        return $registro;
    }

    private function limpia_campos(array $registro, array $campos_limpiar): array
    {
        foreach ($campos_limpiar as $valor) {
            if (isset($registro[$valor])) {
                unset($registro[$valor]);
            }
        }
        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $conf = $this->get_conf_retenido(fc_conf_retenido_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener conf retenido',data: $conf);
        }

        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $conf["fc_conf_retenido_codigo"];
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro',data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $registro = $this->validaciones(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $registro);
        }

        $registro = $this->limpia_campos(registro: $registro, campos_limpiar: array('com_tipo_producto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar conf. retenido',data:  $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function validaciones(array $data): bool|array
    {
        if(isset($data['status'])){
           return $data;
        }

        $keys = array('descripcion','codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('com_producto_id', 'cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }
}