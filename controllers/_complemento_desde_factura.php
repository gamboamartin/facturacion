<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_complemento_desde_factura as ServicioComplemento;
use InvalidArgumentException;
use stdClass;
use Throwable;

trait _complemento_desde_factura
{
    public array $complemento_desde_factura_config = [];

    private function permiso_complemento_desde_factura(): bool
    {
        $id = (int)($_SESSION['usuario_id'] ?? 0);
        if ($id <= 0) { return false; }
        $q = $this->link->prepare("SELECT DISTINCT s.descripcion AS seccion,a.descripcion AS accion
            FROM adm_usuario u INNER JOIN adm_grupo g ON g.id=u.adm_grupo_id
            INNER JOIN adm_accion_grupo ag ON ag.adm_grupo_id=g.id INNER JOIN adm_accion a ON a.id=ag.adm_accion_id
            INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id
            WHERE u.id=? AND u.status='activo' AND g.status='activo' AND ag.status='activo'
            AND a.status='activo' AND s.status='activo' AND ((s.descripcion='fc_factura' AND a.descripcion IN ('lista','complemento_pago'))
            OR (s.descripcion='fc_complemento_pago' AND a.descripcion IN ('alta_bd','modifica')))"
            .($this->link->inTransaction() ? ' FOR UPDATE' : ''));
        return $q && $q->execute([$id]) && count($q->fetchAll(\PDO::FETCH_ASSOC)) === 4;
    }

    public function complemento_pago(bool $header = true, bool $ws = false): array|stdClass
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { $this->respuesta_complemento_desde_factura(); }
        try {
            $servicio = $this->servicio_complemento_desde_factura();
            $config = $servicio->preparar((int)$this->registro_id);
            if (empty($_SESSION['fc_complemento_factura_csrf'])) { $_SESSION['fc_complemento_factura_csrf'] = bin2hex(random_bytes(32)); }
            $config['csrf'] = $_SESSION['fc_complemento_factura_csrf'];
            $config['clave_solicitud'] = bin2hex(random_bytes(16));
            $config['endpoint'] = $this->obj_link->link_con_id(accion: 'complemento_pago', link: $this->link,
                registro_id: (int)$this->registro_id, seccion: 'fc_factura');
            $config['link_lista'] = $this->obj_link->link_sin_id(accion: 'lista', link: $this->link, seccion: 'fc_factura');
            if (!is_string($config['endpoint']) || !is_string($config['link_lista']) || errores::$error) {
                throw new InvalidArgumentException('No se pudo preparar el formulario del complemento.');
            }
            $this->complemento_desde_factura_config = $config;
            return new stdClass();
        } catch (Throwable $error) {
            return $this->retorno_error(mensaje: $error instanceof InvalidArgumentException ? $error->getMessage()
                : 'No se pudo preparar el complemento de pago.', data: [], header: $header, ws: $ws);
        }
    }

    private function servicio_complemento_desde_factura(): ServicioComplemento
    {
        return new ServicioComplemento($this->link, (int)($_SESSION['usuario_id'] ?? 0),
            fn(): bool => $this->permiso_complemento_desde_factura());
    }

    private function respuesta_complemento_desde_factura(): never
    {
        $teniaErrorSesion = array_key_exists('error_resultado', $_SESSION ?? []);
        $errorSesionAnterior = $_SESSION['error_resultado'] ?? null;
        $salidaAnterior = errores::$out;
        $banderaAnterior = errores::$error;
        $restaurado = false;
        $restaurar = static function () use (&$restaurado, $teniaErrorSesion, $errorSesionAnterior,
            $salidaAnterior, $banderaAnterior): void {
            if ($restaurado) { return; }
            if ($teniaErrorSesion) {
                $_SESSION['error_resultado'] = $errorSesionAnterior;
            } else {
                unset($_SESSION['error_resultado']);
            }
            errores::$out = $salidaAnterior;
            errores::$error = $banderaAnterior;
            $restaurado = true;
        };
        // El shutdown cubre los exit heredados que no ejecutan finally.
        register_shutdown_function($restaurar);
        unset($_SESSION['error_resultado']);
        errores::$out = [];
        errores::$error = false;
        $nivel = ob_get_level();
        ob_start(static fn(string $salida): string => '');
        $codigo = 200;
        try {
            $csrf = $_POST['csrf'] ?? '';
            if (!is_string($csrf) || !isset($_SESSION['fc_complemento_factura_csrf'])
                || !hash_equals($_SESSION['fc_complemento_factura_csrf'], $csrf)) {
                $codigo = 403;
                throw new InvalidArgumentException('La sesión del formulario venció. Actualiza la pantalla.');
            }
            if (!$this->permiso_complemento_desde_factura()) {
                $codigo = 403;
                throw new InvalidArgumentException('No tienes permiso para crear complementos de pago.');
            }
            $resultado = $this->servicio_complemento_desde_factura()->crear((int)$this->registro_id, $_POST);
            $link = $this->obj_link->link_con_id(accion: 'modifica', link: $this->link,
                registro_id: $resultado['fc_complemento_pago_id'], seccion: 'fc_complemento_pago');
            if (!is_string($link) || errores::$error) {
                throw new \RuntimeException('No se pudo generar el enlace del complemento creado.');
            }
            $respuesta = ['ok'=>true, 'datos'=>$resultado + ['link_modifica'=>$link]];
        } catch (InvalidArgumentException $error) {
            $codigo = $codigo === 200 ? 422 : $codigo;
            $respuesta = ['ok'=>false, 'mensaje'=>$error->getMessage()];
        } catch (Throwable $error) {
            $codigo = 500;
            $respuesta = ['ok'=>false, 'mensaje'=>'No se pudo completar la operación. Reenvía el mismo formulario para consultar su resultado sin duplicarlo.'];
            error_log('[facturacion.complemento_desde_factura] Operación interrumpida; no se llamó al PAC.');
        } finally {
            $restaurar();
        }
        while (ob_get_level() > $nivel) { ob_end_clean(); }
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($respuesta, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
