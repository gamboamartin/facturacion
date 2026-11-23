<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbrado_guardia;
use gamboamartin\facturacion\models\_timbrado_lote;
use gamboamartin\facturacion\models\_timbrado_pendientes;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use stdClass;
use Throwable;

/** Acción independiente: no invoca controladores con redirecciones dentro del lote. */
trait _timbrado_masivo
{
    public array $timbrado_masivo_config = [];

    /** Consulta los permisos actuales; una sesión anterior no conserva un permiso revocado. */
    private function permiso_timbrado_masivo(): bool
    {
        $id = (int)($_SESSION['usuario_id'] ?? 0);
        if ($id <= 0) {
            return false;
        }
        $q = $this->link->prepare("SELECT COUNT(DISTINCT a.descripcion)
            FROM adm_usuario u INNER JOIN adm_grupo g ON g.id=u.adm_grupo_id
            INNER JOIN adm_accion_grupo ag ON ag.adm_grupo_id=g.id
            INNER JOIN adm_accion a ON a.id=ag.adm_accion_id
            INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id
            WHERE u.id=? AND u.status='activo' AND g.status='activo' AND ag.status='activo'
              AND a.status='activo' AND s.status='activo' AND s.descripcion='fc_factura'
              AND a.descripcion IN ('lista','timbra_xml','timbrado_masivo')");
        return $q && $q->execute([$id]) && (int)$q->fetchColumn() === 3;
    }

    private function servicio_timbrado_lote(): _timbrado_lote
    {
        return new _timbrado_lote($this->link, (int)($_SESSION['usuario_id'] ?? 0),
            fn(): bool => $this->permiso_timbrado_masivo(),
            fn(int $id): array => $this->timbrar_elemento_lote($id));
    }

    public function timbrado_masivo(bool $header = true, bool $ws = false): array|stdClass
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->respuesta_timbrado_masivo();
        }
        if (!$this->permiso_timbrado_masivo()) {
            return $this->retorno_error(mensaje: 'No tienes permiso para consultar y timbrar facturas por lote.',
                data: [], header: $header, ws: $ws);
        }
        if (empty($_SESSION['fc_timbrado_csrf'])) {
            $_SESSION['fc_timbrado_csrf'] = bin2hex(random_bytes(32));
        }
        $endpoint = $this->obj_link->link_sin_id(accion: 'timbrado_masivo', link: $this->link, seccion: 'fc_factura');
        $lista = $this->obj_link->link_sin_id(accion: 'lista', link: $this->link, seccion: 'fc_factura');
        if (!is_string($endpoint) || !is_string($lista) || errores::$error) {
            return $this->retorno_error(mensaje: 'No se pudo preparar la pantalla de timbrado.', data: [], header: $header, ws: $ws);
        }
        $this->timbrado_masivo_config = ['endpoint' => $endpoint, 'csrf' => $_SESSION['fc_timbrado_csrf'],
            'link_lista' => $lista, 'maximo' => _timbrado_pendientes::MAXIMO,
            'emisores' => (new _timbrado_pendientes($this->link))->emisores(),
            'lote_id' => is_string($_GET['lote_id'] ?? null) ? $_GET['lote_id'] : ''];
        return new stdClass();
    }

    private function respuesta_timbrado_masivo(): never
    {
        // Descartar salida de componentes heredados: la API pública nunca expone sus volcados.
        $nivel = ob_get_level();
        ob_start(static fn(string $salida): string => '');
        $codigo = 200;
        try {
            $csrf = $_POST['csrf'] ?? '';
            if (!is_string($csrf) || !isset($_SESSION['fc_timbrado_csrf']) || !hash_equals($_SESSION['fc_timbrado_csrf'], $csrf)) {
                $codigo = 403;
                throw new InvalidArgumentException('La sesión del formulario venció. Actualiza la pantalla.');
            }
            if (!$this->permiso_timbrado_masivo()) {
                $codigo = 403;
                throw new InvalidArgumentException('No tienes permiso para realizar esta operación.');
            }
            $operacion = $_POST['operacion'] ?? '';
            if (!is_string($operacion)) {
                throw new InvalidArgumentException('Indica una operación válida.');
            }
            $servicio = $this->servicio_timbrado_lote();
            $datos = match ($operacion) {
                'listar' => $servicio->listar($this->json_masivo('filtros', []), max(1, (int)($_POST['pagina'] ?? 1))),
                'crear' => $servicio->crear($this->texto_masivo('clave_solicitud'), $this->texto_masivo('modo'),
                    $this->json_masivo('factura_ids', []), $this->json_masivo('filtros', [])),
                'estado' => $servicio->estado($this->texto_masivo('lote_id')),
                'procesar' => $servicio->procesar($this->texto_masivo('lote_id'), (int)$this->texto_masivo('factura_id')),
                default => throw new InvalidArgumentException('La operación solicitada no existe.')
            };
            $respuesta = ['ok' => true, 'datos' => $datos];
        } catch (InvalidArgumentException $error) {
            $codigo = $codigo === 200 ? 422 : $codigo;
            $respuesta = ['ok' => false, 'mensaje' => $error->getMessage()];
        } catch (Throwable $error) {
            $codigo = 500;
            $respuesta = ['ok' => false, 'mensaje' => 'No se pudo completar la operación. Consulta el estado del lote antes de volver a enviar.'];
            error_log('[facturacion.timbrado_masivo] Operación interrumpida; consultar el lote.');
        }
        while (ob_get_level() > $nivel) {
            ob_end_clean();
        }
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($respuesta, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function json_masivo(string $campo, array $defecto): array
    {
        $valor = $_POST[$campo] ?? null;
        if ($valor === null) {
            return $defecto;
        }
        if (!is_string($valor) || strlen($valor) > 20000) {
            throw new InvalidArgumentException('Los datos del formulario no son válidos.');
        }
        try {
            $datos = json_decode($valor, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $error) {
            throw new InvalidArgumentException('Los datos del formulario no son válidos.');
        }
        if (!is_array($datos)) {
            throw new InvalidArgumentException('Los datos del formulario no son válidos.');
        }
        return $datos;
    }

    private function texto_masivo(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';
        if (!is_string($valor) || strlen($valor) > 100) {
            throw new InvalidArgumentException('El identificador recibido no es válido.');
        }
        return $valor;
    }

    private function timbrar_elemento_lote(int $id): array
    {
        $sesionAnterior = $_SESSION['error_resultado'] ?? null;
        $salidaAnterior = errores::$out;
        $banderaAnterior = errores::$error;
        $limpiar = static function () use ($sesionAnterior, $salidaAnterior, $banderaAnterior): void {
            if ($sesionAnterior === null) {
                unset($_SESSION['error_resultado']);
            } else {
                $_SESSION['error_resultado'] = $sesionAnterior;
            }
            errores::$out = $salidaAnterior;
            errores::$error = $banderaAnterior;
        };
        // También protege una salida heredada con exit antes de devolver JSON.
        register_shutdown_function($limpiar);
        try {
            return $this->ejecutar_timbrado_elemento($id);
        } finally {
            $limpiar();
        }
    }

    private function ejecutar_timbrado_elemento(int $id): array
    {
        errores::$error = false;
        $modelo = new fc_factura($this->link);
        $resultado = $modelo->timbra_xml(
            modelo_documento: new fc_factura_documento($this->link), modelo_etapa: new fc_factura_etapa($this->link),
            modelo_partida: new fc_partida($this->link), modelo_predial: new fc_cuenta_predial($this->link),
            modelo_relacion: new fc_relacion($this->link), modelo_relacionada: new fc_factura_relacionada($this->link),
            modelo_retencion: new fc_retenido($this->link), modelo_sello: new fc_cfdi_sellado($this->link),
            modelo_traslado: new fc_traslado($this->link), modelo_uuid_ext: new fc_uuid_fc($this->link), registro_id: $id);
        $error = errores::$error;
        $q = $this->link->prepare('SELECT s.uuid,f.folio_fiscal FROM fc_cfdi_sellado s INNER JOIN fc_factura f ON f.id=s.fc_factura_id WHERE f.id=? ORDER BY s.id DESC LIMIT 2');
        $q->execute([$id]);
        $sellos = $q->fetchAll(PDO::FETCH_ASSOC);
        $uuid = count($sellos) === 1 ? trim((string)$sellos[0]['uuid']) : '';
        $previo = is_array($resultado) && is_array($resultado['data'] ?? null)
            ? $resultado['data'] : $resultado;
        $coherente = preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/iD', $uuid)
            && strcasecmp($uuid, trim((string)$sellos[0]['folio_fiscal'])) === 0;
        if ($coherente) {
            if (is_array($previo) && !empty($previo['ya_timbrada'])) {
                if (!empty($previo['requiere_revision'])) {
                    return ['estado' => 'revision_requerida', 'uuid' => $uuid, 'mensaje' => 'La factura tiene UUID local, pero su registro requiere revisión. No se volvió a enviar.'];
                }
                return ['estado' => 'omitida', 'uuid' => $uuid, 'mensaje' => 'La factura ya estaba timbrada. No se volvió a enviar.'];
            }
            if (!$error) {
                $modelo->actualiza_agente_operacion_en_fc_factura(fc_factura_id: $id, campo: 'agente_operacion_timbrado_id');
                $error = errores::$error;
            }
            return ['estado' => 'timbrada', 'uuid' => $uuid,
                'mensaje' => $error ? 'Factura timbrada. Hay un aviso de registro local que requiere revisión.' : 'Factura timbrada correctamente.'];
        }
        if ($sellos) {
            return ['estado' => 'revision_requerida', 'mensaje' => 'Existe evidencia fiscal local inconsistente. No se volverá a enviar la factura.'];
        }
        $intento = (new _timbrado_guardia($this->link))->estado($id);
        $revision = in_array($intento['estado'] ?? '', ['enviado', 'aceptado', 'completo', 'revision_requerida'], true);
        return ['estado' => $revision ? 'revision_requerida' : 'rechazada', 'codigo' => $intento['codigo'] ?? '',
            'mensaje' => $revision ? 'El resultado del PAC requiere revisión. No se reenviará automáticamente.'
                : (($intento['codigo'] ?? '') !== '' ? (string)$intento['mensaje']
                    : 'No se pudo timbrar. Revisa las validaciones de la factura antes de volver a seleccionarla.')];
    }
}
