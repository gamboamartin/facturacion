<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use stdClass;

class controlador_org_logo extends \gamboamartin\organigrama\controllers\controlador_org_logo {

    // AQUI COMIENZA LO NUEVO
    public array $logo_activo = [];
    public array $logos = [];

    //AQUI TERMINA



    //AQUI COMIENZA LO NUEVO
    public function subir_logo(bool $header, bool $ws = false): array|stdClass
    {
        $org_empresa_id = (int)($_GET['org_empresa_id'] ?? 0);

        if ($org_empresa_id <= 0) {
            $modelo_empresa = new \gamboamartin\organigrama\models\org_empresa($this->link);

            $emp = $modelo_empresa->filtro_and(
                aplica_seguridad: false,
                columnas: ['org_empresa.id'],
                filtro: ['org_empresa.status' => 'activo'],
                limit: 1,
                order: ['org_empresa.id' => 'ASC']
            );

            if (\gamboamartin\errores\errores::$error) {
                return $this->retorno_error('Error al obtener empresa activa', $emp, $header, $ws);
            }

            $org_empresa_id = (int)($emp->registros[0]['org_empresa_id'] ?? 0);
        }

        if ($org_empresa_id <= 0) {
            return $this->retorno_error('No existe una empresa activa para asignar el logo', $org_empresa_id, $header, $ws);
        }

        $modelo_logo = new \gamboamartin\organigrama\models\org_logo($this->link);

        $extra_join = [
            'doc_documento' => [] // No se necesitan columnas adicionales de doc_documento, solo la ruta relativa
        ];

        // Logo principal actual
        $r_activo = $modelo_logo->filtro_and(
            aplica_seguridad: false,
            columnas: [
                'org_logo_id',
                'org_logo_fecha_alta',
                'org_logo_es_principal',
                'doc_documento_ruta_relativa'
            ],
            filtro: [
                'org_logo.status' => 'activo',
                'org_logo.es_principal' => 'activo',
                'org_logo.org_empresa_id' => $org_empresa_id
            ],
            limit: 1,
            order: ['org_logo.id' => 'DESC']
        );

        if (\gamboamartin\errores\errores::$error) {
            return $this->retorno_error('Error al obtener logo activo', $r_activo, $header, $ws);
        }

        $logo_activo = $r_activo->registros[0] ?? [];

        // Lista últimos logos
        $r_lista = $modelo_logo->filtro_and(
            aplica_seguridad: false,
            columnas: [
                'org_logo_id',
                'org_logo_status',
                'org_logo_es_principal',
                'org_logo_fecha_alta',
                'doc_documento_ruta_relativa'
            ],
            filtro: [
                'org_logo.status' => 'activo',
                'org_logo.org_empresa_id' => $org_empresa_id
            ],
            limit: 12,
            order: ['org_logo.id' => 'DESC']
        );
        if (\gamboamartin\errores\errores::$error) {
            return $this->retorno_error('Error al obtener lista de logos', $r_lista, $header, $ws);
        }

        $logos = $r_lista->registros ?? [];
        


        // asignar props
        $this->logo_activo = $logo_activo;
        $this->logos = $logos;
        $this->registro_id = $org_empresa_id;
        $this->seccion_titulo = 'Logo';
        $this->titulo_lista = 'Selecciona el logo principal';

        return ['logo_activo' => $logo_activo, 'logos' => $logos, 'org_empresa_id' => $org_empresa_id];
    }


    public function activar_logo_bd(bool $header, bool $ws = false): array
    {
        $org_logo_id = (int)($_POST['org_logo_id'] ?? 0);
        if ($org_logo_id <= 0) {
            return $this->retorno_error('Selecciona un logo válido', $org_logo_id, $header, $ws);
        }

        $modelo_logo = new \gamboamartin\organigrama\models\org_logo($this->link);

        // 1) Obtener org_empresa_id del logo seleccionado (activo)
        $r_logo = $modelo_logo->filtro_and(
            aplica_seguridad: false,
            columnas: [
                'org_logo_id',
                'org_empresa_id',        
                'org_logo_status'
            ],
            filtro: [
                'org_logo.id' => $org_logo_id,
                'org_logo.status' => 'activo'
            ],
            limit: 1
        );


        if (\gamboamartin\errores\errores::$error) {
            return $this->retorno_error('Error al obtener logo seleccionado', $r_logo, $header, $ws);
        }


        $row = $r_logo->registros[0] ?? [];
        if (empty($row)) {
            return $this->retorno_error('El logo seleccionado no existe o está inactivo', $org_logo_id, $header, $ws);
        }

        $org_empresa_id = (int)($row['org_empresa_id'] ?? 0);
        if ($org_empresa_id <= 0) {
            return $this->retorno_error('El logo no tiene empresa asociada', $row, $header, $ws);
        }


        try {
            $this->link->beginTransaction();

            // 1) Quitar principal actual SOLO de esa empresa
            $stmt_off = $this->link->prepare("
                UPDATE org_logo
                SET es_principal='inactivo'
                WHERE status='activo'
                AND es_principal='activo'
                AND org_empresa_id = :org_empresa_id
            ");
            $stmt_off->execute([':org_empresa_id' => $org_empresa_id]);

            // 2) Activar el seleccionado
            $stmt_on = $this->link->prepare("
                UPDATE org_logo
                SET es_principal='activo'
                WHERE id = :id
                AND status='activo'
                AND org_empresa_id = :org_empresa_id
            ");
            $stmt_on->execute([
                ':id' => $org_logo_id,
                ':org_empresa_id' => $org_empresa_id
            ]);

            $this->link->commit();
        } catch (\Throwable $e) {
            if ($this->link->inTransaction()) {
                $this->link->rollBack();
            }
            return $this->retorno_error('Error al activar logo: ' . $e->getMessage(), $org_logo_id, $header, $ws);
        }


        $_SESSION['exito'][]['mensaje'] = 'Logo principal actualizado correctamente';

        if ($header) {
            $sid = $_GET['session_id'] ?? '';
            header('Location: index.php?seccion=org_logo&accion=subir_logo&org_empresa_id=' . $org_empresa_id . '&session_id=' . $sid);
            exit;
        }

        return ['ok' => true];
    }



    // AQUI TERMINA LO NUEVO

}
