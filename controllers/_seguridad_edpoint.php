<?php

namespace gamboamartin\facturacion\controllers;

use PDO;

class SeguridadEndpoint
{
    private PDO $link;

    public function __construct(PDO $link)
    {
        $this->link = $link;
    }

    public function valida_contacto_cliente_factura(
        string $telefono_whatsapp,
        string $folio = '',
        string $rfc = ''
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

        if ($folio !== '') {
            return $this->valida_por_folio(
                telefono_whatsapp: $telefono_whatsapp,
                folio: $folio,
                rfc: $rfc
            );
        }

        return $this->valida_por_rfc(
            telefono_whatsapp: $telefono_whatsapp,
            rfc: $rfc
        );
    }

    private function valida_por_folio(
        string $telefono_whatsapp,
        string $folio,
        string $rfc = ''
    ): array {
        $where_rfc = '';
        $params = [
            ':telefono_whatsapp' => $telefono_whatsapp,
            ':folio' => $folio,
            ':estatus_telefono' => 'validado'
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
                com_cliente.rfc AS com_cliente_rfc,
                com_contacto.id AS com_contacto_id
            FROM fc_factura
            INNER JOIN com_sucursal
                ON com_sucursal.id = fc_factura.com_sucursal_id
            INNER JOIN com_cliente
                ON com_cliente.id = com_sucursal.com_cliente_id
            INNER JOIN com_contacto
                ON com_contacto.com_cliente_id = com_cliente.id
            WHERE fc_factura.folio = :folio
              AND CONCAT(com_contacto.codigo_pais, com_contacto.telefono) = :telefono_whatsapp
              AND com_contacto.estatus_telefono = :estatus_telefono
              $where_rfc
            LIMIT 1
        ";

        $stmt = $this->link->prepare($sql);
        $stmt->execute($params);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return [
                'autorizado' => false,
                'status' => 'no_autorizado',
                'mensaje' => 'Tu número no está asociado al cliente de la factura solicitada'
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
            'com_contacto_id' => (int)$registro['com_contacto_id']
        ];
    }

    private function valida_por_rfc(
        string $telefono_whatsapp,
        string $rfc
    ): array {
        $params = [
            ':telefono_whatsapp' => $telefono_whatsapp,
            ':rfc' => $rfc,
            ':estatus_telefono' => 'validado'
        ];

        $sql = "
            SELECT
                com_cliente.id AS com_cliente_id,
                com_cliente.rfc AS com_cliente_rfc,
                com_contacto.id AS com_contacto_id
            FROM com_cliente
            INNER JOIN com_contacto
                ON com_contacto.com_cliente_id = com_cliente.id
            WHERE com_cliente.rfc = :rfc
              AND CONCAT(com_contacto.codigo_pais, com_contacto.telefono) = :telefono_whatsapp
              AND com_contacto.estatus_telefono = :estatus_telefono
            LIMIT 1
        ";

        $stmt = $this->link->prepare($sql);
        $stmt->execute($params);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return [
                'autorizado' => false,
                'status' => 'no_autorizado',
                'mensaje' => 'Tu número no está asociado al cliente solicitado'
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
            'com_contacto_id' => (int)$registro['com_contacto_id']
        ];
    }
}