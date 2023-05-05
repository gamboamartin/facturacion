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

class fc_factura_documento extends _doc {
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

        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);


        $r_alta_bd= parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_elimina_bd= parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

    public function status(string $campo, int $registro_id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_modifica_bd = parent::status($campo, $registro_id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}