<?php
namespace base_movil\v1\src;

use config\generales;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use gamboamartin\administrador\models\adm_usuario;
use gamboamartin\facturacion\models\base_movil_token_revocado;
use gamboamartin\encripta\encriptador;
use gamboamartin\errores\errores;
use PDO;
use Throwable;

/**
 * Autenticación de la app (Prototipo #3): login, logout y validación de JWT.
 *
 * - El login reutiliza el flujo del sistema web (controlador_adm_session::loguea):
 *   mismo flag encripta_md5 de generales y mismo adm_usuario::valida_usuario_password,
 *   así entran los mismos usuarios con su contraseña de siempre.
 * - La sesión es el JWT (no se usa $_SESSION).
 * - El logout invalida el token de verdad: su jti se guarda en
 *   base_movil_token_revocado hasta que expira.
 *
 * Igual que app_screens: los métodos públicos devuelven arrays y el
 * enrutador (index.php) decide cómo responder.
 */
class app_sesion
{
    private const ALGORITMO = 'HS256';

    /** Duración por defecto: una jornada. Se puede cambiar con jwt_duracion en generales. */
    private const DURACION_SEGUNDOS = 8 * 60 * 60;

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
     * Correcto:   { session: { token }, action: NAVIGATE home (reset) }
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

        $ahora = time();
        $claims = array(
            'sub' => (string)$registro['adm_usuario_id'],
            'iat' => $ahora,
            'exp' => $ahora + $this->duracion_sesion(),
            'jti' => bin2hex(random_bytes(16)),
        );

        $token = JWT::encode($claims, $secreto['valor'], self::ALGORITMO);

        return array(
            'session' => array('token' => $token),
            'action' => array('type' => 'NAVIGATE', 'screenId' => app_config::PANTALLA_INICIAL, 'reset' => true),
        );
    }

    /**
     * Revoca el token actual (si es válido) y manda al login.
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

        $revoca = $this->revoca_token(
            jti: (string)$claims['jti'],
            exp: (int)$claims['exp'],
            usuario_id: (int)$claims['sub']
        );
        if (isset($revoca['error'])) {
            return $revoca;
        }

        return $respuesta;
    }

    /**
     * Valida el JWT de la cabecera Authorization: firma, expiración y que
     * no esté revocado. Devuelve los claims o un error.
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

        $revocado = $this->esta_revocado(jti: (string)$claims['jti']);
        if (isset($revocado['error'])) {
            return $revocado;
        }
        if ($revocado['valor']) {
            return $this->errores->error(mensaje: 'Error token revocado', data: array());
        }

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

    private function esta_revocado(string $jti): array
    {
        $modelo = new base_movil_token_revocado($this->link);
        $r = $modelo->filtro_and(filtro: array('base_movil_token_revocado.jti' => $jti));
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al consultar tokens revocados', data: $r);
        }
        return array('valor' => (int)$r->n_registros > 0);
    }

    /**
     * Guarda el jti del token cerrado. Igual que el login web (alta_session)
     * y el endpoint de n8n: el modelo toma el usuario de $_SESSION para
     * llenar usuario_alta_id / usuario_update_id, así que se asigna con el
     * usuario dueño del token (quien cerró sesión).
     */
    private function revoca_token(string $jti, int $exp, int $usuario_id): array
    {
        $revocado = $this->esta_revocado(jti: $jti);
        if (isset($revocado['error'])) {
            return $revocado;
        }
        if ($revocado['valor']) {
            return array('valor' => true);
        }

        $_SESSION['usuario_id'] = $usuario_id;

        $modelo = new base_movil_token_revocado($this->link);
        $modelo->registro = array(
            'jti' => $jti,
            'expira' => date('Y-m-d H:i:s', $exp),
            'adm_usuario_id' => $usuario_id,
        );
        $r_alta = $modelo->alta_bd();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al revocar el token', data: $r_alta);
        }

        return array('valor' => true);
    }
}