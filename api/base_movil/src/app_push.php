<?php
namespace base_movil\v1\src;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\base_movil_push_token;
use PDO;

/**
 * Notificaciones push de la app (#7 parte 3).
 *
 * - registra_push_token: la app manda el token de su dispositivo después del
 *   login. Queda ligado al usuario y a la sesión: al cerrar esa sesión el
 *   token se da de baja (app_sesion::cierra_sesion).
 * - envia_a_usuario: envía a todos los dispositivos activos de un usuario.
 *   Es lo que usará cualquier parte del sistema que quiera notificar.
 * - envia_notificacion_prueba: el usuario de la sesión se manda una a sí
 *   mismo. Solo funciona con push_prueba = true en generales (local o
 *   pruebas); en producción queda apagado.
 *
 * Igual que las demás clases: los métodos públicos devuelven arrays y el
 * router decide la salida. Todas las consultas van por el modelo
 * base_movil_push_token (filtro_and, alta_bd, modifica_bd). El token que
 * manda el teléfono solo entra como valor en `filtro` o en el registro,
 * nunca como clave ni en filtro_rango.
 */
class app_push
{
    /** Proveedores con adaptador en la app. Agregar uno = su adaptador allá y aquí. */
    private const PROVEEDORES = array('fcm');
    private const PLATAFORMAS = array('android');
    private const LARGO_MAX_TOKEN = 512;

    private errores $errores;
    private PDO $link;
    private generales $generales;
    private app_sesion $sesion;

    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->link = $link;
        $this->generales = new generales();
        $this->sesion = new app_sesion($link);
    }

    /**
     * Recibe { token, provider, platform }. Si el token ya existía (el mismo
     * teléfono, otro login), la fila se actualiza con el usuario y la sesión
     * actuales y vuelve a quedar activa.
     */
    public function registra_push_token(): array
    {
        $claims = $this->sesion->valida_token();
        if (isset($claims['error'])) {
            errores::$error = false;
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            return $this->error_http(400, 'Error el body debe ser un objeto JSON');
        }

        $token = $body['token'] ?? null;
        $proveedor = $body['provider'] ?? null;
        $plataforma = $body['platform'] ?? null;

        // Un token de FCM son letras, números y : _ -. Cualquier otra cosa se rechaza.
        if (!is_string($token) || $token === '' || strlen($token) > self::LARGO_MAX_TOKEN
            || preg_match('/^[A-Za-z0-9:_\-]+$/', $token) !== 1) {
            return $this->error_http(400, 'Error token inválido');
        }
        if (!is_string($proveedor) || !in_array($proveedor, self::PROVEEDORES, true)) {
            return $this->error_http(400, 'Error proveedor no soportado');
        }
        if (!is_string($plataforma) || !in_array($plataforma, self::PLATAFORMAS, true)) {
            return $this->error_http(400, 'Error plataforma no soportada');
        }

        $usuario_id = (int)$claims['sub'];
        $registro = array(
            'proveedor' => $proveedor,
            'plataforma' => $plataforma,
            'adm_usuario_id' => $usuario_id,
            'base_movil_sesion_id' => (int)$claims['sesion_id'],
            'ultimo_registro' => date('Y-m-d H:i:s'),
        );

        $this->sesion->usuario_para_modelos($usuario_id);
        $modelo = new base_movil_push_token($this->link);

        $existente = $modelo->filtro_and(filtro: array('base_movil_push_token.token' => $token));
        if (errores::$error) {
            return $this->error_http(500, 'Error al consultar el dispositivo');
        }

        if ((int)$existente->n_registros > 0) {
            // El mismo teléfono que vuelve a entrar: se actualiza y se reactiva.
            $registro['status'] = 'activo';
            $registro['motivo_baja'] = '';
            $r = $modelo->modifica_bd(
                registro: $registro,
                id: (int)$existente->registros[0]['base_movil_push_token_id'],
                reactiva: true
            );
        } else {
            $registro['token'] = $token;
            $modelo->registro = $registro;
            $r = $modelo->alta_bd();
        }
        if (errores::$error) {
            error_log('base_movil registra_push_token: ' . ($r['mensaje'] ?? 'sin mensaje'));
            return $this->error_http(500, 'Error al registrar el dispositivo');
        }

        return array('registrado' => true);
    }

    /**
     * Prueba: el usuario de la sesión se manda una notificación que al
     * tocarla abre la pantalla de estado. Apagada salvo push_prueba = true.
     */
    public function envia_notificacion_prueba(): array
    {
        if (($this->generales->push_prueba ?? false) !== true) {
            return $this->error_http(404, 'Error metodo no habilitado');
        }

        $claims = $this->sesion->valida_token();
        if (isset($claims['error'])) {
            errores::$error = false;
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        return $this->envia_a_usuario(
            usuario_id: (int)$claims['sub'],
            titulo: 'Notificación de prueba',
            texto: 'Tócala para abrir el estado del dispositivo.',
            accion: array('type' => 'NAVIGATE', 'screenId' => 'estado')
        );
    }

    /**
     * Envía a todos los dispositivos activos del usuario cuya sesión siga
     * vigente. Un token que el proveedor rechaza se da de baja.
     * Devuelve array('enviadas' => n, 'fallidas' => n) o un error.
     *
     * $accion: la misma forma que la acción de un botón. La app solo
     * ejecuta NAVIGATE o CALL desde una notificación.
     */
    public function envia_a_usuario(int $usuario_id, string $titulo, string $texto, ?array $accion = null): array
    {
        $envio = fcm_envio::desde_archivo((string)($this->generales->fcm_cuenta_servicio ?? ''));
        if (is_string($envio)) {
            error_log('base_movil push: ' . $envio);
            return $this->error_http(500, 'Error las notificaciones no están configuradas en el servidor');
        }

        $tokens = $this->tokens_activos($usuario_id);
        if (isset($tokens['error'])) {
            return $tokens;
        }

        $enviadas = 0;
        $fallidas = 0;
        foreach ($tokens['valor'] as $fila) {
            $r = $envio->envia($fila['token'], $titulo, $texto, $accion);
            if ($r['ok']) {
                $enviadas++;
                continue;
            }
            $fallidas++;
            error_log('base_movil push token ' . $fila['id'] . ': ' . $r['motivo']);
            if ($r['token_invalido']) {
                $this->da_de_baja(id: $fila['id'], usuario_id: $usuario_id, motivo: 'rechazado por el proveedor');
            }
        }

        return array('enviadas' => $enviadas, 'fallidas' => $fallidas);
    }

    /**
     * Tokens activos del usuario con sesión activa y no vencida.
     * La vigencia va en filtro_rango (desde ahora hasta un tope lejano): las
     * fechas las arma el servidor, ningún dato del cliente entra ahí.
     */
    private function tokens_activos(int $usuario_id): array
    {
        $r = (new base_movil_push_token($this->link))->filtro_and(
            filtro: array(
                'base_movil_push_token.adm_usuario_id' => $usuario_id,
                'base_movil_push_token.status' => 'activo',
                'base_movil_sesion.status' => 'activo',
            ),
            filtro_rango: array(
                'base_movil_sesion.expira' => array('valor1' => date('Y-m-d H:i:s'), 'valor2' => '9999-12-31 23:59:59'),
            )
        );
        if (errores::$error) {
            error_log('base_movil tokens_activos: ' . ($r['mensaje'] ?? 'sin mensaje'));
            return $this->error_http(500, 'Error al consultar los dispositivos');
        }

        $tokens = array();
        foreach ($r->registros as $fila) {
            $tokens[] = array(
                'id' => (int)$fila['base_movil_push_token_id'],
                'token' => (string)$fila['base_movil_push_token_token'],
            );
        }
        return array('valor' => $tokens);
    }

    /** De mejor esfuerzo: si falla, el token se vuelve a rechazar en el próximo envío. */
    private function da_de_baja(int $id, int $usuario_id, string $motivo): void
    {
        $this->sesion->usuario_para_modelos($usuario_id);
        $r = (new base_movil_push_token($this->link))->modifica_bd(
            registro: array('status' => 'inactivo', 'motivo_baja' => $motivo),
            id: $id
        );
        if (errores::$error) {
            error_log('base_movil da_de_baja push: ' . ($r['mensaje'] ?? 'sin mensaje'));
            // Error manejado: se limpia el flag global para no contaminar los envíos que siguen.
            errores::$error = false;
        }
    }

    private function error_http(int $http_code, string $mensaje): array
    {
        $error = $this->errores->error(mensaje: $mensaje, data: array());
        $error['http_code'] = $http_code;
        return $error;
    }
}
