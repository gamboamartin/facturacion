<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_layout_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_layout_factura';
        $columnas = [$tabla=>false];
        $campos_obligatorios = [];
        $campos_view = [];
        $no_duplicados = [];

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Layouts y Facturas';
    }

    public function relaciona_factura_con_layout(int $fc_factura_id, int $fc_layout_nom_id){

        $this->link->beginTransaction();

        $user_id = $_SESSION['usuario_id'];

        $consulta = "INSERT INTO fc_layout_factura ";
        $consulta .= " (fc_layout_nom_id, fc_factura_id, status, usuario_alta_id, usuario_update_id)";
        $consulta .= " VALUES ({$fc_layout_nom_id}, {$fc_factura_id}, 'activo', {$user_id}, {$user_id})";
        $rs = $this->ejecuta_sql($consulta);
        if(errores::$error){
            $this->link->rollback();
            return $this->error->error(
                mensaje: 'Error al dar de alta relacion',data: $rs
            );
        }

        $fc_factura_modelo = new fc_factura($this->link);
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);

        $rs_asigna_factura = $fc_factura_modelo->asigna_bd(id: $fc_factura_id);
        if(errores::$error){
            $this->link->rollback();
            return $this->error->error(
                mensaje: 'Error al marcar fc_factura como asignada', data: $rs_asigna_factura
            );
        }

        $rs_asigna_layout = $fc_layout_nom_modelo->asigna_bd(id: $fc_layout_nom_id);
        if(errores::$error){
            $this->link->rollback();
            return $this->error->error(
                mensaje: 'Error al marcar fc_layout_nom como asignada', data: $rs_asigna_layout
            );
        }

        $this->link->commit();

        return [];

    }

    public function elimina_relacion_con_factura_id(int $fc_factura_id)
    {
        $filtro = [
            'fc_layout_factura.fc_factura_id' => $fc_factura_id,
        ];

        $rs = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al buscar registro fc_layout_factura',data: $rs
            );
        }

        echo '<pre>';
        print_r($rs);
        echo '</pre>';exit;
    }

}