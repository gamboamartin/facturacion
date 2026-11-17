<?php
namespace gamboamartin\administrador\models;
use base\orm\_modelo_children;
use base\orm\modelo;
use config\generales;
use gamboamartin\errores\errores;


use PDO;
use stdClass;

class adm_seccion extends _modelo_children {
    /**
     * DEBUG INI
     * seccion_menu constructor.
     * @param PDO $link
     * @param array $childrens
     * @param array $columnas_extra
     */
    public function __construct(PDO $link, array $childrens = array(), array $columnas_extra = array()){
        $tabla = 'adm_seccion';
        $columnas = array($tabla=>false, 'adm_menu'=>$tabla,'adm_namespace'=>$tabla);
        $campos_obligatorios = array('status','descripcion','adm_menu_id', 'adm_namespace_id','etiqueta_label');

        $parents_data['adm_menu'] = array();
        $parents_data['adm_menu']['namespace'] = 'gamboamartin\\administrador\\models';
        $parents_data['adm_menu']['registro_id'] = -1;
        $parents_data['adm_menu']['keys_parents'] = array('adm_menu_codigo');
        $parents_data['adm_menu']['key_id'] = 'adm_menu_id';

        $parents_data['adm_namespace'] = array();
        $parents_data['adm_namespace']['namespace'] = 'gamboamartin\\administrador\\models';
        $parents_data['adm_namespace']['registro_id'] = -1;
        $parents_data['adm_namespace']['keys_parents'] = array('adm_namespace_descripcion');
        $parents_data['adm_namespace']['key_id'] = 'adm_namespace_id';


        $childrens['adm_accion'] = "gamboamartin\\administrador\\models";
        $childrens['adm_bitacora'] = "gamboamartin\\administrador\\models";
        $childrens['adm_seccion_pertenece'] = "gamboamartin\\administrador\\models";
        $childrens['adm_campo'] = "gamboamartin\\administrador\\models";

        $columnas_extra['adm_seccion_n_acciones'] = /** @lang sql */
            "(SELECT COUNT(*) FROM adm_accion WHERE adm_accion.adm_seccion_id = adm_seccion.id)";

            $atributos_criticos = array('adm_namespace_id');


        parent::__construct(link: $link,tabla:  $tabla,campos_obligatorios: $campos_obligatorios,columnas:  $columnas,
            columnas_extra: $columnas_extra, childrens: $childrens, parents_data: $parents_data,
            atributos_criticos: $atributos_criticos);
        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Seccion';

    }

    private function accion_base_alta(array $accion_basica){
        $campos = array('descripcion','icono','visible','seguridad','inicio','lista','es_lista','status','es_status',
            'es_view','muestra_icono_btn','muestra_titulo_btn','css');

        $accion = $this->accion_maqueta_campos(accion_basica: $accion_basica,campos:  $campos);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar campo',data:  $accion);
        }
        return $accion;
    }

    private function accion_maqueta(array $accion_basica, int $registro_id): array
    {

        $accion = $this->accion_base_alta(accion_basica: $accion_basica);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar campo',data:  $accion);
        }
        $accion['adm_seccion_id'] = $registro_id;
        $accion['titulo'] =ucwords($accion_basica['adm_accion_basica_titulo']);

        return $accion;
    }

    /**
     * Maqueta un campo de accion para integrar en alta
     * @param array $accion Accion precargada para insersion
     * @param array $accion_basica Accion basica
     * @param string $key Campo a integrar en accion
     * @return array
     * @version 4.7.0
     */
    private function accion_maqueta_campo(array $accion, array $accion_basica, string $key): array
    {
        $key = trim($key);
        if($key === ''){
            return $this->error->error(mensaje: 'Error key esta vacio',data:  $key);
        }
        $key_ab = "adm_accion_basica_$key";

        $keys = array($key_ab);
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $accion_basica,valida_vacio: false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al valida $accion_basica',data:  $valida);
        }

        $accion[$key] = $accion_basica[$key_ab];
        return $accion;
    }

    /**
     * Maqueta en un array los campos para insertar un accion
     * @param array $accion_basica Accion base
     * @param array $campos Campos a integrar
     * @return array
     */
    private function accion_maqueta_campos(array $accion_basica, array $campos): array
    {
        $accion = array();
        foreach ($campos as $campo){
            $accion = $this->accion_maqueta_campo(accion: $accion,accion_basica:  $accion_basica, key: $campo);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al maquetar campo',data:  $accion);
            }
        }
        return $accion;
    }

    /**
     * Obtiene las acciones de una seccion
     * @param int $adm_seccion_id Seccion identificador
     * @return array
     * @version 2.47.4
     */
    final public function acciones(int $adm_seccion_id): array
    {
        if($adm_seccion_id <= 0){
            return $this->error->error(mensaje: 'Error adm_seccion_id debe ser mayor a 0',data:  $adm_seccion_id);
        }
        $filtro['adm_seccion.id'] = $adm_seccion_id;
        $r_adm_accion = (new adm_accion($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener acciones',data:  $r_adm_accion);
        }
        return $r_adm_accion->registros;
    }
    final public function acciones_permitidas(int $grupo_id, string $seccion, array $filtro_extra = array()): array
    {
        if($grupo_id <= 0){
            return $this->error->error(mensaje: 'Error grupo_id es menor a 0',data:  $grupo_id);
        }
        $seccion = trim($seccion);
        if($seccion === ''){
            return $this->error->error(mensaje: 'Error seccion esta vacia',data:  $seccion);
        }
        $filtro['adm_grupo.id'] = $grupo_id;
        $filtro['adm_seccion.descripcion'] = $seccion;

        foreach ($filtro_extra as $campo=>$value){
            $filtro[$campo] = $value;
        }

        $r_accion_grupo = (new adm_accion_grupo(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener accion',data:  $r_accion_grupo);
        }
        return $r_accion_grupo->registros;

    }

    final public function acciones_visibles_permitidas(int $grupo_id, string $seccion)
    {
        $filtro_extra['adm_accion.visible'] = 'activo';
        $filtro_extra['adm_accion.status'] = 'activo';
        $filtro_extra['adm_seccion.status'] = 'activo';
        $acciones = $this->acciones_permitidas(grupo_id: $grupo_id,seccion:  $seccion,
            filtro_extra: $filtro_extra);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener acciones',data:  $acciones);
        }
        $datos = array();
        foreach ($acciones as $accion){
            $datos[] = (object)$accion;
        }

        return $datos;

    }

    private function adm_namespace_name(string $adm_seccion_descricpion)
    {
        $adm_seccion = $this->seccion_by_descripcion(descripcion: $adm_seccion_descricpion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener seccion con descripcion: '.$adm_seccion_descricpion,
                data: $adm_seccion);
        }
        $adm_namespace_name = trim($adm_seccion->adm_namespace_name);
        $adm_namespace_name_new = $adm_namespace_name.'/models';
        return str_replace('/', '\\', $adm_namespace_name_new);

    }

    final public function adm_seccion(string $descripcion)
    {
        $filtro['adm_seccion.descripcion'] = $descripcion;

        $existe = $this->existe_seccion(descripcion: $descripcion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe seccion',data: $existe);
        }
        if(!$existe){
            return $this->error->error(mensaje: 'Error no existe la seccion',data: $existe);
        }

        $r_adm_seccion = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener seccion',data: $r_adm_seccion);
        }
        return (object)$r_adm_seccion->registros[0];
    }
    final public function adm_seccion_id(string $descripcion)
    {
        $existe = $this->existe_seccion(descripcion: $descripcion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe seccion',data: $existe);
        }
        if(!$existe){
            return $this->error->error(mensaje: 'Error no existe la seccion',data: $descripcion);
        }

        $adm_seccion = $this->adm_seccion(descripcion: $descripcion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener seccion',data: $adm_seccion);
        }
        return $adm_seccion->adm_seccion_id;

    }
    public function alta_bd(): array|stdClass
    {

        $keys = array('descripcion');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $registro = $this->init_row_alta(defaults: $this->defaults, parents_data: $this->parents_data, registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar registro',data: $registro);
        }

        if(!isset($registro['adm_namespace_id'])){
            $inserta_pred = (new adm_namespace(link: $this->link))->inserta_predeterminado();
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar namespace predeterminado',data: $inserta_pred);
            }

            $adm_namespace_id = (new adm_namespace(link: $this->link))->id_predeterminado();
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener namespace predeterminado',data: $adm_namespace_id);
            }
            $registro['adm_namespace_id'] = $adm_namespace_id;
        }

        if(!isset($registro['etiqueta_label']) || trim($registro['etiqueta_label'] === '')){
            $registro['etiqueta_label'] = $registro['descripcion'];
        }

        $this->registro = $registro;

        $r_alta_bd =  parent::alta_bd(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta seccion ',data: $r_alta_bd);
        }
        $registro_id = $r_alta_bd->registro_id;

        $r_alta_acciones =$this->inserta_acciones_basicas(registro_id: $registro_id);
        if (errores::$error){
            return  $this->error->error(mensaje: 'Error al dar de alta acciones basicas',data: $r_alta_acciones);
        }

        return $r_alta_bd;

    }
    protected function asigna_full_status_alta(array $registro): array
    {
        $keys = array();

        $registro = $this->asigna_status_alta(keys:$keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar status',data: $registro);
        }
        return $registro;
    }

    /**
     * Elimina una seccion con sus hijos adm_accion, adm_seccion_pertenece, adm_elemento_lista, adm_campo, adm_bitacora
     * @param int $id Identificador de la seccion
     * @return array|stdClass
     * @version 2.46.4
     */
    public function elimina_bd(int $id): array|stdClass
    {
        if($id <=0){
            return $this->error->error('Error id debe se mayor a 0', $id);
        }
        $filtro['adm_seccion.id'] = $id;
        $r_adm_accion = (new adm_accion(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error('Error al eliminar r_adm_accion', $r_adm_accion);
        }
        $r_adm_seccion_pertenece = (new adm_seccion_pertenece(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error('Error al eliminar r_adm_seccion_pertenece', $r_adm_seccion_pertenece);
        }

        $r_adm_campo = (new adm_campo(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error('Error al eliminar r_adm_campo', $r_adm_campo);
        }
        $r_adm_bitacora = (new adm_bitacora(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error('Error al eliminar r_adm_bitacora', $r_adm_bitacora);
        }

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error('Error al eliminar adm_seccion', $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    final public function existe_seccion(string $descripcion)
    {
        $filtro['adm_seccion.descripcion'] = $descripcion;
        $existe = $this->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error a verificar si existe seccion',data:  $existe);
        }
        return $existe;

    }

    final public function crea_modelo(string $adm_seccion_descricpion)
    {
        $adm_namespace_name = (new adm_seccion(link: $this->link))->adm_namespace_name(
            adm_seccion_descricpion: $adm_seccion_descricpion);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al obtener adm_namespace_name con adm_seccion_descricpion: '.$adm_seccion_descricpion,
                data: $adm_namespace_name);
        }

        $modelo = modelo::modelo_new(link: $this->link,modelo:  $adm_seccion_descricpion,
            namespace_model: $adm_namespace_name);

        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al obtener modelo con adm_seccion_descricpion: '.$adm_seccion_descricpion,
                data: $modelo);
        }
        return $modelo;

    }
    final public function inserta_accion(array $accion_basica, int $registro_id){
        $accion = $this->accion_maqueta(accion_basica: $accion_basica, registro_id: $registro_id);
        if (errores::$error){
            return  $this->error->error(mensaje: 'Error al maquetar accion',data: $accion);
        }

        $adm_accion_modelo = new adm_accion($this->link);
        $adm_accion_modelo->registro = $accion;
        $r_alta_accion =$adm_accion_modelo->alta_bd();
        if (errores::$error){
            return  $this->error->error(mensaje: 'Error al dar de alta acciones basicas',data: $r_alta_accion);
        }
        return $r_alta_accion;
    }
    private function inserta_acciones(array $acciones_basicas, int $registro_id){
        $insert = array();
        foreach ($acciones_basicas as $accion_basica) {
            $r_alta_accion =$this->inserta_accion(accion_basica: $accion_basica,registro_id:  $registro_id);
            if (errores::$error){
                return  $this->error->error(mensaje: 'Error al dar de alta acciones basicas',data: $r_alta_accion);
            }
            $insert[] = $r_alta_accion;
        }
        return $insert;
    }
    private function inserta_acciones_basicas(int $registro_id){
        $r_accion_basica = (new adm_accion_basica($this->link))->obten_registros_activos();
        if (errores::$error){
            return  $this->error->error(mensaje: 'Error al obtener datos del registro',data: $r_accion_basica);
        }

        $acciones_basicas = $r_accion_basica->registros;

        $r_alta_acciones =$this->inserta_acciones(acciones_basicas: $acciones_basicas, registro_id: $registro_id);
        if (errores::$error){
            return  $this->error->error(mensaje: 'Error al dar de alta acciones basicas',data: $r_alta_acciones);
        }
        return $r_alta_acciones;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys = array('adm_menu_id','adm_namespace_id','descripcion')): array|stdClass
    {

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva,keys:  $keys); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error('Error al modificar adm_seccion', $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


    /**
     *
     * @param int $menu_id
     * @return array
     */
    public function obten_submenu_permitido(int $menu_id): array
    {
        $valida = $this->validacion->valida_estructura_menu($menu_id);
        if(errores::$error){
            return $this->error->error('Error al validar ',$valida);
        }

	    $grupo_id = $_SESSION['grupo_id'];

	    $where_menu = " AND adm_seccion.adm_menu_id = $menu_id";

	    $consulta = "SELECT 
               		adm_seccion.id AS id ,
                		adm_seccion.icono AS icono,
                		adm_seccion.descripcion AS descripcion,
                		adm_seccion.etiqueta_label AS etiqueta_label,
                		adm_seccion.adm_menu_id AS menu_id
                		FROM adm_seccion
                	INNER JOIN adm_accion  ON adm_accion.adm_seccion_id = adm_seccion.id
                	INNER JOIN adm_accion_grupo AS permiso ON permiso.adm_accion_id = adm_accion.id
                	INNER JOIN adm_grupo  ON adm_grupo.id = permiso.adm_grupo_id
                	INNER JOIN adm_menu  ON adm_menu.id = adm_seccion.adm_menu_id
                WHERE 
                	adm_seccion.status = 'activo' 
                	AND adm_accion.status = 'activo' 
                	AND adm_grupo.status = 'activo' 
                	AND permiso.adm_grupo_id = $grupo_id $where_menu
                        AND adm_accion.visible = 'activo'
                GROUP BY adm_seccion.id
                ";
	    $result = $this->link->query($consulta);
	    $n_registros = $result->rowCount();

	    if($this->link->errorInfo()[1]){
	        return $this->error->error(mensaje: 'Error al ejecutar sql',data: array(array($this->link->errorInfo(),$consulta)));
	    }

	    $new_array = array();
	    while( $row = $result->fetchObject()){
	        $new_array[] = (array)$row;
	    }
        $result->closeCursor();
	    return array('registros' => $new_array, 'n_registros' => $n_registros);
	}

    final public function seccion_by_descripcion(string $descripcion){

        $r_adm_seccion = $this->registro_by_descripcion(descripcion: $descripcion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener seccion con descripcion: '.$descripcion,
                data: $r_adm_seccion);
        }
        if($r_adm_seccion->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe la seccion con descripcion: '.$descripcion,
                data: $r_adm_seccion);
        }
        if($r_adm_seccion->n_registros > 1){
            return $this->error->error(mensaje: 'Error existe mas de una seccion con descripcion: '.$descripcion,
                data: $r_adm_seccion);
        }
        return (object)$r_adm_seccion->registros[0];

    }

    public function secciones_permitidas(int $adm_menu_id = -1): array
    {

        $r_adm_seccion = new stdClass();
        $r_adm_seccion->registros = array();

        $adm_usuario_id = -1;
        if(isset($_SESSION['usuario_id'])){
            $adm_usuario_id = $_SESSION['usuario_id'];
        }

        if($adm_usuario_id > 0) {

            $secciones_sistema = $this->secciones_sistema();
            if (errores::$error) {
                return $this->error->error('Error al obtener secciones ', $secciones_sistema);
            }

            $secciones_sistema = $this->validar_acciones(secciones_sistema: $secciones_sistema);
            if (errores::$error) {
                return $this->error->error('Error al validar acciones', $secciones_sistema);
            }

            if(count($secciones_sistema)>0 && $adm_menu_id > 0) {

                $adm_usuario = (new adm_usuario(link: $this->link))->registro(
                    registro_id: $adm_usuario_id, columnas_en_bruto: true, retorno_obj: true);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al obtener usuario ',data:  $adm_usuario);
                }

                $adm_grupo = (new adm_grupo(link: $this->link))->registro(
                    registro_id: $adm_usuario->adm_grupo_id, columnas_en_bruto: true, retorno_obj: true);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al obtener grupo ',data: $adm_grupo);
                }


                $seccion_sistema_in = array();
                foreach ($secciones_sistema as $seccion_sistema) {
                    $seccion_sistema_in[] = $seccion_sistema['adm_seccion_id'];
                }

                $filtro['adm_grupo.id'] = $adm_grupo->id;
                $filtro['adm_menu.id'] = $adm_menu_id;
                $group_by[] = 'adm_seccion.id';
                $in['llave'] = 'adm_seccion.id';
                $in['values'] = $seccion_sistema_in;

                $r_adm_seccion = (new adm_accion_grupo(link: $this->link))->filtro_and(
                    filtro: $filtro, group_by: $group_by, in: $in);
                if (errores::$error) {
                    return $this->error->error('Error al obtener secciones ', $r_adm_seccion);
                }
            }

        }

        return $r_adm_seccion->registros;

    }


    /**
     * Obtiene las secciones integradas en un paquete o sistema
     * @return array
     */
    final public function secciones_sistema(): array
    {
        $filtro['adm_sistema.descripcion'] = (new generales())->sistema;
        $r_seccion_pertenece = (new adm_seccion_pertenece(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener secciones ', data: $r_seccion_pertenece);
        }
        return $r_seccion_pertenece->registros;
    }

    /**
     * Valida las acciones de un sistema
     * @param array $secciones_sistema Secciones de un sistema
     * @return array
     * @version 11.6.0
     */
    final public function validar_acciones(array $secciones_sistema): array
    {
        foreach ($secciones_sistema as $item => $value){
            $adm_seccion_id = $value['adm_seccion_id'];

            $filtro['adm_seccion_id'] = $adm_seccion_id;
            $acciones = (new adm_accion($this->link))->filtro_and(columnas: array('adm_accion_descripcion'),
                filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener acciones ', data: $acciones);
            }


            $stream = array_map(fn($valor): string => $valor['adm_accion_descripcion'],$acciones->registros);

            if (!in_array("lista",$stream)){
                unset($secciones_sistema[$item]);
            }
        }
        return $secciones_sistema;
    }

    protected function valida_alta_bd(array $registro): bool|array
    {
        $keys = array('adm_menu_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $valida = parent::valida_alta_bd(registro:$registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        return true;
    }
}