<?php

use gamboamartin\acl\controllers\_accion_base;

class SeguridadEndpoint
{
    private PDO $link;

    public function __construct(PDO $link)
    {
        $this->link = $link;
    }

    // esto es para los clientes de los clientes

    // public function valida_contacto_cliente_factura(
    //     string $telefono_whatsapp,
    //     string $folio = '',
    //     string $rfc = ''
    // ): array {
    //     $telefono_whatsapp = preg_replace('/\D+/', '', $telefono_whatsapp);
    //     $folio = trim($folio);
    //     $rfc = strtoupper(trim($rfc));

    //     if ($telefono_whatsapp === '') {
    //         return [
    //             'autorizado' => false,
    //             'status' => 'telefono_vacio',
    //             'mensaje' => 'El teléfono de WhatsApp es requerido'
    //         ];
    //     }

    //     if ($folio === '' && $rfc === '') {
    //         return [
    //             'autorizado' => false,
    //             'status' => 'datos_insuficientes',
    //             'mensaje' => 'Debes indicar folio o RFC para validar la solicitud'
    //         ];
    //     }

    //     if ($folio !== '') {
    //         return $this->valida_por_folio(
    //             telefono_whatsapp: $telefono_whatsapp,
    //             folio: $folio,
    //             rfc: $rfc
    //         );
    //     }

    //     return $this->valida_por_rfc(
    //         telefono_whatsapp: $telefono_whatsapp,
    //         rfc: $rfc
    //     );
    // }

    // private function valida_por_folio(
    //     string $telefono_whatsapp,
    //     string $folio,
    //     string $rfc = ''
    // ): array {
    //     $where_rfc = '';
    //     $params = [
    //         ':telefono_whatsapp' => $telefono_whatsapp,
    //         ':folio' => $folio,
    //         ':estatus_telefono' => 'validado'
    //     ];

    //     if ($rfc !== '') {
    //         $where_rfc = ' AND com_cliente.rfc = :rfc ';
    //         $params[':rfc'] = $rfc;
    //     }

    //     $sql = "
    //         SELECT
    //             fc_factura.id AS fc_factura_id,
    //             fc_factura.folio AS fc_factura_folio,
    //             com_cliente.id AS com_cliente_id,
    //             com_cliente.rfc AS com_cliente_rfc,
    //             com_contacto.id AS com_contacto_id
    //         FROM fc_factura
    //         INNER JOIN com_sucursal
    //             ON com_sucursal.id = fc_factura.com_sucursal_id
    //         INNER JOIN com_cliente
    //             ON com_cliente.id = com_sucursal.com_cliente_id
    //         INNER JOIN com_contacto
    //             ON com_contacto.com_cliente_id = com_cliente.id
    //         WHERE fc_factura.folio = :folio
    //           AND CONCAT(com_contacto.codigo_pais, com_contacto.telefono) = :telefono_whatsapp
    //           AND com_contacto.estatus_telefono = :estatus_telefono
    //           $where_rfc
    //         LIMIT 1
    //     ";

    //     $stmt = $this->link->prepare($sql);
    //     $stmt->execute($params);
    //     $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$registro) {
    //         return [
    //             'autorizado' => false,
    //             'status' => 'no_autorizado',
    //             'mensaje' => 'Tu número no está asociado al cliente de la factura solicitada'
    //         ];
    //     }

    //     return [
    //         'autorizado' => true,
    //         'status' => 'autorizado',
    //         'tipo_validacion' => 'folio',
    //         'mensaje' => 'Solicitud autorizada por folio',
    //         'fc_factura_id' => (int)$registro['fc_factura_id'],
    //         'fc_factura_folio' => $registro['fc_factura_folio'],
    //         'com_cliente_id' => (int)$registro['com_cliente_id'],
    //         'com_cliente_rfc' => $registro['com_cliente_rfc'],
    //         'com_contacto_id' => (int)$registro['com_contacto_id']
    //     ];
    // }

    // private function valida_por_rfc(
    //     string $telefono_whatsapp,
    //     string $rfc
    // ): array {
    //     $params = [
    //         ':telefono_whatsapp' => $telefono_whatsapp,
    //         ':rfc' => $rfc,
    //         ':estatus_telefono' => 'validado'
    //     ];

    //     $sql = "
    //         SELECT
    //             com_cliente.id AS com_cliente_id,
    //             com_cliente.rfc AS com_cliente_rfc,
    //             com_contacto.id AS com_contacto_id
    //         FROM com_cliente
    //         INNER JOIN com_contacto
    //             ON com_contacto.com_cliente_id = com_cliente.id
    //         WHERE com_cliente.rfc = :rfc
    //           AND CONCAT(com_contacto.codigo_pais, com_contacto.telefono) = :telefono_whatsapp
    //           AND com_contacto.estatus_telefono = :estatus_telefono
    //         LIMIT 1
    //     ";

    //     $stmt = $this->link->prepare($sql);
    //     $stmt->execute($params);
    //     $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$registro) {
    //         return [
    //             'autorizado' => false,
    //             'status' => 'no_autorizado',
    //             'mensaje' => 'Tu número no está asociado al cliente solicitado'
    //         ];
    //     }

    //     return [
    //         'autorizado' => true,
    //         'status' => 'autorizado',
    //         'tipo_validacion' => 'rfc',
    //         'mensaje' => 'Solicitud autorizada por RFC',
    //         'fc_factura_id' => 0,
    //         'fc_factura_folio' => '',
    //         'com_cliente_id' => (int)$registro['com_cliente_id'],
    //         'com_cliente_rfc' => $registro['com_cliente_rfc'],
    //         'com_contacto_id' => (int)$registro['com_contacto_id']
    //     ];
    // }

    // VALIDACION PARA USUARIOS DEL SISTEMA

    public function valida_adm_usuario_factura(
        string $telefono_whatsapp,
        string $folio = '',
        int $accion_id = 0,
        string $rfc = '',
        
    ): array {
        $telefono_whatsapp = preg_replace('/\D+/', '', $telefono_whatsapp);
        $folio = trim($folio);
        $rfc = strtoupper(trim($rfc));

        if ($telefono_whatsapp === '') {
            return [
                'autorizado' => false,
                'status' => 'telefono_vacio',
                'mensaje' => 'El teléfono de WhatsApp es requerido'
            ];
        }

        if ($folio === '' && $rfc === '') {
            return [
                'autorizado' => false,
                'status' => 'datos_insuficientes',
                'mensaje' => 'Debes indicar folio o RFC para validar la solicitud'
            ];
        }

        $r_usuario = $this->obten_adm_usuario_por_telefono(
            telefono_whatsapp: $telefono_whatsapp
        );

        if (!$r_usuario['encontrado']) {
            return [
                'autorizado' => false,
                'status' => 'no_encontrado',
                'mensaje' => $r_usuario['mensaje']
            ];
        }

      
        if ($accion_id > 0) {
            $grupo_usuario = (int)$r_usuario['adm_grupo_id'];
            if (!$this->valida_seccion_permiso($accion_id, $grupo_usuario)) {
                return [
                    'autorizado' => false,
                    'status'     => 'sin_permiso',
                    'mensaje'    => 'No tienes acceso a esta opción'
                ];
            }
        }

        if ($folio !== '') {
            return $this->valida_adm_usuario_por_folio(
                telefono_whatsapp: $telefono_whatsapp,
                folio: $folio,
                rfc: $rfc,
                adm_usuario: $r_usuario
            );
        }

        return $this->valida_adm_usuario_por_rfc(
            telefono_whatsapp: $telefono_whatsapp,
            rfc: $rfc,
            adm_usuario: $r_usuario
        );
    }

    private function valida_seccion_permiso(int $accion_id, int $grupo_id): bool
    {
        $sql = "SELECT id FROM adm_accion_grupo 
                WHERE adm_accion_id = :accion_id 
                AND adm_grupo_id  = :grupo_id
                AND status = 'activo'
                LIMIT 1";
        $stmt = $this->link->prepare($sql);
        $stmt->execute([':accion_id' => $accion_id, ':grupo_id' => $grupo_id]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function obten_adm_usuario_por_telefono(string $telefono_whatsapp): array
    {
        $sql = "
            SELECT
                adm_usuario.id AS adm_usuario_id,
                adm_usuario.nombre,
                adm_usuario.ap,
                adm_usuario.am,
                adm_usuario.email,
                adm_usuario.adm_grupo_id,
                adm_grupo.codigo AS adm_grupo_codigo,
                adm_grupo.alias AS adm_grupo_alias,
                adm_grupo.descripcion AS adm_grupo_descripcion,
                adm_grupo.root AS adm_grupo_root
            FROM adm_usuario
            INNER JOIN adm_grupo
                ON adm_grupo.id = adm_usuario.adm_grupo_id
            WHERE CONCAT(adm_usuario.codigo_pais, adm_usuario.telefono) = :telefono_whatsapp
              AND adm_usuario.estatus_telefono = 'validado'
              AND adm_usuario.status = 'activo'
              AND adm_grupo.status = 'activo'
            LIMIT 1
        ";

        $stmt = $this->link->prepare($sql);
        $stmt->execute([':telefono_whatsapp' => $telefono_whatsapp]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return [
                'encontrado' => false,
                'mensaje' => 'Tu número no está registrado o no está validado en el sistema'
            ];
        }

        return [
            'encontrado' => true,
            'mensaje' => 'Usuario encontrado',
            'adm_usuario_id' => (int)$registro['adm_usuario_id'],
            'adm_grupo_id' => (int)$registro['adm_grupo_id'],
            'adm_grupo_codigo' => $registro['adm_grupo_codigo'],
            'adm_grupo_alias' => $registro['adm_grupo_alias'],
            'adm_grupo_descripcion' => $registro['adm_grupo_descripcion'],
            'adm_grupo_root' => $registro['adm_grupo_root']
        ];
    }

    private function valida_adm_usuario_por_folio(
        string $telefono_whatsapp,
        string $folio,
        string $rfc = '',
        array $adm_usuario = []
    ): array {
        $where_rfc = '';
        $params = [
            ':folio' => $folio
        ];

        if ($rfc !== '') {
            $where_rfc = ' AND com_cliente.rfc = :rfc ';
            $params[':rfc'] = $rfc;
        }

        $sql = "
            SELECT
                fc_factura.id AS fc_factura_id,
                fc_factura.folio AS fc_factura_folio,
                com_cliente.id AS com_cliente_id,
                com_cliente.rfc AS com_cliente_rfc
            FROM fc_factura
            INNER JOIN com_sucursal
                ON com_sucursal.id = fc_factura.com_sucursal_id
            INNER JOIN com_cliente
                ON com_cliente.id = com_sucursal.com_cliente_id
            WHERE fc_factura.folio = :folio
              $where_rfc
            LIMIT 1
        ";

        $stmt = $this->link->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return [
                'autorizado' => false,
                'status' => 'no_encontrado',
                'mensaje' => 'No se encontró ninguna factura con los datos proporcionados'
            ];
        }

        return [
            'autorizado' => true,
            'status' => 'autorizado',
            'tipo_validacion' => 'folio',
            'mensaje' => 'Solicitud autorizada por folio',
            'fc_factura_id' => (int)$registro['fc_factura_id'],
            'fc_factura_folio' => $registro['fc_factura_folio'],
            'com_cliente_id' => (int)$registro['com_cliente_id'],
            'com_cliente_rfc' => $registro['com_cliente_rfc'],
            'adm_usuario_id' => $adm_usuario['adm_usuario_id'] ?? 0,
            'adm_grupo_id' => $adm_usuario['adm_grupo_id'] ?? 0,
            'adm_grupo_codigo' => $adm_usuario['adm_grupo_codigo'] ?? '',
            'adm_grupo_alias' => $adm_usuario['adm_grupo_alias'] ?? ''
        ];
    }

    private function valida_adm_usuario_por_rfc(
        string $telefono_whatsapp,
        string $rfc,
        array $adm_usuario = []
    ): array {
        $params = [':rfc' => $rfc];

        $sql = "
            SELECT
                com_cliente.id AS com_cliente_id,
                com_cliente.rfc AS com_cliente_rfc
            FROM com_cliente
            WHERE com_cliente.rfc = :rfc
            LIMIT 1
        ";

        $stmt = $this->link->prepare($sql);
        $stmt->execute($params);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return [
                'autorizado' => false,
                'status' => 'no_encontrado',
                'mensaje' => 'No se encontró ningún cliente con el RFC proporcionado'
            ];
        }

        return [
            'autorizado' => true,
            'status' => 'autorizado',
            'tipo_validacion' => 'rfc',
            'mensaje' => 'Solicitud autorizada por RFC',
            'fc_factura_id' => 0,
            'fc_factura_folio' => '',
            'com_cliente_id' => (int)$registro['com_cliente_id'],
            'com_cliente_rfc' => $registro['com_cliente_rfc'],
            'adm_usuario_id' => $adm_usuario['adm_usuario_id'] ?? 0,
            'adm_grupo_id' => $adm_usuario['adm_grupo_id'] ?? 0,
            'adm_grupo_codigo' => $adm_usuario['adm_grupo_codigo'] ?? '',
            'adm_grupo_alias' => $adm_usuario['adm_grupo_alias'] ?? ''
        ];
    }
}