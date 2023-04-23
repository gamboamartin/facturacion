<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use base\orm\modelo;
use gamboamartin\facturacion\models\fc_relacion_nc;
/*use gamboamartin\facturacion\models\fc_cfdi;*/
use gamboamartin\errores\errores;
use PDO;
use stdClass;
class fc_uuid_rel_nc extends _base {
    public function __construct(PDO $link){
        $tabla = 'fc_uuid_rel_nc';
        $columnas = array($tabla=>false, /*'fc_cfdi' => $tabla,*/'fc_relacion_nc' => $tabla, );
        $campos_obligatorios = array();

        /*$campos_view['fc_cfdi_id'] = array('type' => 'selects', 'model' => new fc_cfdi($link));*/
        $campos_view['fc_relacion_nc_id'] = array('type' => 'selects', 'model' => new fc_relacion_nc($link));

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Configuracion Nota credito';
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

        $this->registro = $this->limpia_campos(registro: $this->registro, campos_limpiar: array());
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $this->registro);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar nota credito', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    protected function campos_base(array $data, modelo $modelo, int $id = -1,
                                   array $keys_integra_ds = array('codigo', 'descripcion')): array
    {
        if(!isset($data['descripcion'])){
            $csd =  (new fc_relacion_nc($this->link))->get_relacion_nc(fc_relacion_nc_id: $data['fc_relacion_nc_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener producto',data:  $data);
            }

            $data['descripcion'] =  $data['codigo'];
            $data['descripcion'] .=  " ".$csd['fc_relacion_nc_id'];
        }

        $data = parent::campos_base($data, $modelo, $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        return $data;
    }
    public function get_configuraciones(int $fc_csd): array|stdClass|int
    {
        $filtro['fc_relacion_nc'] = 'activo';
        $filtro['fc_relacion_nc'] = $fc_csd;
        $registro = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al configuraciones de traslado',data:  $registro);
        }

        return $registro;
    }

    public function get_uuid_rel_nc(int $fc_uuid_rel_nc_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_uuid_rel_nc_id);
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
        $conf = $this->get_uuid_rel_nc(fc_uuid_rel_nc_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener conf retenido',data: $conf);
        }

        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $conf["fc_uuid_rel_nc_codigo"];
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

        $registro = $this->limpia_campos(registro: $registro, campos_limpiar: array());
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

        $keys = array(/*'fc_cfdi_id',*/'fc_relacion_nc_id',);
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }
}