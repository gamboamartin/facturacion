<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use gamboamartin\notificaciones\mail\_mail;
use gamboamartin\notificaciones\models\not_emisor;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use stdClass;

class _email_nomina_cliente extends _mail {

    /**
     * Envía un correo con ZIP de las nóminas.
     *
     * @param string $asunto     Asunto del Correo
     * @param string $correo     Correo destino
     * @param array $adjuntos    Arreglo de rutas absolutas a XML / PDF
     * @param PDO $link          Conexión PDO
     *
     * @return array|PHPMailer
     */
    public function enviar_nomina_cliente(string $asunto, string $correo, array $adjuntos, PDO $link): array|PHPMailer
    {
        /*
         * ================================
         * OBTENER EMISOR
         * ================================
         */
        $not_emisores = (new not_emisor(link: $link))->registros_activos();
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener emisor', data: $not_emisores);
        }

        if (empty($not_emisores)) {
            return (new errores())->error(mensaje: 'No existen emisores configurados', data: $not_emisores);
        }

        $datos_emisor = $not_emisores[0];

        /*
         * ================================
         * ARMAR MENSAJE
         * ================================
         */
        $mensaje = new stdClass();

        // Configuración SMTP
        $mensaje->not_emisor_host = $datos_emisor['not_emisor_host'];
        $mensaje->not_emisor_port = $datos_emisor['not_emisor_port'];
        $mensaje->not_emisor_user_name = $datos_emisor['not_emisor_user_name'];
        $mensaje->not_emisor_password = $datos_emisor['not_emisor_password'];
        $mensaje->not_emisor_email = $datos_emisor['not_emisor_email'];

        // Receptor
        $mensaje->not_receptor_email = $correo;
        $mensaje->not_receptor_alias = $correo;

        // Asunto
        $mensaje->not_mensaje_asunto = $asunto;

        // Cuerpo del mensaje HTML
        $mensaje->not_mensaje_mensaje = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>$asunto</h2>
                <p>Se adjuntan las facturas en archivo ZIP</p>
                
            </body>
            </html>
        ";


        /*
         * ================================
         * ENVÍO DEL CORREO
         * ================================
         */
        return $this->envia(mensaje: $mensaje,adjuntos:  $adjuntos);
    }
}
