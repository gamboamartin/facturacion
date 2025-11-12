<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use gamboamartin\notificaciones\mail\_mail;
use gamboamartin\notificaciones\models\not_emisor;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use stdClass;

class _email_validacion extends _mail {


    public function enviar_validacion(string $correo_destino, string $url_validacion, PDO $link): array|PHPMailer {

        $not_emisores = (new not_emisor(link: $link))->registros_activos();
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener emisor', data: $not_emisores);
        }

        if (empty($not_emisores)) {
            return (new errores())->error(mensaje: 'Error no existen emisores configurados', data: $not_emisores);
        }

        $datos_emisor = $not_emisores[0];

        $mensaje = new stdClass();

        /*
         * ================================
         * CONFIGURACIÓN DEL EMISOR SMTP
         * ================================
         * Llena estos campos manualmente.
         */
        $mensaje->not_emisor_host = $datos_emisor['not_emisor_host'];            // Servidor SMTP
        $mensaje->not_emisor_port = $datos_emisor['not_emisor_port'];            // Puerto (465=SSL, 587=TLS)
        $mensaje->not_emisor_user_name = $datos_emisor['not_emisor_user_name'];  // Usuario SMTP
        $mensaje->not_emisor_password = $datos_emisor['not_emisor_password'];    // Contraseña SMTP
        $mensaje->not_emisor_email = $datos_emisor['not_emisor_email'];          // Correo remitente

        /*
         * ================================
         * DATOS DEL RECEPTOR Y MENSAJE
         * ================================
         */
        $mensaje->not_receptor_email = $correo_destino;
        $mensaje->not_receptor_alias = $correo_destino;
        $mensaje->not_mensaje_asunto = 'Verificación de correo electrónico';

        $mensaje->not_mensaje_mensaje = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Verificación de correo electrónico</h2>
                <p>Por favor haz clic en el siguiente enlace para validar tu correo:</p>
                <p><a href='{$url_validacion}' 
                    style='background-color:#007bff;color:#fff;padding:10px 20px;
                    border-radius:5px;text-decoration:none;'>Validar correo</a></p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p>{$url_validacion}</p>
                <hr>
                <p style='font-size:12px;color:#666;'>Si no solicitaste este correo, puedes ignorarlo.</p>
            </body>
            </html>
        ";

        return $this->envia($mensaje);
    }
}

