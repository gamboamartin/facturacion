<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_entidad;
use PDO;


class fc_layout_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_layout_factura';
        $columnas = [
                $tabla=>false, 'fc_factura'=>$tabla,
                'fc_layout_nom'=>$tabla, 'fc_layout_periodo'=>'fc_layout_nom'
        ];
        $campos_obligatorios = [];
        $campos_view = [];
        $no_duplicados = [];

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Layouts y Facturas';
    }

    public function relaciona_factura_con_layout(int $fc_factura_id, int $fc_layout_nom_id)
    {
        $this->link->beginTransaction();

        $rs1 = $this->elimina_relacion_con_factura_id(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error en elimina_relacion_con_factura_id',data: $rs1
            );
        }

        $rs2 = $this->relaciona_factura_y_layout(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id
        );
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error en relaciona_factura_con_layout',data: $rs2
            );
        }

        $this->link->commit();

        return [];
    }

    public function relaciona_layout_con_factura(int $fc_layout_nom_id, int $fc_factura_id)
    {
        $this->link->beginTransaction();

        $rs1 = $this->elimina_relacion_con_layout_nom_id(fc_layout_nom_id: $fc_layout_nom_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error en elimina_relacion_con_layout_nom_id',data: $rs1
            );
        }

        $rs2 = $this->relaciona_factura_y_layout(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id
        );
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error en relaciona_factura_con_layout',data: $rs2
            );
        }

        $this->link->commit();

        return [];
    }

    private function relaciona_factura_y_layout(int $fc_factura_id, int $fc_layout_nom_id){

        $user_id = $_SESSION['usuario_id'];

        $consulta = "INSERT INTO fc_layout_factura ";
        $consulta .= " (fc_layout_nom_id, fc_factura_id, status, usuario_alta_id, usuario_update_id)";
        $consulta .= " VALUES ({$fc_layout_nom_id}, {$fc_factura_id}, 'activo', {$user_id}, {$user_id})";
        $rs = $this->ejecuta_sql($consulta);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al dar de alta relacion',data: $rs
            );
        }

        $fc_factura_modelo = new fc_factura($this->link);
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);

        $rs_asigna_factura = $fc_factura_modelo->asigna_bd(id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al marcar fc_factura como asignada', data: $rs_asigna_factura
            );
        }

        $rs_asigna_layout = $fc_layout_nom_modelo->asigna_bd(id: $fc_layout_nom_id);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al marcar fc_layout_nom como asignada', data: $rs_asigna_layout
            );
        }

        return [];

    }

    public function elimina_relacion_con_factura_id(int $fc_factura_id)
    {
        $filtro = [
            'fc_layout_factura.fc_factura_id' => $fc_factura_id,
        ];

        $rs1 = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al buscar registro fc_layout_factura',data: $rs1
            );
        }

        $rs2 = $this->elimina_registros(registros: $rs1->registros);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error en elimina_registros',data: $rs2
            );
        }

        return [];

    }

    public function elimina_relacion_con_layout_nom_id(int $fc_layout_nom_id)
    {
        $filtro = [
            'fc_layout_factura.fc_layout_nom_id' => $fc_layout_nom_id,
        ];

        $rs1 = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al buscar registro fc_layout_factura',data: $rs1
            );
        }

        $rs2 = $this->elimina_registros(registros: $rs1->registros);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error en elimina_registros',data: $rs2
            );
        }

        return [];

    }

    private function elimina_registros(array $registros)
    {
        $fc_factura_modelo = new fc_factura($this->link);
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);

        foreach ($registros as $registro){
            $fc_layout_factura_id = $registro['fc_layout_factura_id'];
            $fc_layout_nom_id = $registro['fc_layout_factura_fc_layout_nom_id'];
            $fc_factura_id = $registro['fc_layout_factura_fc_factura_id'];

            $rs1 = $fc_factura_modelo->desasigna_bd(id: $fc_factura_id);
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error en desasigna_bd fc_factura',data: $rs1
                );
            }

            $rs2 = $fc_layout_nom_modelo->desasigna_bd(id: $fc_layout_nom_id);
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error en desasigna_bd fc_layout_nom',data: $rs2
                );
            }

            $rs3 = $this->elimina_bd(id: $fc_layout_factura_id);
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error en elimina_bd fc_layout_factura',data: $rs3
                );
            }

        }

        return [];
    }

}