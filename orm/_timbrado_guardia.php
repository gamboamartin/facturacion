<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use PDO;
use RuntimeException;
use Throwable;

/** Exclusión compartida y evidencia durable: un envío incierto nunca se reintenta solo. */
final class _timbrado_guardia
{
    private const RECHAZOS_CONFIRMADOS = ['CFDI40130', 'CFDI40145'];
    private bool $esquema_validado = false;
    private int $factura_activa = 0;
    private int $csd_activo = 0;
    private string $intento = '';

    public function __construct(private readonly PDO $link)
    {
    }

    public function ejecutar(int $facturaId, callable $operacion): mixed
    {
        if ($facturaId <= 0 || $this->factura_activa !== 0) {
            throw new RuntimeException('El intento de timbrado requiere una factura válida y una operación independiente.');
        }
        $this->autocommit();
        $this->esquema();
        $base = $this->consulta('SELECT DATABASE()')->fetchColumn();
        if (!is_string($base) || $base === '') {
            throw new RuntimeException('No se pudo identificar la base de facturación.');
        }
        $cerrojo = 'fc_timbra_'.substr(hash('sha256', $base.':'.$facturaId), 0, 52);
        if ((int)$this->consulta('SELECT GET_LOCK(?, 0)', [$cerrojo])->fetchColumn() !== 1) {
            throw new RuntimeException('Esta factura se está procesando; consulte su resultado antes de intentar nuevamente.');
        }
        $fallo = null;
        try {
            $factura = $this->factura($facturaId);
            $anterior = $this->leer($facturaId);
            $local = $this->identidad_local($facturaId, $factura);
            if ($local['existe']) {
                if ($anterior === null) {
                    $this->iniciar($facturaId);
                    $this->guardar(
                        $local['consistente'] ? 'completo' : 'revision_requerida', $local['uuid'], 'YA_TIMBRADA',
                        'La factura ya tiene evidencia fiscal local; no se realizó otro envío.'
                    );
                    $anterior = $this->leer($facturaId);
                }
                if ($local['uuid'] === '') {
                    throw new RuntimeException('La factura tiene evidencia fiscal local inconsistente y requiere revisión. No se realizará otro envío.');
                }
                return [
                    'uuid' => $local['uuid'], 'ya_timbrada' => true,
                    'advertencia' => 'La factura ya tiene evidencia fiscal local; no se realizó otro envío.',
                    'requiere_revision' => !$local['consistente'] || ($anterior['estado'] ?? '') !== 'completo',
                ];
            }
            if ($anterior !== null && !in_array($anterior['estado'], ['preparando', 'rechazado'], true)) {
                throw new RuntimeException('Existe un intento de timbrado pendiente de revisión. No se reenviará la factura.');
            }
            $this->csd_activo = (int)$factura['fc_csd_id'];
            return (new bloqueo_uso_csd($this->link))->ejecutar($this->csd_activo, function () use ($facturaId, $operacion): mixed {
                if ((int)$this->factura($facturaId)['fc_csd_id'] !== $this->csd_activo) {
                    throw new RuntimeException('Cambió el CSD de la factura; revise sus datos antes de timbrar.');
                }
                $this->iniciar($facturaId);
                try {
                    $resultado = $operacion();
                    $error_legacy = errores::$error;
                    $this->autocommit();
                    $actual = $this->leer($facturaId);
                    if ($error_legacy || (is_array($resultado) && !empty($resultado['error']))) {
                        $this->marcar_fallo($actual);
                        return $resultado;
                    }
                    $local = $this->identidad_local($facturaId, $this->factura($facturaId));
                    if (($actual['estado'] ?? '') !== 'aceptado' || !$local['consistente']
                        || strcasecmp($local['uuid'], $actual['uuid']) !== 0) {
                        $this->marcar_fallo($actual);
                        throw new RuntimeException('El resultado fiscal requiere revisión antes de cualquier nuevo envío.');
                    }
                    $this->guardar('completo', $local['uuid'], 'TIMBRADA', 'La factura se timbró y quedó registrada.');
                    return $resultado;
                } catch (Throwable) {
                    // La entrada exige autocommit: cualquier TX abierta aquí pertenece a
                    // esta operación, nunca al llamador. El envío durable ya quedó fuera de ella.
                    if ($this->link->inTransaction()) {
                        $this->link->rollBack();
                    }
                    try {
                        $this->marcar_fallo($this->leer($facturaId));
                    } catch (Throwable) {
                        // Si se perdió la conexión, 'enviado' persistido sigue impidiendo reenvíos.
                    }
                    throw new RuntimeException('No se pudo completar el timbrado. Consulte el estado del intento antes de continuar.');
                }
            });
        } catch (Throwable $error) {
            $fallo = $error;
            throw $error;
        } finally {
            $this->factura_activa = 0;
            $this->csd_activo = 0;
            $this->intento = '';
            try {
                if ((int)$this->consulta('SELECT RELEASE_LOCK(?)', [$cerrojo])->fetchColumn() !== 1) {
                    throw new RuntimeException('No se pudo liberar el bloqueo de la factura; consulte el resultado del intento.');
                }
            } catch (Throwable) {
                if ($fallo === null) {
                    throw new RuntimeException('No se pudo liberar el bloqueo de la factura; consulte el resultado del intento.');
                }
            }
        }
    }

    public function antes_pac(int $id): void
    {
        $this->activo($id, 'preparando');
        $this->autocommit();
        $factura = $this->factura($id);
        if ((int)$factura['fc_csd_id'] !== $this->csd_activo) {
            throw new RuntimeException('Cambió el CSD durante la preparación; no se envió el comprobante.');
        }
        if ($this->identidad_local($id, $factura)['existe']) {
            throw new RuntimeException('La factura ya tiene evidencia fiscal local; no se realizará otro envío.');
        }
        if ($this->consulta("UPDATE fc_timbrado_intento SET estado = 'enviado', codigo = 'ENVIO_INICIADO', "
            ."mensaje = 'Se inició el envío al PAC.', fecha_update = CURRENT_TIMESTAMP "
            ."WHERE factura_id = ? AND intento = ? AND estado = 'preparando'", [$id, $this->intento])->rowCount() !== 1) {
            throw new RuntimeException('No se pudo registrar el envío; el comprobante no debe enviarse al PAC.');
        }
    }

    public function respuesta_pac(int $id, mixed $respuesta, bool $error): void
    {
        $this->activo($id, 'enviado');
        $this->autocommit();
        $xml = is_object($respuesta) && isset($respuesta->xml_sellado) && is_string($respuesta->xml_sellado)
            ? $respuesta->xml_sellado : null;
        if ($xml !== null && $xml !== '') {
            $uuid = $this->uuid_xml($xml);
            $this->guardar(
                $uuid === '' ? 'revision_requerida' : 'aceptado', $uuid,
                $uuid === '' ? 'RESPUESTA_NO_VERIFICADA' : 'PAC_ACEPTADO',
                $uuid === '' ? 'La respuesta del PAC requiere revisión.' : 'El PAC devolvió un comprobante timbrado.',
                $xml
            );
            if ($uuid === '') {
                throw new RuntimeException('No se pudo verificar el UUID de la respuesta; no se reenviará el comprobante.');
            }
            return;
        }
        // Contrato concreto de xml_cfdi_4::timbra: error del PAC con resultado
        // posicional [0]=Error, [6]=código confirmado de rechazo. La forma CFDIxxxxx
        // por sí sola no prueba rechazo; no buscar texto en JSON/XML/errores anidados.
        $datos = is_array($respuesta) && !empty($respuesta['error']) ? ($respuesta['data'] ?? null) : null;
        $codigo = is_array($datos) ? ($datos[6] ?? null) : null;
        if ($error && is_array($datos) && ($datos[0] ?? null) === 'Error' && is_string($codigo)
            && in_array($codigo, self::RECHAZOS_CONFIRMADOS, true)) {
            $this->guardar('rechazado', '', $codigo, 'El PAC rechazó el comprobante ('.$codigo.').');
            return;
        }
        $this->guardar('revision_requerida', '', 'RESPUESTA_INCIERTA', 'No se pudo confirmar el resultado del envío al PAC.');
        throw new RuntimeException('La respuesta del PAC requiere revisión; no se reenviará el comprobante.');
    }

    /** No devuelve XML, datos del proveedor, trazas ni contenido de excepciones. */
    public function estado(int $id): ?array
    {
        if ($id <= 0) {
            throw new RuntimeException('El identificador de factura debe ser positivo.');
        }
        $this->esquema();
        $estado = $this->leer($id);
        if ($estado === null) {
            return null;
        }
        return array_intersect_key($estado, array_flip(['estado', 'uuid', 'codigo', 'mensaje']));
    }

    private function iniciar(int $id): void
    {
        $this->autocommit();
        $this->factura_activa = $id;
        $this->intento = bin2hex(random_bytes(16));
        $this->consulta("INSERT INTO fc_timbrado_intento (factura_id,intento,estado,uuid,codigo,mensaje,xml_respuesta) "
            ."VALUES (?,?,'preparando','','PREPARANDO','Se está preparando el comprobante.',NULL) "
            ."ON DUPLICATE KEY UPDATE intento=VALUES(intento),estado=VALUES(estado),uuid='',codigo=VALUES(codigo),"
            ."mensaje=VALUES(mensaje),xml_respuesta=NULL,fecha_update=CURRENT_TIMESTAMP", [$id, $this->intento]);
    }

    private function guardar(string $estado, string $uuid, string $codigo, string $mensaje, ?string $xml = null): void
    {
        $this->autocommit();
        $sql = 'UPDATE fc_timbrado_intento SET estado=?,uuid=?,codigo=?,mensaje=?,fecha_update=CURRENT_TIMESTAMP';
        $valores = [$estado, $uuid, $codigo, $mensaje];
        if ($xml !== null) {
            $sql .= ',xml_respuesta=?';
            $valores[] = $xml;
        }
        $sql .= ' WHERE factura_id=? AND intento=?';
        array_push($valores, $this->factura_activa, $this->intento);
        $this->consulta($sql, $valores);
        $actual = $this->leer($this->factura_activa);
        if (($actual['intento'] ?? '') !== $this->intento || ($actual['estado'] ?? '') !== $estado) {
            throw new RuntimeException('No se pudo conservar el estado del intento de timbrado.');
        }
    }

    private function marcar_fallo(?array $actual): void
    {
        if ($actual === null || ($actual['intento'] ?? '') !== $this->intento) {
            return;
        }
        if (in_array($actual['estado'], ['enviado', 'aceptado'], true)) {
            $this->guardar('revision_requerida', $actual['uuid'], 'REVISION_REQUERIDA',
                'El envío puede haberse timbrado; revise el intento antes de continuar.');
        } elseif ($actual['estado'] === 'preparando') {
            $this->guardar('preparando', '', 'FALLO_PREVIO', 'No se completó la preparación; no se inició el envío al PAC.');
        }
    }

    private function activo(int $id, string $estado): void
    {
        $actual = $this->leer($id);
        if ($id !== $this->factura_activa || $this->intento === ''
            || ($actual['intento'] ?? '') !== $this->intento || ($actual['estado'] ?? '') !== $estado) {
            throw new RuntimeException('La fase del intento de timbrado no permite esta operación.');
        }
    }

    private function factura(int $id): array
    {
        $fila = $this->consulta('SELECT id,fc_csd_id,folio_fiscal FROM fc_factura WHERE id=?', [$id])->fetch(PDO::FETCH_ASSOC);
        if ($fila === false) {
            throw new RuntimeException('La factura no existe.');
        }
        return $fila;
    }

    private function identidad_local(int $id, array $factura): array
    {
        $sellos = $this->consulta('SELECT uuid FROM fc_cfdi_sellado WHERE fc_factura_id=? ORDER BY id LIMIT 2', [$id])
            ->fetchAll(PDO::FETCH_COLUMN);
        $folio = trim((string)$factura['folio_fiscal']);
        // Valor predeterminado del esquema legado: no representa evidencia fiscal.
        // Solo normalizar esta lectura; los sellos existentes nunca se ignoran.
        if (strcasecmp($folio, 'SIN UUID') === 0) {
            $folio = '';
        }
        $sello = count($sellos) === 1 ? trim((string)$sellos[0]) : '';
        $uuid = $this->uuid_valido($sello) ? $sello : ($this->uuid_valido($folio) ? $folio : '');
        return [
            'existe' => $sellos !== [] || $folio !== '',
            'uuid' => $uuid,
            'consistente' => $uuid !== '' && count($sellos) === 1 && strcasecmp($sello, $folio) === 0,
        ];
    }

    private function uuid_xml(string $xml): string
    {
        if (stripos($xml, '<!DOCTYPE') !== false) {
            return '';
        }
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            if (!$dom->loadXML($xml, LIBXML_NONET)) {
                return '';
            }
            $raiz = $dom->documentElement;
            if ($raiz === null || $raiz->localName !== 'Comprobante' || $raiz->namespaceURI !== 'http://www.sat.gob.mx/cfd/4') {
                return '';
            }
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            $nodos = $xpath->query('/cfdi:Comprobante/cfdi:Complemento/tfd:TimbreFiscalDigital');
            if ($nodos === false || $nodos->length !== 1) {
                return '';
            }
            $uuid = $nodos->item(0)->getAttribute('UUID');
            return $this->uuid_valido($uuid) ? $uuid : '';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }

    private function uuid_valido(string $uuid): bool
    {
        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $uuid) === 1;
    }

    private function leer(int $id): ?array
    {
        $fila = $this->consulta('SELECT intento,estado,uuid,codigo,mensaje FROM fc_timbrado_intento WHERE factura_id=?', [$id])
            ->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    private function autocommit(): void
    {
        if ($this->link->inTransaction() || (int)$this->consulta('SELECT @@SESSION.autocommit')->fetchColumn() !== 1) {
            throw new RuntimeException('El timbrado requiere autocommit y no admite una transacción abierta.');
        }
    }

    private function esquema(): void
    {
        if ($this->esquema_validado) {
            return;
        }
        try {
            $motor = $this->consulta("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() "
                ."AND TABLE_NAME='fc_timbrado_intento' AND TABLE_TYPE='BASE TABLE'")->fetchColumn();
            if (!is_string($motor) || strcasecmp($motor, 'InnoDB') !== 0) {
                throw new RuntimeException();
            }
            $this->consulta('SELECT factura_id,intento,estado,uuid,codigo,mensaje,xml_respuesta,fecha_update '
                .'FROM fc_timbrado_intento WHERE 1=0');
        } catch (Throwable) {
            throw new RuntimeException('Falta aplicar o actualizar la migración de control de intentos de timbrado.');
        }
        $this->esquema_validado = true;
    }

    private function consulta(string $sql, array $valores = []): \PDOStatement
    {
        $consulta = $this->link->prepare($sql);
        if ($consulta === false || !$consulta->execute($valores)) {
            throw new RuntimeException('No se pudo consultar o conservar el control de timbrado.');
        }
        return $consulta;
    }
}
