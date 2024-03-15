<?php
namespace gamboamartin\facturacion\models;

use config\generales;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\documento\models\doc_extension_permitido;
use gamboamartin\errores\errores;
use gamboamartin\plugins\files;
use gamboamartin\plugins\ssl;
use gamboamartin\proceso\models\pr_etapa_proceso;
use gamboamartin\validacion\validacion;
use PDO;
use stdClass;

class _cert
{
    private errores $error;
    private validacion $validacion;

    public function __construct()
    {
        $this->error = new errores();
        $this->validacion = new validacion();

    }

    private function alta_documento(string $documento, PDO $link): array|stdClass
    {

        if (!array_key_exists($documento,$_FILES)){
            return $this->error->error(mensaje: "Error no existe: $documento", data: $documento);
        }
        if(!isset($_FILES[$documento]['name'])){
            return $this->error->error(mensaje: 'Error no existe: $_FILES[$documento][name]', data: $documento);
        }

        $extension = (new files())->extension(archivo: $_FILES[$documento]['name']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener extension', data: $extension);
        }

        $filtro['doc_extension.descripcion'] = $extension;
        $existe = (new doc_extension_permitido($link))->filtro_and(filtro: $filtro,limit: 1);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar extension del documento', data: $extension);
        }

        if ($existe->n_registros <= 0){
            return $this->error->error(mensaje: "Error la extension: $extension no esta permitida", data: $extension);
        }

        $filtro['doc_documento.descripcion'] = mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).$_FILES[$documento]['name'];
        $duplicado = (new doc_documento($link))->filtro_and(filtro: $filtro,limit: 1);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar duplicado del documento', data: $duplicado);
        }

        if ($duplicado->n_registros >= 1){
            return $this->error->error(mensaje: "Error el documento ya existe", data: $duplicado);
        }

        $doc_documento = new doc_documento($link);
        $doc_documento->registro['doc_tipo_documento_id'] = $existe->registros[0]['doc_tipo_documento_id'];
        $doc_documento->registro['descripcion'] = $_FILES[$documento]['name'];
        $doc_documento->registro['descripcion_select'] = $_FILES[$documento]['name'];
        $doc_documento = $doc_documento->alta_bd(file: $_FILES[$documento]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al dar de alta el documento', data: $doc_documento);
        }

        return $doc_documento;
    }

    private function asigna_documento(array $data, PDO $link): array|stdClass
    {
        $alta_documento = $this->alta_documento(documento: "documento",link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta documento',data: $alta_documento);
        }

        $data['doc_documento_id'] = $alta_documento->registro_id;

        return $data;
    }

    private function code_row_ins(fc_key_csd|fc_cer_csd|fc_key_pem|fc_cer_pem $modelo, array $registro)
    {
        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $modelo->get_codigo_aleatorio();
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $registro);
            }
        }
        return $registro;

    }

    private function etapa_docs_completos(int $fc_csd_id, PDO $link)
    {
        $tiene_documentos_completos = (new fc_csd(link: $link))->tiene_documentos_completos(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar docs',data: $tiene_documentos_completos);
        }

        if($tiene_documentos_completos){
            $inserta_etapa = $this->inserta_etapa(fc_csd_id: $fc_csd_id,link:  $link,
                pr_etapa_descripcion:  'DOCS INTEGRADOS', pr_proceso_descripcion: 'CSD');
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
            }
        }
        return $tiene_documentos_completos;

    }

    private function etapa_docs_completos_pem(int $fc_csd_id, PDO $link)
    {
        $tiene_documentos_completos = (new fc_csd(link: $link))->tiene_documentos_completos_pem(fc_csd_id: $fc_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar docs',data: $tiene_documentos_completos);
        }

        if($tiene_documentos_completos){
            $inserta_etapa = $this->inserta_etapa(fc_csd_id: $fc_csd_id,link:  $link,
                pr_etapa_descripcion:  'LISTO USO', pr_proceso_descripcion: 'CSD');
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
            }
        }
        return $tiene_documentos_completos;

    }

    private function fc_csd_etapa_ins(int $fc_csd_id, PDO $link, string $pr_etapa_descripcion, string $pr_proceso_descripcion)
    {
        $pr_etapa_proceso_id =$this->pr_etapa_proceso_id(link: $link,pr_etapa_descripcion:  $pr_etapa_descripcion,
            pr_proceso_descripcion:  $pr_proceso_descripcion);


        $fc_csd_etapa = $this->row_fc_csd_etapa(fc_csd_id: $fc_csd_id, pr_etapa_proceso_id: $pr_etapa_proceso_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener etapa ins',data: $fc_csd_etapa);
        }

        return $fc_csd_etapa;

    }

    private function file_pem(string $file_origen,string $name): array
    {
        $_FILES['documento'] = array();
        $_FILES['documento']['name'] =  $name;
        $_FILES['documento']['tmp_name'] = $file_origen;

        return $_FILES;

    }

    private function genera_pem(int $registro_id, fc_key_csd|fc_cer_csd $modelo)
    {

        $pem = $this->pem(modelo: $modelo,registro_id:  $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pem', data: $pem);
        }

        $data_pem = file_get_contents($pem->ruta_out);
        if($data_pem === ''){
            return $this->error->error(mensaje: 'Error al generar pem', data: $data_pem);
        }
        $data = new stdClass();
        $data->file= $pem->ruta_out;
        $data->contenido= $data_pem;
        return $data;

    }

    final public function init_alta_bd(fc_key_csd|fc_cer_csd $modelo, string $key_val_id, array $registro)
    {
        $keys = array('fc_csd_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data: $valida);
        }

        $existe_file = $this->valida_existe_file(fc_csd_id: $registro['fc_csd_id'],link:  $modelo->link,
            name_modelo:  $modelo->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe file',data: $existe_file);
        }

        $registro = $this->code_row_ins(modelo: $modelo,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $registro);
        }


        $validacion = $this->validaciones(data: $registro, key_id: $key_val_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $registro= $this->asigna_documento(data: $registro,link: $modelo->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar documento',data: $registro);
        }


        $registro = $this->init_campos_base(data: $registro,link: $modelo->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        return $registro;

    }

    final public function init_alta_pem(string $key_val_id, fc_cer_pem|fc_key_pem $modelo)
    {
        $registro = $this->code_row_ins(modelo: $modelo,registro:  $modelo->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integra code',data: $registro);
        }
        $modelo->registro = $registro;

        $validacion = $this->validaciones(data: $modelo->registro, key_id: $key_val_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $modelo->registro = $this->asigna_documento(data: $modelo->registro,link: $modelo->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar documento',data: $modelo->registro);
        }

        $modelo->registro = $this->init_campos_base(data: $modelo->registro,link: $modelo->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $modelo->registro);
        }
        return $modelo->registro;

    }

    final public function init_campos_base(array $data, PDO $link): array
    {


        $documento = (new doc_documento($link))->registro(registro_id: $data["doc_documento_id"]);
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

    final public function inserta_etapa(int $fc_csd_id, PDO $link, string $pr_etapa_descripcion, string $pr_proceso_descripcion)
    {
        $fc_csd_etapa = $this->fc_csd_etapa_ins(fc_csd_id: $fc_csd_id,link:  $link,
            pr_etapa_descripcion:  $pr_etapa_descripcion, pr_proceso_descripcion:  $pr_proceso_descripcion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener etapa ins',data: $fc_csd_etapa);
        }

        $r_alta_et_p = (new fc_csd_etapa(link: $link))->alta_registro(registro: $fc_csd_etapa);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $r_alta_et_p);
        }
        return $r_alta_et_p;

    }

    final public function inserta_etapas(int $fc_csd_id, PDO $link, string $pr_etapa_descripcion)
    {
        $etapas = new stdClass();
        $inserta_etapa = $this->inserta_etapa(fc_csd_id: $fc_csd_id,link:  $link,
            pr_etapa_descripcion:  $pr_etapa_descripcion, pr_proceso_descripcion: 'CSD');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
        }
        $etapas->doc = $inserta_etapa;

        $inserta_etapa = $this->etapa_docs_completos(fc_csd_id: $fc_csd_id,link:  $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
        }
        $etapas->full = $inserta_etapa;

        $inserta_etapa = $this->etapa_docs_completos_pem(fc_csd_id: $fc_csd_id,link:  $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
        }
        $etapas->full = $inserta_etapa;

        return $etapas;

    }

    private function integra_files(string $file_origen)
    {
        $name = $this->name_pem();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar name', data: $name);
        }

        $file = $this->file_pem(file_origen: $file_origen,name:  $name);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar FILE', data: $file);
        }
        return $file;

    }

    final public function integra_pem(int $registro_id, fc_key_csd|fc_cer_csd $modelo)
    {
        $data = $this->genera_pem(registro_id: $registro_id,modelo: $modelo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pem', data: $data);
        }

        $file = $this->integra_files(file_origen: $data->file);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar FILE', data: $file);
        }
        return $file;

    }

    private function params_pem(fc_key_csd|fc_cer_csd $modelo, int $registro_id)
    {
        $row = $modelo->registro(registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener datos', data: $row);
        }
        $ruta_in = $row['doc_documento_ruta_absoluta'];
        $pass = $row['fc_csd_password'];

        $ruta_out = $this->ruta_out();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar ruta_out', data: $ruta_out);
        }

        $data = new stdClass();
        $data->ruta_in = $ruta_in;
        $data->pass = $pass;
        $data->ruta_out = $ruta_out;

        return $data;

    }

    private function pem(fc_key_csd|fc_cer_csd $modelo, int $registro_id)
    {
        $params = $this->params_pem(modelo: $modelo,registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar params', data: $params);
        }

        if($modelo->tabla === 'fc_key_csd') {
            $pem = (new ssl())->genera_key_pem(pass: $params->pass, ruta_in: $params->ruta_in, ruta_out: $params->ruta_out);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar pem key', data: $pem);
            }
        }
        else{
            $pem = (new ssl())->genera_cer_pem(ruta_in: $params->ruta_in, ruta_out: $params->ruta_out);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar pem cer', data: $pem);
            }
        }

        $params->pem = $pem;

        return $params;

    }

    private function pr_etapa_proceso_id(PDO $link, string $pr_etapa_descripcion, string $pr_proceso_descripcion)
    {
        $filtro = array();
        $filtro['pr_proceso.descripcion'] = $pr_proceso_descripcion;
        $filtro['pr_etapa.descripcion'] = $pr_etapa_descripcion;
        $pr_etapa_proceso = (new pr_etapa_proceso(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener etapa_proceso',data: $pr_etapa_proceso);
        }
        if($pr_etapa_proceso->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe etapa proceso',data: $pr_etapa_proceso);
        }
        return (int)$pr_etapa_proceso->registros[0]['pr_etapa_proceso_id'];

    }

    private function name_pem(): string
    {
        $name = mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99);
        $name .= mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99);
        $name .= '.pem';
        return $name;
    }

    private function row_fc_csd_etapa(int $fc_csd_id, int $pr_etapa_proceso_id): array
    {
        $fc_csd_etapa['codigo'] = $fc_csd_id.$pr_etapa_proceso_id.mt_rand(1000000,9999999);
        $fc_csd_etapa['fc_csd_id'] = $fc_csd_id;
        $fc_csd_etapa['pr_etapa_proceso_id'] = $pr_etapa_proceso_id;
        $fc_csd_etapa['fecha'] = date('Y-m-d');

        return $fc_csd_etapa;

    }


    private function ruta_out(): string
    {
        $ruta_out_base = $this->ruta_out_base();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar ruta_out_base', data: $ruta_out_base);
        }

        $ruta_temporales = $this->ruta_temporales();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar ruta_temporales', data: $ruta_temporales);
        }

        return $ruta_temporales.$ruta_out_base.'.pem';

    }

    /**
     * POR DOCUMENTAR EN WIKI
     * Genera una cadena aleatoria.
     *
     * Esta función genera una cadena aleatoria con un máximo de
     * 20 iteraciones donde cada iteración agrega un número aleatorio entre 10 y 99.
     * La cadena resultante se utiliza probablemente como una ruta única base.
     *
     * @return string La cadena aleatoria generada.
     * @version 27.6.0
     */
    private function ruta_out_base(): string
    {
        $ruta_out_base = '';
        $n_iteraciones = 20;
        $i=0;
        while ($i<=$n_iteraciones){
            $ruta_out_base.=mt_rand(10,99);
            $i++;
        }
        return $ruta_out_base;

    }

    private function ruta_temporales(): string
    {
        $ruta_temporales = (new generales())->path_base.'archivos/temporales/';
        if(!is_dir($ruta_temporales)){
            mkdir($ruta_temporales);
        }
        return $ruta_temporales;

    }
    private function valida_existe_file(int $fc_csd_id, PDO $link, string $name_modelo)
    {
        if($name_modelo === 'fc_cer_csd') {
            $existe_file = (new fc_csd(link: $link))->tiene_file_cer(fc_csd_id: $fc_csd_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar si existe file', data: $existe_file);
            }
        }
        else{
            $existe_file = (new fc_csd(link: $link))->tiene_file_key(fc_csd_id: $fc_csd_id);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar si existe file',data: $existe_file);
            }
        }
        if($existe_file){
            return $this->error->error(mensaje: 'Error el cer ya existe favor eliminalo',data: $existe_file);
        }

        return true;

    }

    final public function validaciones(array $data, string $key_id): bool|array
    {
        $keys = array('codigo');
        $valida = (new validacion())->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array($key_id);
        $valida = (new validacion())->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return true;
    }

}
