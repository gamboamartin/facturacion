<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use models\doc_documento;
use PDO;
use stdClass;


class fc_cer_csd extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_cer_csd';
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
    }

    public function alta_bd(): array|stdClass
    {
        $validacion = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $this->registro = $this->asigna_documento(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar documento',data: $this->registro);
        }

        $this->registro = $this->init_campos_base(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $r_alta_bd = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta key csd',data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    private function asigna_documento(array $data): array|stdClass
    {
        $alta_documento = (new fc_key_csd($this->link))->alta_documento(documento: "documento");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta documento',data: $alta_documento);
        }

        $data['doc_documento_id'] = $alta_documento->registro_id;

        return $data;
    }

    public function get_key_csd(int $fc_cer_csd_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_cer_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener Cer CSD',data:  $registro);
        }

        return $registro;
    }

    private function init_campos_base(array $data): array
    {
        $csd = (new fc_csd($this->link))->get_csd(fc_csd_id: $data["fc_csd_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener CSD',data:  $csd);
        }

        $documento = (new doc_documento($this->link))->registro(registro_id: $data["doc_documento_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener documento',data:  $documento);
        }

        if(!isset($data['codigo'])){
            $data['codigo'] =  $data['fc_csd_codigo'];
            $data['codigo'] .=  $documento['doc_documento_codigo'];
        }

        if(!isset($data['descripcion'])){
            $data['descripcion'] =  $documento['doc_documento_descripcion'];
        }

        if(!isset($data['codigo_bis'])){
            $data['codigo_bis'] =  $data['codigo'];
        }

        if(!isset($data['descripcion_select'])){
            $ds = ucwords($data['descripcion']);
            $data['descripcion_select'] =  "{$data['codigo']} - {$ds}";
        }

        if(!isset($data['alias'])){
            $data['alias'] = $data['codigo'];
        }
        return $data;
    }

    private function validaciones(array $data): bool|array
    {
        $keys = array('codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return true;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $validacion = $this->validaciones(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $registro = $this->init_campos_base(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar csd',data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

}