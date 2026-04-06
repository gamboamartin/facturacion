<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_entidad;
use PDO;
use PDOException;


class fc_layout_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_layout_factura';
        $columnas = [
            $tabla=>false, 'fc_factura'=>$tabla,
            'fc_layout_nom'=>$tabla, 'fc_layout_periodo'=>'fc_layout_nom',
            'com_sucursal'=> 'fc_layout_nom', 'com_cliente' => 'com_sucursal',
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

        $r_montos = $this->actualiza_montos_por_asignar(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id
        );
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error al modificar montos por asignar',data: $r_montos
            );
        }

        $rs2 = $this->relaciona_factura_y_layout(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id,
            montos: $r_montos
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

        $r_montos = $this->actualiza_montos_por_asignar(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id
        );
        if(errores::$error){
            $this->link->rollBack();
            return $this->error->error(
                mensaje: 'Error al modificar montos por asignar',data: $r_montos
            );
        }

        $rs2 = $this->relaciona_factura_y_layout(
            fc_factura_id:  $fc_factura_id,
            fc_layout_nom_id: $fc_layout_nom_id,
            montos: $r_montos
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

    private function relaciona_factura_y_layout(int $fc_factura_id, int $fc_layout_nom_id, array $montos){

        $user_id = $_SESSION['usuario_id'];

        $resto_factura = 0;
        $resto_layout_nom = 0;
        $monto_relacionado = 0;

        if (isset($montos['monto_resto_factura'])) {
            $resto_factura = $montos['monto_resto_factura'];
        }

        if (isset($montos['monto_resto_layout'])) {
            $resto_layout_nom = $montos['monto_resto_layout'];
        }

        if (isset($montos['monto_relacionado'])) {
            $monto_relacionado = $montos['monto_relacionado'];
        }

        $consulta = "INSERT INTO fc_layout_factura ";
        $consulta .= " (fc_layout_nom_id, fc_factura_id, status, usuario_alta_id, usuario_update_id, ";
        $consulta .= " monto_relacionado, resto_asignar_factura, resto_asignar_layout_nom)";
        $consulta .= " VALUES ({$fc_layout_nom_id}, {$fc_factura_id}, 'activo', {$user_id}, {$user_id}, ";
        $consulta .= " {$monto_relacionado}, {$resto_factura}, {$resto_layout_nom})";
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

    private function actualiza_montos_por_asignar(int $fc_factura_id, int $fc_layout_nom_id)
    {
        $fc_factura_modelo = new fc_factura($this->link);
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);

        $fc_factura_modelo->registro_id = $fc_factura_id;
        $data_factura = $fc_factura_modelo->obten_data();
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al obtener el monto_por_asignar de la factura', data: $data_factura
            );
        }

        $fc_layout_nom_modelo->registro_id = $fc_layout_nom_id;
        $data_layout = $fc_layout_nom_modelo->obten_data();
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al obtener el monto_por_asignar de la layout', data: $data_layout
            );
        }

        $monto_factura = (double)$data_factura['fc_factura_monto_por_asignar'];
        $monto_layout = (double)$data_layout['fc_layout_nom_monto_por_asignar'];

        $resto_layout = 0;
        $resto_factura = 0;

        $monto_relacionado = min($monto_factura, $monto_layout);

        if ($monto_factura > $monto_layout) {

            $resto_layout = 0;
            $resto_factura = $monto_factura - $monto_layout;

            $rs_mf = $this->modifica_monto_por_asignar(
                tabla: 'fc_factura',
                nuevo_monto: $resto_factura,
                registro_id: $fc_factura_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de la factura', data: $rs_mf
                );
            }

            $rs_ml = $this->modifica_monto_por_asignar(
                tabla: 'fc_layout_nom',
                nuevo_monto: $resto_layout,
                registro_id: $fc_layout_nom_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de el layout', data: $rs_ml
                );
            }
        }

        if ($monto_layout > $monto_factura) {

            $resto_layout = $monto_layout - $monto_factura;
            $resto_factura = 0;

            $rs_mf = $this->modifica_monto_por_asignar(
                tabla: 'fc_factura',
                nuevo_monto: $resto_factura,
                registro_id: $fc_factura_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de la factura', data: $rs_mf
                );
            }

            $rs_ml = $this->modifica_monto_por_asignar(
                tabla: 'fc_layout_nom',
                nuevo_monto: $resto_layout,
                registro_id: $fc_layout_nom_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de el layout', data: $rs_ml
                );
            }
        }

        if ($monto_factura === $monto_layout){

            $rs_mf = $this->modifica_monto_por_asignar(
                tabla: 'fc_factura',
                nuevo_monto: 0,
                registro_id: $fc_factura_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de la factura', data: $rs_mf
                );
            }

            $rs_ml = $this->modifica_monto_por_asignar(
                tabla: 'fc_layout_nom',
                nuevo_monto: 0,
                registro_id: $fc_layout_nom_id
            );
            if(errores::$error){
                return $this->error->error(
                    mensaje: 'Error al modificar el monto por asignar de el layout', data: $rs_ml
                );
            }
        }

        return [
            'monto_resto_factura' => $resto_factura,
            'monto_resto_layout' => $resto_layout,
            'monto_relacionado' => $monto_relacionado,
        ];
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

    private function modifica_monto_por_asignar(string $tabla, $nuevo_monto, int $registro_id): array
    {
        $query = "
                UPDATE 
                    {$tabla}
                SET 
                    {$tabla}.monto_por_asignar = :nuevo_monto
                WHERE 
                    {$tabla}.id = :registro_id";

        try {
            $stmt = $this->link->prepare($query);
            $stmt->execute([
                ':nuevo_monto' => $nuevo_monto,
                ':registro_id' => $registro_id,
            ]);
        } catch (PDOException $e) {
            return (new errores())->error(
                mensaje: $e->getMessage(),
                data:  $e
            );
        }

        return [];
    }

}