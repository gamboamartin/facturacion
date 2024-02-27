<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use config\generales;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\plugins\ssl;
use PDO;
use stdClass;


class fc_key_csd extends modelo{

    public function __construct(PDO $link){
        $tabla = 'fc_key_csd';
        $columnas = array($tabla=>false,'fc_csd'=>$tabla,'doc_documento'=>$tabla);
        $campos_obligatorios = array('codigo');

        $campos_view['fc_csd_id'] = array('type' => 'selects', 'model' => new fc_csd($link));
        $campos_view['doc_documento_id'] = array('type' => 'selects', 'model' => new doc_documento($link));
        $campos_view['documento'] = array('type' => 'files');
        $campos_view['codigo'] = array('type' => 'inputs');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Key CSD';
    }

    public function alta_bd(): array|stdClass
    {

        $registro = (new _cert())->init_alta_bd(modelo: $this, key_val_id: 'fc_csd_id', registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar',data: $registro);
        }

        $this->registro = $registro;

        $r_alta_bd = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta key csd',data: $r_alta_bd);
        }

        $inserta_etapa = (new _cert())->inserta_etapas($r_alta_bd->registro['fc_csd_id'],link: $this->link,
            pr_etapa_descripcion:  'KEY INTEGRADO');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
        }

        return $r_alta_bd;
    }
    final public function elimina_bd(int $id): array|stdClass
    {
        $filtro['fc_key_csd.id'] = $id;
        $del = (new fc_key_pem(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar pem',data: $del);
        }

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al r_elimina_bd',data: $r_elimina_bd);
        }
        return $r_elimina_bd;

    }
    final public function genera_pem_full(int $fc_key_csd_id)
    {
        $data = (new _cert())->integra_pem(registro_id: $fc_key_csd_id,modelo: $this);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pem', data: $data);
        }

        $fc_key_csd = $this->registro(registro_id: $fc_key_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row', data: $fc_key_csd);
        }

        $fc_key_pem_ins['fc_key_csd_id'] = $fc_key_csd['fc_key_csd_id'];
        $inserta_pem = (new fc_key_pem(link: $this->link))->alta_registro(registro: $fc_key_pem_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar pem', data: $inserta_pem);
        }
        return $inserta_pem;

    }
    public function get_key_csd(int $fc_key_csd_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_key_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener key CSD',data:  $registro);
        }

        return $registro;
    }
    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $validacion = (new _cert())->validaciones(data: $registro,key_id: 'fc_csd_id');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $registro = (new _cert())->init_campos_base(data: $registro,link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar csd',data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    final public function row_by_csd(int $fc_csd_id)
    {
        $filtro = array();
        $filtro['fc_csd.id'] = $fc_csd_id;
        $r_fc_csd_key = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_csd_key', data: $r_fc_csd_key);
        }

        if($r_fc_csd_key->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe registro', data: $r_fc_csd_key);
        }
        if($r_fc_csd_key->n_registros > 1){
            return $this->error->error(mensaje: 'Error  existe mas de un registro', data: $r_fc_csd_key);
        }
        return $r_fc_csd_key->registros[0];

    }

    final public function ruta_key(int $fc_csd_id){
        $row = $this->row_by_csd(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro', data: $row);
        }

        return $row['doc_documento_ruta_absoluta'];
    }

}