<?php
namespace base_movil\v1\src;

use Firebase\JWT\JWT;
use Throwable;

/**
 * Envío de notificaciones por Firebase Cloud Messaging, API HTTP v1 (#7).
 *
 * Autenticación: con la cuenta de servicio del proyecto de Firebase se firma
 * un JWT (RS256, con firebase/php-jwt, que ya está instalado) y se cambia por
 * un token de acceso de Google que dura una hora.
 *
 * La cuenta de servicio es un SECRETO: el archivo JSON vive en el servidor,
 * fuera de la carpeta pública, y su ruta va en config/generales.php
 * (fcm_cuenta_servicio). Nunca en el repo ni en el chat. Nada de su
 * contenido sale en logs ni en errores.
 *
 * Formato del mensaje (probado en un teléfono real el 07/11/2026):
 * - notification (title, body): Android la muestra de forma nativa con la app
 *   en segundo plano o cerrada, en cualquier marca de teléfono. Un mensaje de
 *   solo datos no basta: varios fabricantes no lo muestran, y expo-notifications
 *   lo trata como comportamiento heredado.
 * - data, con las claves que entiende expo-notifications en Android: title,
 *   message, channelId y body (un JSON en texto con la acción). Con la app
 *   abierta la muestra la app, y al tocarla, en cualquier estado, la app lee
 *   la acción de body.
 */
class fcm_envio
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CANAL = 'default';
    private const TIMEOUT_SEGUNDOS = 10;

    /** Token de acceso de Google para esta petición (dura una hora; no se guarda). */
    private ?string $access_token = null;

    private array $cuenta;

    /** @param array $cuenta contenido de la cuenta de servicio (project_id, client_email, private_key, token_uri) */
    private function __construct(array $cuenta)
    {
        $this->cuenta = $cuenta;
    }

    /**
     * Crea el envío leyendo la cuenta de servicio desde la ruta indicada.
     * Devuelve el objeto o un string con el motivo (sin datos de la cuenta).
     */
    public static function desde_archivo(string $ruta): fcm_envio|string
    {
        if ($ruta === '' || !is_file($ruta) || !is_readable($ruta)) {
            return 'no se encontró la cuenta de servicio de Firebase';
        }
        $cuenta = json_decode((string)file_get_contents($ruta), true);
        if (!is_array($cuenta)) {
            return 'la cuenta de servicio de Firebase no es un JSON válido';
        }
        foreach (array('project_id', 'client_email', 'private_key', 'token_uri') as $campo) {
            if (!isset($cuenta[$campo]) || !is_string($cuenta[$campo]) || $cuenta[$campo] === '') {
                return "a la cuenta de servicio de Firebase le falta $campo";
            }
        }
        return new self($cuenta);
    }

    /**
     * Mensaje para FCM. Público para poder probarlo sin enviar nada.
     * Todos los valores de `data` deben ser texto (lo exige FCM).
     */
    public static function mensaje(string $token, string $titulo, string $texto, ?array $accion): array
    {
        $body = $accion === null ? new \stdClass() : array('action' => $accion);
        return array(
            'message' => array(
                'token' => $token,
                'notification' => array(
                    'title' => $titulo,
                    'body' => $texto,
                ),
                'data' => array(
                    'title' => $titulo,
                    'message' => $texto,
                    'channelId' => self::CANAL,
                    'body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                ),
                // Prioridad alta: se entrega aunque el teléfono esté en reposo.
                // channel_id: el canal que crea la app (fcmProvider.ts).
                'android' => array(
                    'priority' => 'HIGH',
                    'notification' => array('channel_id' => self::CANAL),
                ),
            ),
        );
    }

    /**
     * Envía una notificación a un token.
     * Devuelve:
     *   array('ok' => true)
     *   array('ok' => false, 'token_invalido' => bool, 'motivo' => string)
     * token_invalido = el proveedor dice que ese token ya no existe: hay que darlo de baja.
     */
    public function envia(string $token, string $titulo, string $texto, ?array $accion): array
    {
        $access = $this->access_token();
        if (isset($access['error'])) {
            return array('ok' => false, 'token_invalido' => false, 'motivo' => $access['error']);
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($this->cuenta['project_id']) . '/messages:send';
        $respuesta = $this->post_json($url, self::mensaje($token, $titulo, $texto, $accion), $access['valor']);
        if (isset($respuesta['error'])) {
            return array('ok' => false, 'token_invalido' => false, 'motivo' => $respuesta['error']);
        }

        if ($respuesta['codigo'] === 200) {
            return array('ok' => true);
        }

        $estado = $respuesta['json']['error']['status'] ?? '';
        $detalle = $respuesta['json']['error']['details'][0]['errorCode'] ?? '';
        // 404 UNREGISTERED: la app se desinstaló o el token caducó.
        // 400 INVALID_ARGUMENT con errorCode INVALID_ARGUMENT: el token no es válido.
        $invalido = $respuesta['codigo'] === 404 || $detalle === 'UNREGISTERED'
            || ($respuesta['codigo'] === 400 && $estado === 'INVALID_ARGUMENT');

        return array(
            'ok' => false,
            'token_invalido' => $invalido,
            'motivo' => 'FCM respondió ' . $respuesta['codigo'] . ($estado !== '' ? " $estado" : ''),
        );
    }

    /** Cambia un JWT firmado con la cuenta de servicio por un token de acceso de Google. */
    private function access_token(): array
    {
        if ($this->access_token !== null) {
            return array('valor' => $this->access_token);
        }

        $ahora = time();
        try {
            $assertion = JWT::encode(array(
                'iss' => $this->cuenta['client_email'],
                'scope' => self::SCOPE,
                'aud' => $this->cuenta['token_uri'],
                'iat' => $ahora,
                'exp' => $ahora + 3600,
            ), $this->cuenta['private_key'], 'RS256');
        } catch (Throwable $e) {
            return array('error' => 'no se pudo firmar con la cuenta de servicio');
        }

        $respuesta = $this->post_form($this->cuenta['token_uri'], array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ));
        if (isset($respuesta['error'])) {
            return $respuesta;
        }
        $token = $respuesta['json']['access_token'] ?? null;
        if ($respuesta['codigo'] !== 200 || !is_string($token) || $token === '') {
            return array('error' => 'Google no entregó el token de acceso (' . $respuesta['codigo'] . ')');
        }

        $this->access_token = $token;
        return array('valor' => $token);
    }

    private function post_json(string $url, array $cuerpo, string $access_token): array
    {
        return $this->post($url, json_encode($cuerpo, JSON_UNESCAPED_UNICODE), array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . $access_token,
        ));
    }

    private function post_form(string $url, array $campos): array
    {
        return $this->post($url, http_build_query($campos), array('Content-Type: application/x-www-form-urlencoded'));
    }

    /** POST con verificación TLS. Devuelve array('codigo' => int, 'json' => array) o array('error' => string). */
    private function post(string $url, string $cuerpo, array $cabeceras): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));
        $texto = curl_exec($curl);
        $codigo = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $falla = curl_error($curl);
        curl_close($curl);

        if ($texto === false) {
            return array('error' => 'sin respuesta de Google: ' . $falla);
        }
        $json = json_decode((string)$texto, true);
        return array('codigo' => $codigo, 'json' => is_array($json) ? $json : array());
    }
}
