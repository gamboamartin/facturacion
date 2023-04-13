<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_factura_documento extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_factura_documento';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla,'doc_documento'=>$tabla,
            'doc_tipo_documento' => 'doc_documento','doc_extension'=>'doc_documento');
        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Fac Documento';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $this->registro['fc_factura_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_factura);
        }
        $doc_documento_id = (new doc_documento(link: $this->link))->registro(registro_id: $this->registro['doc_documento_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener doc_documento_id', data: $doc_documento_id);
        }
        if($doc_documento_id->doc_tipo_documento_descripcion!=='CFDI PDF') {
            $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_factura->fc_factura_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        if(!isset($this->registro['codigo'])){
            $this->registro['codigo'] =  $this->get_codigo_aleatorio();
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar codigo aleatorio',data:  $this->registro);
            }
        }

        $this->registro['descripcion'] = $this->registro['codigo'];

        $this->registro = $this->campos_base(data: $this->registro,modelo: $this);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $this->registro);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar factura documento', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    final public function elimina_bd(int $id): array|stdClass
    {
        $fc_factura_documento = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_factura_documento);
        }
        if($fc_factura_documento->doc_tipo_documento_descripcion!=='CFDI PDF') {
            $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_factura_documento->fc_factura_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        $r_elimina = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_elimina);
        }
        return $r_elimina;
    }

    public function get_factura_documento(int $fc_factura_id, string $tipo_documento): array|string{


        $documento = $this->get_factura_documentos(fc_factura_id: $fc_factura_id,tipo_documento: $tipo_documento);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documento', data: $documento);
        }


        $ruta_archivo = "";

        if ($documento->n_registros > 0){
            $ruta_archivo = $documento->registros[0]['doc_documento_ruta_absoluta'];
        }

        return $ruta_archivo;
    }

    public function get_factura_documentos(int $fc_factura_id, string $tipo_documento): array|stdClass{

        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['doc_tipo_documento.descripcion'] = $tipo_documento;
        $documento = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener documento', data: $documento);
        }


        return $documento;
    }

    final public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_factura_documento = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_factura_documento);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_factura_documento->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_modifica = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica);
        }
        return $r_modifica;
    }

    final public function status(string $campo, int $registro_id): array|stdClass
    {
        $fc_factura_documento = $this->registro(registro_id: $registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener registro', data: $fc_factura_documento);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_factura_documento->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_status = parent::status($campo, $registro_id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error r_status', data: $r_status);
        }
        return $r_status;
    }


}