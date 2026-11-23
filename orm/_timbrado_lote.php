<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use Closure;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/** Lotes acotados y durables. Cada petición ejecuta como máximo una factura. */
final class _timbrado_lote
{
    private Closure $autorizar;
    private Closure $timbrar;

    public function __construct(private readonly PDO $link, private readonly int $usuario,
        callable $autorizar, callable $timbrar)
    {
        $this->autorizar = Closure::fromCallable($autorizar);
        $this->timbrar = Closure::fromCallable($timbrar);
    }

    private function permiso(): void
    {
        if ($this->usuario <= 0 || ($this->autorizar)() !== true) {
            throw new RuntimeException('No tienes permiso para realizar el timbrado masivo.');
        }
    }

    private function ejecutar(string $sql, array $params = []): \PDOStatement
    {
        $q = $this->link->prepare($sql);
        if (!$q || !$q->execute($params)) {
            throw new RuntimeException('No se pudo guardar o consultar el lote.');
        }
        return $q;
    }

    private function autocommit(): void
    {
        if ($this->link->inTransaction() || (int)$this->link->query('SELECT @@SESSION.autocommit')->fetchColumn() !== 1) {
            throw new RuntimeException('El lote requiere una operación independiente, sin transacciones abiertas.');
        }
    }

    public function listar(array $filtros, int $pagina = 1): array
    {
        $this->permiso();
        return (new _timbrado_pendientes($this->link))->listar($filtros, $pagina);
    }

    public function crear(string $clave, string $modo, array $ids, array $entrada): array
    {
        $this->permiso();
        $this->autocommit();
        if (!preg_match('/^[a-f0-9]{32}$/D', $clave)) {
            throw new InvalidArgumentException('La clave de solicitud no es válida. Actualiza la pantalla.');
        }
        $filtros = _timbrado_pendientes::filtros($entrada);
        $huella = hash('sha256', json_encode([$modo, $ids, $filtros], JSON_THROW_ON_ERROR));
        $previo = $this->ejecutar('SELECT id,huella FROM fc_timbrado_lote WHERE usuario_id=? AND clave_solicitud=?', [$this->usuario, $clave])->fetch(PDO::FETCH_ASSOC);
        if ($previo) {
            return $this->repetida($previo, $huella);
        }
        $filas = (new _timbrado_pendientes($this->link))->seleccionar($filtros, $modo, $ids);
        if ($modo === 'todas') {
            $esperados = array_column($filas, 'id');
            $seleccion = array_values(array_unique(array_map(static function ($id): int {
                if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]{0,17}$/D', (string)$id)) {
                    throw new InvalidArgumentException('La selección revisada no es válida. Actualiza la lista.');
                }
                return (int)$id;
            }, $ids)));
            sort($esperados);sort($seleccion);
            if ($esperados !== $seleccion) {
                throw new InvalidArgumentException('Las facturas que cumplen el filtro cambiaron. Actualiza la lista y revisa la selección antes de timbrar.');
            }
        }
        if ($this->link->inTransaction()) {
            throw new RuntimeException('Finaliza la operación anterior antes de crear un lote.');
        }
        $token = bin2hex(random_bytes(16));
        $this->link->beginTransaction();
        try {
            $this->ejecutar('INSERT INTO fc_timbrado_lote (id,usuario_id,clave_solicitud,huella,filtros) VALUES (?,?,?,?,?)',
                [$token, $this->usuario, $clave, $huella, json_encode($filtros, JSON_THROW_ON_ERROR)]);
            foreach ($filas as $posicion => $fila) {
                $this->ejecutar('INSERT INTO fc_timbrado_lote_item (lote_id,factura_id,posicion,empresa_id,folio) VALUES (?,?,?,?,?)',
                    [$token, $fila['id'], $posicion + 1, $fila['empresa_id'], self::folio($fila)]);
            }
            $this->link->commit();
        } catch (Throwable $error) {
            if ($this->link->inTransaction()) {
                $this->link->rollBack();
            }
            // La clave por usuario hace idempotentes dos POST de creación concurrentes.
            if ($error instanceof PDOException && $error->getCode() === '23000') {
                $previo = $this->ejecutar('SELECT id,huella FROM fc_timbrado_lote WHERE usuario_id=? AND clave_solicitud=?', [$this->usuario, $clave])->fetch(PDO::FETCH_ASSOC);
                if ($previo) {
                    return $this->repetida($previo, $huella);
                }
            }
            throw $error;
        }
        return $this->estado($token);
    }

    private function repetida(array $previo, string $huella): array
    {
        if (!hash_equals($previo['huella'], $huella)) {
            throw new InvalidArgumentException('La misma solicitud contiene otra selección. Actualiza la pantalla.');
        }
        return $this->estado($previo['id']);
    }

    private function lote(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $token)) {
            throw new InvalidArgumentException('El identificador de lote no es válido.');
        }
        $fila = $this->ejecutar('SELECT * FROM fc_timbrado_lote WHERE id=? AND usuario_id=?', [$token, $this->usuario])->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new RuntimeException('El lote no existe o no pertenece a tu usuario.');
        }
        return $fila;
    }

    private function nombre_bloqueo(string $token): string
    {
        $base = $this->link->query('SELECT DATABASE()')->fetchColumn();
        return 'fc_lote_'.substr(hash('sha256', $base.':'.$token), 0, 54);
    }

    /** Sin transacción larga: el bloqueo de conexión serializa las peticiones del mismo lote. */
    private function bloqueado(string $token, callable $operacion): array
    {
        $this->permiso();
        $this->autocommit();
        $lote = $this->lote($token);
        $nombre = $this->nombre_bloqueo($token);
        if ((int)$this->ejecutar('SELECT GET_LOCK(?,0)', [$nombre])->fetchColumn() !== 1) {
            return $this->resumen($token);
        }
        try {
            return $operacion($lote);
        } finally {
            $this->ejecutar('SELECT RELEASE_LOCK(?)', [$nombre]);
        }
    }

    public function estado(string $token): array
    {
        return $this->bloqueado($token, function () use ($token): array {
            $this->interrumpidos($token);
            return $this->resumen($token);
        });
    }

    public function procesar(string $token, int $factura_id): array
    {
        return $this->bloqueado($token, function (array $lote) use ($token, $factura_id): array {
            $this->interrumpidos($token);
            $item = $this->ejecutar('SELECT * FROM fc_timbrado_lote_item WHERE lote_id=? AND factura_id=?', [$token, $factura_id])->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                throw new InvalidArgumentException('La factura no pertenece a este lote.');
            }
            if ($item['estado'] !== 'pendiente') {
                return $this->resumen($token);
            }
            $this->permiso();
            $this->ejecutar("UPDATE fc_timbrado_lote_item SET estado='procesando',mensaje='Procesando factura' WHERE id=? AND estado='pendiente'", [$item['id']]);
            try {
                $control = $this->ejecutar('SELECT estado FROM fc_timbrado_intento WHERE factura_id=?', [$item['factura_id']])->fetchColumn();
                if (in_array($control, ['enviado', 'aceptado', 'completo', 'revision_requerida'], true)) {
                    $this->guardar($item, $this->resultado_interrumpido((int)$item['factura_id']));
                    return $this->resumen($token);
                }
                $filtros = json_decode($lote['filtros'], true, 512, JSON_THROW_ON_ERROR);
                try {
                    $actual = (new _timbrado_pendientes($this->link))->seleccionar($filtros, 'ids', [(int)$item['factura_id']])[0];
                } catch (InvalidArgumentException $error) {
                    $this->guardar($item, ['estado' => 'omitida', 'mensaje' => 'La factura ya no está pendiente o dejó de cumplir los filtros.']);
                    return $this->resumen($token);
                }
                if ((int)$actual['empresa_id'] !== (int)$item['empresa_id']) {
                    $this->guardar($item, ['estado' => 'omitida', 'mensaje' => 'El emisor de la factura cambió. Actualiza la selección.']);
                    return $this->resumen($token);
                }
                $this->permiso();
                $resultado = ($this->timbrar)((int)$item['factura_id']);
                $this->guardar($item, $resultado);
            } catch (Throwable $error) {
                if ($this->link->inTransaction()) {
                    $this->link->rollBack();
                }
                // Nunca interpretar un fallo de transporte como permiso para reenviar.
                $this->guardar($item, $this->resultado_interrumpido((int)$item['factura_id']));
            }
            return $this->resumen($token);
        });
    }

    private function resultado_interrumpido(int $id): array
    {
        $sellos = $this->ejecutar('SELECT s.uuid,f.folio_fiscal FROM fc_cfdi_sellado s INNER JOIN fc_factura f ON f.id=s.fc_factura_id WHERE f.id=? ORDER BY s.id DESC LIMIT 2', [$id])->fetchAll(PDO::FETCH_ASSOC);
        $uuid = count($sellos) === 1 ? trim((string)$sellos[0]['uuid']) : '';
        if (preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/iD', $uuid)
            && strcasecmp($uuid, trim((string)$sellos[0]['folio_fiscal'])) === 0) {
            return ['estado' => 'timbrada', 'uuid' => $uuid, 'mensaje' => 'Timbrada. Revisa los avisos de la operación posterior.'];
        }
        $cabecera = $this->ejecutar('SELECT folio_fiscal FROM fc_factura WHERE id=?', [$id])->fetchColumn();
        $folioFiscal = trim((string)$cabecera);
        if ($sellos || ($cabecera !== false && $folioFiscal !== '' && strcasecmp($folioFiscal, 'SIN UUID') !== 0)) {
            return ['estado' => 'revision_requerida', 'mensaje' => 'Existe evidencia fiscal local inconsistente. Revisa la factura antes de continuar.'];
        }
        $intento = $this->ejecutar('SELECT estado FROM fc_timbrado_intento WHERE factura_id=?', [$id])->fetchColumn();
        if ($intento === false || in_array($intento, ['preparando', 'rechazado'], true)) {
            return ['estado' => 'rechazada', 'mensaje' => 'La operación se interrumpió antes de confirmar un envío. Revisa la factura antes de crear otro lote.'];
        }
        return ['estado' => 'revision_requerida', 'mensaje' => 'El resultado del PAC requiere revisión. No vuelvas a enviar esta factura hasta verificarlo.'];
    }

    /** Un proceso muerto libera GET_LOCK; solo entonces es seguro clasificar el ítem abandonado. */
    private function interrumpidos(string $token): void
    {
        $items = $this->ejecutar("SELECT * FROM fc_timbrado_lote_item WHERE lote_id=? AND estado='procesando'", [$token])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $this->guardar($item, $this->resultado_interrumpido((int)$item['factura_id']));
        }
    }

    private function guardar(array $item, array $resultado): void
    {
        if (!in_array($resultado['estado'] ?? '', ['timbrada', 'omitida', 'rechazada', 'revision_requerida'], true)) {
            throw new RuntimeException('El procesamiento no devolvió un resultado válido.');
        }
        $mensaje = mb_substr(strip_tags((string)($resultado['mensaje'] ?? 'Operación terminada.')), 0, 500);
        $this->ejecutar('UPDATE fc_timbrado_lote_item SET estado=?,mensaje=?,uuid=?,codigo=? WHERE id=?',
            [$resultado['estado'], $mensaje, (string)($resultado['uuid'] ?? ''), (string)($resultado['codigo'] ?? ''), $item['id']]);
    }

    private function resumen(string $token): array
    {
        $items = $this->ejecutar('SELECT factura_id,folio,estado,mensaje,uuid FROM fc_timbrado_lote_item WHERE lote_id=? ORDER BY posicion', [$token])->fetchAll(PDO::FETCH_ASSOC);
        $resumen = ['total' => count($items), 'pendientes' => 0, 'procesando' => 0, 'timbradas' => 0, 'omitidas' => 0, 'rechazadas' => 0, 'revision_requerida' => 0];
        $claves = ['pendiente' => 'pendientes', 'procesando' => 'procesando', 'timbrada' => 'timbradas', 'omitida' => 'omitidas', 'rechazada' => 'rechazadas', 'revision_requerida' => 'revision_requerida'];
        foreach ($items as &$item) {
            $item['factura_id'] = (int)$item['factura_id'];
            $resumen[$claves[$item['estado']]]++;
        }
        return ['lote_id' => $token, 'estado' => $resumen['pendientes'] + $resumen['procesando'] === 0 ? 'terminado' : 'pendiente',
            'resumen' => $resumen, 'resultados' => $items];
    }

    private static function folio(array $fila): string
    {
        $serie = trim((string)$fila['serie']);
        $folio = trim((string)$fila['folio']);
        return $serie !== '' && $folio !== '' && $folio !== $serie
            && !str_starts_with($folio, $serie.'-') && !str_starts_with($folio, $serie.' ')
            ? $serie.'-'.$folio : $folio;
    }
}
