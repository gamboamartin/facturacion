<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_factura_documento extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_factura_documento';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla,'doc_documento'=>$tabla, 'doc_tipo_documento' => 'doc_documento');
        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
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

    public function get_codigo_aleatorio(int $longitud = 6): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';

        for($i = 0; $i < $longitud; $i++) {
            $random_character = $chars[mt_rand(0, strlen($chars) - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
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


}