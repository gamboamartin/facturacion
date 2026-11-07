<?php
namespace base_movil\v1\src;

use config\generales;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use gamboamartin\administrador\models\adm_usuario;
use gamboamartin\facturacion\models\base_movil_push_token;
use gamboamartin\facturacion\models\base_movil_sesion;
use gamboamartin\encripta\encriptador;
use gamboamartin\errores\errores;
use PDO;
use Throwable;

/**
 * Autenticación de la app: login, logout y validación de JWT.
 *
 * - El login reutiliza el flujo del sistema web (controlador_adm_session::loguea):
 *   mismo flag encripta_md5 de generales y mismo adm_usuario::valida_usuario_password,
 *   así entran los mismos usuarios con su contraseña de siempre.
 * - #6: cada token tiene su fila en base_movil_sesion desde que nace. Un token
 *   vale solo si su firma es correcta, no expiró Y su sesión está activa. Así
 *   se puede cortar una sesión (o todas las de un usuario o dispositivo) al
 *   momento, sin esperar a que el token venza.
 * - La sesión del servidor es el JWT + su fila (no se usa sesión PHP).
 * - Todas las consultas van por los modelos del framework (filtro_and,
 *   alta_bd, modifica_bd, elimina_bd). Los modelos toman el usuario de
 *   $_SESSION['usuario_id'] para la auditoría: usuario_para_modelos() lo
 *   asigna antes de crearlos.
 *
 * Igual que app_screens: los métodos públicos devuelven arrays y el
 * enrutador (index.php) decide cómo responder.
 */
class app_sesion
{
    private const ALGORITMO = 'HS256';

    /** Duración por defecto: una jornada. Se puede cambiar con jwt_duracion en generales. */
    private const DURACION_SEGUNDOS = 8 * 60 * 60;

    /** ultimo_uso se actualiza como máximo una vez cada 5 minutos por sesión. */
    private const INTERVALO_ULTIMO_USO = 5 * 60;

    /** Días que se guardan las sesiones como bitácora antes de borrarlas. */
    private const DIAS_HISTORIAL = 90;

    /** Probabilidad (1 de N logins) de correr la limpieza del historial. */
    private const LIMPIEZA_UNO_DE = 100;

    /** Sesiones que borra cada limpieza como máximo: el login no debe tardar. */
    private const LIMPIEZA_MAXIMO = 200;

    private errores $errores;
    private PDO $link;
    private generales $generales;

    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->link = $link;
        $this->generales = new generales();
    }

    /**
     * Recibe { values: { usuario, password } }.
     * Correcto:   { session: { token }, action: NAVIGATE pantalla inicial (reset) }
     * Incorrecto: { errors: { password: '...' } } → la app lo pinta en el Form.
     */
    public function login(): array
    {
        $secreto = $this->secreto();
        if (isset($secreto['error'])) {
            return $secreto;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $values = $body['values'] ?? null;

        if (!is_array($values)) {
            return $this->errores->error(mensaje: 'Error el body debe traer "values"', data: array());
        }

        $usuario = $values['usuario'] ?? '';
        $password = $values['password'] ?? '';

        if (!is_string($usuario) || !is_string($password) || trim($usuario) === '' || $password === '') {
            return array('errors' => array('password' => 'Usuario y contraseña son obligatorios'));
        }

        $password_bd = $this->password_como_en_bd($password);
        if (isset($password_bd['error'])) {
            return $password_bd;
        }

        $registro = (new adm_usuario($this->link))->valida_usuario_password(
            password: $password_bd['valor'],
            usuario: trim($usuario)
        );

        if (errores::$error) {
            // Credenciales inválidas. Mensaje genérico a propósito: no revela
            // si el usuario existe. Se limpia el flag global de errores porque
            // esto es un resultado esperado, no una falla del sistema.
            // Si fue una falla de la consulta, queda en el log del servidor.
            // Solo el mensaje: el detalle del error puede traer el SQL con la contraseña.
            error_log('base_movil login: ' . ($registro['mensaje'] ?? 'sin mensaje'));
            errores::$error = false;
            return array('errors' => array('password' => 'Usuario o contraseña incorrectos'));
        }

        $usuario_id = (int)$registro['adm_usuario_id'];
        $ahora = time();
        $claims = array(
            'sub' => (string)$usuario_id,
            'iat' => $ahora,
            'exp' => $ahora + $this->duracion_sesion(),
            'jti' => bin2hex(random_bytes(16)),
        );

        // Sin su fila de sesión el token no serviría: si no se puede guardar,
        // el login falla en lugar de entregar un token inútil.
        $sesion = $this->registra_sesion(
            jti: $claims['jti'],
            usuario_id: $usuario_id,
            iat: $claims['iat'],
            exp: $claims['exp']
        );
        if (isset($sesion['error'])) {
            return $sesion;
        }

        $this->limpia_historial_a_veces();

        $token = JWT::encode($claims, $secreto['valor'], self::ALGORITMO);

        return array(
            'session' => array('token' => $token),
            'action' => array('type' => 'NAVIGATE', 'screenId' => app_config::PANTALLA_INICIAL, 'reset' => true),
        );
    }

    /**
     * Cierra la sesión del token actual (si es válido) y manda al login.
     * Si el token ya no era válido, igual se responde lo mismo: la app
     * borra el token local de todas formas.
     */
    public function logout(): array
    {
        $respuesta = array(
            'action' => array('type' => 'NAVIGATE', 'screenId' => 'login', 'reset' => true),
        );

        $claims = $this->valida_token();
        if (isset($claims['error'])) {
            errores::$error = false;
            return $respuesta;
        }

        $cierre = $this->cierra_sesion(
            sesion_id: (int)$claims['sesion_id'],
            usuario_id: (int)$claims['sub'],
            motivo: 'logout'
        );
        if (isset($cierre['error'])) {
            return $cierre;
        }

        return $respuesta;
    }

    /**
     * Valida el JWT de la cabecera Authorization: firma, expiración y que su
     * sesión siga activa. Devuelve los claims (más sesion_id, el id de su
     * fila en base_movil_sesion) o un error.
     */
    public function valida_token(): array
    {
        $secreto = $this->secreto();
        if (isset($secreto['error'])) {
            return $secreto;
        }

        $token = $this->token_de_cabecera();
        if ($token === '') {
            return $this->errores->error(mensaje: 'Error no hay token de sesión', data: array());
        }

        try {
            $decodificado = JWT::decode($token, new Key($secreto['valor'], self::ALGORITMO));
        } catch (Throwable $e) {
            // La librería lanza excepciones (firma inválida, expirado, mal formado).
            return $this->errores->error(mensaje: 'Error token inválido', data: array('motivo' => $e->getMessage()));
        }

        $claims = (array)$decodificado;

        if (!isset($claims['jti'], $claims['sub'], $claims['exp'])) {
            return $this->errores->error(mensaje: 'Error token incompleto', data: array());
        }

        $sesion = $this->sesion_activa(jti: (string)$claims['jti'], usuario_id: (int)$claims['sub']);
        if (isset($sesion['error'])) {
            return $sesion;
        }

        $this->marca_ultimo_uso(sesion: $sesion, usuario_id: (int)$claims['sub']);

        // #7: id de la sesión, para ligarle el token de notificaciones.
        $claims['sesion_id'] = $sesion['id'];

        return $claims;
    }

    /** Atajo para app_screens: ¿la petición trae una sesión válida? */
    public function tiene_sesion(): bool
    {
        $claims = $this->valida_token();
        if (isset($claims['error'])) {
            errores::$error = false;
            return false;
        }
        return true;
    }

    /**
     * Los modelos del framework toman el usuario de $_SESSION['usuario_id']
     * al construirse (usuario_alta_id, usuario_update_id) y sin él no
     * escriben. El API no usa sesión PHP, así que se asigna con el usuario
     * del token ANTES de crear el modelo. Si ya hay un usuario (el sistema
     * web llamando a este código), no se toca.
     */
    public function usuario_para_modelos(int $usuario_id): void
    {
        if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] > 0) {
            return;
        }
        $_SESSION['usuario_id'] = $usuario_id;
    }

    /** Aplica la misma regla que el login web: encripta_md5 de generales. */
    private function password_como_en_bd(string $password): array
    {
        if (!$this->generales->encripta_md5) {
            return array('valor' => $password);
        }

        $encriptado = (new encriptador())->encripta_md5($password);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al encriptar password', data: array());
        }
        return array('valor' => $encriptado);
    }

    /**
     * Segundos de vigencia del token. Si config/generales define
     * jwt_duracion (entero > 0) se usa ese valor; si no, una jornada.
     * Sirve para probar el vencimiento en local sin esperar 8 horas.
     */
    private function duracion_sesion(): int
    {
        if (!property_exists($this->generales, 'jwt_duracion')) {
            return self::DURACION_SEGUNDOS;
        }
        $duracion = $this->generales->jwt_duracion;
        if (!is_int($duracion) || $duracion <= 0) {
            return self::DURACION_SEGUNDOS;
        }
        return $duracion;
    }

    private function secreto(): array
    {
        $secreto = $this->generales->jwt_secret_key ?? '';
        if (!is_string($secreto) || strlen($secreto) < 32) {
            return $this->errores->error(
                mensaje: 'Error falta jwt_secret_key (mínimo 32 caracteres) en config/generales',
                data: array()
            );
        }
        return array('valor' => $secreto);
    }

    /**
     * Lee "Authorization: Bearer <token>". Se revisan las tres fuentes porque
     * Apache (Laragon) a veces no pasa la cabecera en HTTP_AUTHORIZATION;
     * en Nginx (producción) llega directo.
     */
    private function token_de_cabecera(): string
    {
        $cabecera = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if ($cabecera === '' && function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            $cabecera = $headers['authorization'] ?? '';
        }

        if (!is_string($cabecera) || stripos($cabecera, 'Bearer ') !== 0) {
            return '';
        }
        return trim(substr($cabecera, 7));
    }

    /** IP desde la que llega la petición. Con la VPN será la IP del túnel del dispositivo. */
    private function ip_cliente(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!is_string($ip)) {
            return '';
        }
        return substr($ip, 0, 45);
    }

    /**
     * Crea la fila de la sesión del token recién emitido.
     * El usuario de la auditoría es el que inicia sesión.
     */
    private function registra_sesion(string $jti, int $usuario_id, int $iat, int $exp): array
    {
        $this->usuario_para_modelos($usuario_id);

        $modelo = new base_movil_sesion($this->link);
        $modelo->registro = array(
            'jti' => $jti,
            'adm_usuario_id' => $usuario_id,
            'ip' => $this->ip_cliente(),
            'expira' => date('Y-m-d H:i:s', $exp),
            'ultimo_uso' => date('Y-m-d H:i:s', $iat),
        );
        $r_alta = $modelo->alta_bd();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al registrar la sesión', data: $r_alta);
        }

        return array('valor' => true);
    }

    /**
     * Busca la sesión del token. Es válida solo si existe, está activa y
     * pertenece al mismo usuario que dice el token.
     * Devuelve array('id' => ..., 'ultimo_uso' => ...) o un error.
     */
    private function sesion_activa(string $jti, int $usuario_id): array
    {
        $modelo = new base_movil_sesion($this->link);
        $r = $modelo->filtro_and(filtro: array('base_movil_sesion.jti' => $jti));
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al consultar la sesión', data: $r);
        }

        if ((int)$r->n_registros !== 1) {
            return $this->errores->error(mensaje: 'Error sesión inexistente', data: array());
        }

        $fila = $r->registros[0];

        if (($fila['base_movil_sesion_status'] ?? '') !== 'activo') {
            return $this->errores->error(mensaje: 'Error sesión cerrada', data: array());
        }

        if ((int)($fila['base_movil_sesion_adm_usuario_id'] ?? 0) !== $usuario_id) {
            return $this->errores->error(mensaje: 'Error la sesión no corresponde al usuario', data: array());
        }

        return array(
            'id' => (int)$fila['base_movil_sesion_id'],
            'ultimo_uso' => (string)($fila['base_movil_sesion_ultimo_uso'] ?? ''),
        );
    }

    /**
     * Actualiza ultimo_uso como máximo una vez cada INTERVALO_ULTIMO_USO, para
     * no escribir en la base en cada petición. Es de mejor esfuerzo: si falla,
     * la petición sigue (solo se pierde la precisión de la última conexión).
     */
    private function marca_ultimo_uso(array $sesion, int $usuario_id): void
    {
        $ultimo = strtotime($sesion['ultimo_uso']);
        if ($ultimo !== false && time() - $ultimo < self::INTERVALO_ULTIMO_USO) {
            return;
        }

        $this->usuario_para_modelos($usuario_id);
        $r = (new base_movil_sesion($this->link))->modifica_bd(
            registro: array('ultimo_uso' => date('Y-m-d H:i:s')),
            id: $sesion['id']
        );
        if (errores::$error) {
            error_log('base_movil ultimo_uso: ' . ($r['mensaje'] ?? 'sin mensaje'));
            // Error manejado: se limpia el flag global para no contaminar lo que sigue.
            errores::$error = false;
        }
    }

    /**
     * Cierra la sesión: queda inactiva, con la fecha y el motivo. La recibe ya
     * validada (activa y del usuario), así que se modifica por su id.
     */
    private function cierra_sesion(int $sesion_id, int $usuario_id, string $motivo): array
    {
        $this->usuario_para_modelos($usuario_id);
        $r = (new base_movil_sesion($this->link))->modifica_bd(
            registro: array(
                'status' => 'inactivo',
                'cerrada_en' => date('Y-m-d H:i:s'),
                'motivo_cierre' => $motivo,
            ),
            id: $sesion_id
        );
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al cerrar la sesión', data: $r);
        }

        $this->baja_push_de_sesion(sesion_id: $sesion_id, motivo: $motivo);

        return array('valor' => true);
    }

    /**
     * #7: los tokens de notificaciones registrados con esa sesión dejan de
     * recibir. De mejor esfuerzo: si falla, el envío igual descarta los
     * tokens cuya sesión ya no está activa (app_push::tokens_activos).
     */
    private function baja_push_de_sesion(int $sesion_id, string $motivo): void
    {
        $modelo = new base_movil_push_token($this->link);
        $r = $modelo->filtro_and(filtro: array(
            'base_movil_push_token.base_movil_sesion_id' => $sesion_id,
            'base_movil_push_token.status' => 'activo',
        ));
        if (errores::$error) {
            error_log('base_movil baja push: ' . ($r['mensaje'] ?? 'sin mensaje'));
            errores::$error = false;
            return;
        }

        foreach ($r->registros as $fila) {
            $upd = $modelo->modifica_bd(
                registro: array('status' => 'inactivo', 'motivo_baja' => $motivo),
                id: (int)$fila['base_movil_push_token_id']
            );
            if (errores::$error) {
                error_log('base_movil baja push ' . $fila['base_movil_push_token_id'] . ': ' . ($upd['mensaje'] ?? 'sin mensaje'));
                errores::$error = false;
            }
        }
    }

    /**
     * Borra las sesiones de más de DIAS_HISTORIAL días (por su vencimiento),
     * estén cerradas o hayan vencido solas. Corre en 1 de cada LIMPIEZA_UNO_DE
     * logins para no necesitar una tarea programada, y borra como máximo
     * LIMPIEZA_MAXIMO por vez. Si falla, no afecta al login.
     * El rango de fechas lo arma el servidor: ningún dato del cliente entra
     * en filtro_rango.
     */
    private function limpia_historial_a_veces(): void
    {
        if (random_int(1, self::LIMPIEZA_UNO_DE) !== 1) {
            return;
        }

        $limite = date('Y-m-d H:i:s', time() - self::DIAS_HISTORIAL * 86400);
        $modelo = new base_movil_sesion($this->link);
        $r = $modelo->filtro_and(
            filtro_rango: array('base_movil_sesion.expira' => array('valor1' => '1970-01-01 00:00:00', 'valor2' => $limite)),
            limit: self::LIMPIEZA_MAXIMO
        );
        if (errores::$error) {
            error_log('base_movil limpieza de sesiones: ' . ($r['mensaje'] ?? 'sin mensaje'));
            errores::$error = false;
            return;
        }

        foreach ($r->registros as $fila) {
            $del = $modelo->elimina_bd(id: (int)$fila['base_movil_sesion_id']);
            if (errores::$error) {
                error_log('base_movil limpieza sesion ' . $fila['base_movil_sesion_id'] . ': ' . ($del['mensaje'] ?? 'sin mensaje'));
                errores::$error = false;
            }
        }
    }
}
