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

    public function elimina_con_layout_nom_id(int $fc_layout_nom_id)
    {
        $rs = $this->elimina_por_filtro(
            filtro: ['fc_layout_factura.fc_layout_nom_id' => $fc_layout_nom_id]
        );
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al elimina_por_filtro',data: $rs
            );
        }

        return [];

    }

    public function elimina_con_factura_id(int $fc_factura_id)
    {

        $rs = $this->elimina_por_filtro(
            filtro: ['fc_layout_factura.fc_factura_id' => $fc_factura_id]
        );
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al elimina_por_filtro',data: $rs
            );
        }

        return [];
    }

    public function elimina_relacion_con_registro_id(int $fc_layout_factura_id)
    {
        $fc_layout_factura_modelo = new fc_layout_factura(link: $this->link);
        $fc_layout_factura_modelo->registro_id = $fc_layout_factura_id;
        $data = $fc_layout_factura_modelo->obten_data(columnas: [
            'fc_layout_factura_fc_layout_nom_id',
            'fc_layout_factura_fc_factura_id',
            'fc_layout_factura_monto_relacionado',
            'fc_factura_monto_por_asignar',
            'fc_layout_nom_monto_por_asignar',

        ]);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error obtener el registro de la relacion',data: $data
            );
        }

        $fc_factura_id = $data['fc_layout_factura_fc_factura_id'];
        $fc_layout_nom_id = $data['fc_layout_factura_fc_layout_nom_id'];

        $monto_relacionado = (double)$data['fc_layout_factura_monto_relacionado'];
        $monto_factura = (double)$data['fc_factura_monto_por_asignar'];
        $monto_layout_nom = (double)$data['fc_layout_nom_monto_por_asignar'];

        $nuevo_monto_factura = $monto_relacionado + $monto_factura;
        $nuevo_monto_layout_nom = $monto_relacionado + $monto_layout_nom;

        $rs_update_factura = $this->modifica_monto_por_asignar(
            tabla: 'fc_factura',
            nuevo_monto: $nuevo_monto_factura,
            registro_id: $fc_factura_id
        );

        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al modificar monto en la factura',data: $rs_update_factura
            );
        }

        $rs_update_layout_nom = $this->modifica_monto_por_asignar(
            tabla: 'fc_layout_nom',
            nuevo_monto: $nuevo_monto_layout_nom,
            registro_id: $fc_layout_nom_id
        );

        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al modificar monto en el layout_nom',data: $rs_update_layout_nom
            );
        }

        $rs_delete_relacion = $fc_layout_factura_modelo->elimina_bd(id: $fc_layout_factura_id);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al eliminar relacion',data: $rs_delete_relacion
            );
        }

        return [
            'fc_factura_id' => $fc_factura_id,
            'fc_layout_nom_id' => $fc_layout_nom_id,
        ];
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

    private function elimina_por_filtro(array $filtro): array
    {
        $fc_layout_factura_modelo = new fc_layout_factura(link: $this->link);

        $rs = $fc_layout_factura_modelo->filtro_and(
            columnas: ['fc_layout_factura_id'],
            filtro: $filtro
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al filtrar datos',
                data: $rs
            );
        }

        if ((int)$rs->n_registros === 0) {
            return [];
        }

        foreach ($rs->registros as $registro) {
            $registro_id = (int)$registro['fc_layout_factura_id'];

            $rs_delete = $fc_layout_factura_modelo->elimina_relacion_con_registro_id(
                fc_layout_factura_id: $registro_id
            );

            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al eliminar relación con registro_id ' . $registro_id,
                    data: $rs_delete
                );
            }
        }

        return [];
    }

}